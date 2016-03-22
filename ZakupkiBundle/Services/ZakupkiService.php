<?php

namespace ZakupkiBundle\Services;
use Guzzle\Http\Client;
use Guzzle\Http\Message\PostFile;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Exception\Exception;
use DOMDocument;
use ZipArchive;

/**
 * Class ZakupkiService
 * @package ZakupkiBundle\Services
 */
class ZakupkiService {

    /** @var \Symfony\Bridge\Monolog\Logger */
    protected $logger;

    /** @var  TwigEngine */
    protected $templating;

    /** @var \Guzzle\Service\Client */
    protected $httpClient;

    /**
     * ZakupkiService constructor.
     * @param \Monolog\Logger $logger
     * @param \Symfony\Bundle\TwigBundle\TwigEngine $twigEngine
     * @param \Guzzle\Http\Client $guzzleClient
     */
    public function __construct(Logger $logger, TwigEngine $twigEngine, Client $guzzleClient){
        $this->logger = $logger;
        $this->templating = $twigEngine;
        $this->httpClient = $guzzleClient;


        /* Setting default content-type */
        $this->httpClient->setDefaultOption('headers', [
          'Content-Type' => 'multipart/form-data',
        ]);
        $this->httpClient->setDefaultOption('ssl.certificate_authority', false);

        $this->httpClient->getEventDispatcher()->addListener(
          'request.error',
          function (\Guzzle\Common\Event $event) {

              /** @var \Guzzle\Http\Message\Response $response */
              $response = $event['response'];

              switch ($response->getStatusCode()) {
                  case 200:
                      /* Do nothing */
                      break;

                  case 400:
                  case 403:
                  case 401:
                      $event->stopPropagation();
                      break;
                  default:
                      /* Stop other events from firing when you override 401 responses */
                      $event->stopPropagation();
                      break;
              }

              dump($event);
          }
        );

    }

    /**
     * Валидация XML по XSD схеме
     *
     * @param $rawXML
     * @param $rawXSD
     * @return array|bool
     */
    public function validateXML($rawXML, $rawXSD){
        try {

            $xml = new DOMDocument();
            $xml->loadXML($rawXML);
            if (!$xml->schemaValidateSource($rawXSD)) {
                $errors = libxml_get_errors();
                foreach ($errors as $error) {

                    switch ($error->level) {
                        case LIBXML_ERR_WARNING :
                            $this->logger->addCritical('ZakupkiService: warning validate xml. Code: ' . $error->code . ' on line ' . $error->line . '; ' . $error->message);
                            break;
                        case LIBXML_ERR_ERROR:
                            $this->logger->addCritical('ZakupkiService: error validate xml. Code: ' . $error->code . ' on line ' . $error->line . '; ' . $error->message);
                            break;
                        case LIBXML_ERR_FATAL:
                            $this->logger->addCritical('ZakupkiService: Fatal validate xml. Code: ' . $error->code . ' on line ' . $error->line . '; ' . $error->message);
                            break;
                    }

                }
                libxml_clear_errors();
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            $this->logger->addCritical('ZakupkiService: Fatal on generate xml.' . $e->getMessage());
        }
    }


    /**
     * Генерация XML на основании полученны данных и шаблона
     *
     * @param $templateXML
     * @param $schemaXSD
     * @param $data
     * @return null
     */
    public function generateXML($templateXML, $schemaXSD, $data){

        $rawXML = $this->templating->render($templateXML, $data);
        return $rawXML;
//      $rawXSD = $this->templating->render($schemaXSD, $data);
//      if ($this->validateXML($rawXML, $rawXSD)) {
//          return $rawXML;
//      } else {
//          return null;
//      }
    }

    /**
     * Отправка документа в закупки
     *
     * @param $data
     * @return bool
     */
    public function postDocuments($data)
    {
        try {
            $request = curl_init();
            curl_setopt($request, CURLOPT_URL, 'https://zakupki.gov.ru/pgz/services/upload');
            curl_setopt($request, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);

            curl_setopt($request, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);


            curl_setopt($request, CURLOPT_POST, 4);
            curl_setopt($request, CURLOPT_POSTFIELDS, [
                'login' => $data->login,
                'password' => $data->pass,
                'document' =>
                    '@'            . base64_decode($data->file)
                    . ';filename=' . 'report.xml'
                    . ';type='     . 'text/xml',
                'signature' =>
                    '@'            . $data->ecp
                    . ';filename=' . 'ecp.cep'
                    . ';type='     . 'application/octet-stream'
            ]);


            $response = curl_exec($request);
            return json_encode($response);

        } catch (Exception $e) {
            return 'Fatal error';
        }
    }


}