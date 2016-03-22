<?php
/**
 * Created by PhpStorm.
 * User: ekretsu
 * Date: 18.11.15
 * Time: 17:13
 */

namespace ZakupkiBundle\Reports;

use Gosbook\AppRpc\StorageRpcMessage;
use ZakupkiBundle\Services\ZakupkiService;
use OldSound\RabbitMqBundle\RabbitMq\RpcClient;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class ReportAbstract implements ReportInterface {

    CONST SCHEME_VERSION = "1.0";

    const XML_TEMPLATE = 'ZakupkiBundle:Consumer/Notice:base.html.twig';

    const XSD_TEMPLATE = 'ZakupkiBundle:Consumer/Notice:base.html.twig';

    const USER_UUID = '1d03f765-fdaa-4b64-9f88-a772eba12e51';

    const FOIV_ID = '4bb574eb-be09-48fb-a087-b5b258718f0b';

    public $storageClient;

    public $xmlBuilder;

    public $message;

    /**
     * ReportAbstract constructor.
     * @param RpcClient $storageClient
     * @param ZakupkiService|null $xmlBuilder
     * @param $message
     */
    public function __construct(RpcClient $storageClient, ZakupkiService $xmlBuilder = null, $message = NULL){
        $this->storageClient = $storageClient;
        $this->xmlBuilder = $xmlBuilder;
        $this->message = $message;
    }

    /**
     * Преобразование данных под формат XML
     *
     * @return mixed
     */
     public function transducerData(){
         return __CLASS__;
     }

    /**
     * Получаем уже сформированный XML
     *
     * @return mixed
     */
    public function getXML(){
        return __CLASS__;
    }

    /**
     * Получение данных со storage
     *
     * @param $messageId
     * @param $url
     * @param array|null $query
     * @return mixed
     * @throws HttpException
     * @throws \Exception
     */
    public function getData($messageId, $url, array $query = null){
        $message = (new StorageRpcMessage())->setUrl($url)->setMethod('GET')->setUserId(static::USER_UUID);
        $message->setRequestTimeout(100);
        if (!is_null($query)) {
            $message->setParamsValue('query', $query);
        }
        $this->storageClient->addRequest(
            $message->getJsonParams(),
            'isz',
            $messageId,
            'app.transit.storage',
            $message->getRequestTimeout()
        );

        try {
            $replies = $this->storageClient->getReplies();
            if (!is_array($replies) || !isset($replies[$messageId])) {
                throw new \Exception('No replies from RPC server '.get_class($this->storageClient));
            }
            return $replies[$messageId];

        } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
            throw new HttpException(500,
                'RPC timed out. Check logs for details.');
        }

    }
}