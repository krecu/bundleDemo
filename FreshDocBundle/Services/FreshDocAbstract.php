<?php
namespace FreshDocBundle\Services;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use Guzzle\Service\Client as GuzzleClient;
use OldSound\RabbitMqBundle\RabbitMq\RpcClient as RpcClient;
use Monolog\Logger;
use \FreshDocBundle\Transport\FreshDocTransport;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Gosbook\AppRpc\StorageRpcMessage;

/**
 * Class FreshDocService
 * @package FreshDocBundle\Services
 */
abstract class FreshDocAbstract implements FreshDocInterface
{
    const STORAGE_ROUTING_KEY = "storage.api.query";
    const FRESHDOC_TASK_ROUTING_KEY = "async.freshdoc.generateDocument";

    protected $_transport;

    protected $_logger;

    protected $_rpc;

    protected $_producer;

    protected $_config = [];

    /**
     * FreshDocAbstract constructor.
     * @param array $config
     * @param GuzzleClient $guzzle
     * @param Logger $logger
     * @param RpcClient $rpc
     * @param Producer $producer
     */
    public function __construct(array $config, GuzzleClient $guzzle, Logger $logger, RpcClient $rpc, Producer $producer)
    {
        $this->_producer = $producer;
        $this->_transport = $guzzle;
        $this->_logger = $logger;
        $this->_rpc = $rpc;
        $this->_config = $config;
    }

    /**
     * Генерация документа
     *
     * @param $message
     * @return mixed
     */
    abstract public function generateDocument($message);


    /**
     * Формирование задачи на генерацию документа по лоту, шаблону, формату
     *
     * @param $message
     * @return mixed
     */
    abstract public function generateDocumentTask($message);

    /**
     * Скачивание документа
     *
     * @param $message
     * @return mixed
     */
    abstract public function downloadDocument($message);

    /**
     * Сохранение временного файла
     *
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function saveTempDocument($data = "")
    {
        $fs = new Filesystem();
        try {
            $tmpFilename = tempnam(sys_get_temp_dir(), 'freshdoc');
            $fs->dumpFile($tmpFilename, $data);
            return $tmpFilename;
        } catch (IOExceptionInterface $e) {
            throw new \Exception("FreshDocError: неудалось сохранить временный файл:" . $e->getMessage());
        }
    }

    /**
     * @return FreshDocTransport
     */
    public function getTransportClient(){

        $this->_logger->addInfo("FRESHDOC: Попытка авторизоваться в API по данным " . json_encode($this->_config));
        try {
            return new FreshDocTransport([
                'login' => $this->_config['login'],
                'password' => $this->_config['password'],
                'client_id' => $this->_config['client_id'],
                'client_secret' => $this->_config['client_secret'],
            ], $this->_transport);
        } catch (\Exception $e) {
            $this->_logger->addInfo("FRESHDOC: авторизация в API не удалась, сообщение об ошибке: " . $e->getMessage());
        }
    }

    /**
     * Отправка запроса на формирование документа
     *
     * @param $data array['entity' => stdClass, 'format' => string, 'id' => integer]
     * @return string
     * @throws \Exception
     */
    public function generateDocumentQuery($data)
    {
        try {

            // инициализируем способ транспортировки request
            $transportClient = $this->getTransportClient();

            $filename = $this->saveTempDocument(json_encode($data['entity']));

            // инициализируем реквест
            $request = $transportClient->client->post("resources/{$data['id']}/dataimport", [
                'content-type' => 'application/x-www-form-urlencoded'
            ]);

            $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, false);
            $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, false);

            $request->addPostFields([
                'format' => $data['format'],
                'callback_url' => $this->_config['callback_url'],
                'gosbook_id' => $data['docId'],
            ]);

            $request->addPostFile('file', $filename, 'multipart/form-data', $filename);

            $response = $request->send();

            if ($response->getStatusCode() == 200) {
                $this->_logger->addInfo("FRESHDOC: Документ " . $data['docId'] . " в формате " . $data['format'] . " по шаблону " . $data['id'] . " отправленн на генерацию");
                return "success";
            } else {
                $this->_logger->addWarning("FRESHDOC: Ошибка отправки документа " . $data['docId'] . " в формате " . $data['format'] . " по шаблону " . $data['id'] . ". Получен код ошбки: " . $response->getStatusCode());
                throw new \Exception("FreshDocError: ошибка отправки документа на генерацию получен код: " . $response->getBody());
            }
        } catch (\Exception $e) {
            throw new \Exception("FreshDocError: ошибка отправки:" . $e->getMessage());
        }
    }


    /**
     * Отправка запроса на скачивание документа
     *
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function getDocumentQuery($data)
    {
        try {

            // инициализируем способ транспортировки request
            $transportClient = $this->getTransportClient();

            // на всякий случай обновляем токен приложения
            if (!$transportClient->getAuthorizeToken()) {
                throw new \Exception("FreshDocError: ошибка обновляения кода авторизации ");
            }

            // генерируем временный файл
            $filename = $this->saveTempDocument();

            // инициализируем реквест
            /** @var \Guzzle\Http\Message\Request $request */
            $request = $transportClient->client->get($data['uri'], null, [
                'query' => [
                    'authorize_token' => $transportClient->tokens['authorize_token'],
                ],
            ]);

            $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, false);
            $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, false);

            $request->setResponseBody($filename);

            $this->_logger->addInfo("FRESHDOC: отправка запроса на получение документа по адресу" . $data['uri']);
            $response = $request->send();

            if ($response->getStatusCode() == 200) {

                $zip = new \ZipArchive();
                $zip->open($filename);

                // ищем необходимый нам документ в архиве
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    // получаем имя файла
                    $docName = $zip->getNameIndex($i);
                    // если имя с нужным нам форматом
                    if (strripos($docName, '.' . $data['type'])) {
                        // получаем контент файла
                        $fileContent = $zip->getFromName($docName);
                        $this->_logger->addInfo("FRESHDOC: найден документ $docName и отправлен на APPLICATION");
                        // возврашаем доку
                        return [
                            'file' => [
                                'filename' => time() . '-' . $docName,
                                'content'  => base64_encode($fileContent),
                                'sizeunit' => 'B',
                                'size'     => $zip->statIndex($i)['size'],
                                'type'     => $data['type'],
                            ]
                        ];
                    }
                }

                $zip->close();

                return false;

            } else {
                $this->_logger->addInfo("FRESHDOC: ошибка загрузки документа на генерацию получен код: " . $response->getStatusCode());
                throw new \Exception("FreshDocError: ошибка загрузки документа на генерацию получен код: " . $response->getStatusCode());
            }
        } catch (\Exception $e) {
            throw new \Exception("FreshDocError: ошибка отправки:" . $e->getMessage());
        }
    }
}