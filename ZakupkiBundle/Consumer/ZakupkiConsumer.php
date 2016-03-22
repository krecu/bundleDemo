<?php

namespace ZakupkiBundle\Consumer;

use Gosbook\AppRpc\StorageRpcMessage;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\RpcClient;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ZakupkiBundle\Reports\Report;
use ZakupkiBundle\Reports\ReportAbstract;
use ZakupkiBundle\Reports\ReportFactory;
use ZakupkiBundle\Service\Helper\RabbitMQHelper;
use ZakupkiBundle\Service\ZakupkiXmlGenerator;
use ZakupkiBundle\Services\ZakupkiService;
use ZakupkiBundle\ZakupkiBundle;
use \ZakupkiBundle\Reports\OpenCompetitionReport as OpenCompetitionReport;
use \ZakupkiBundle\Reports\ScheduleReport as ScheduleReport;

/**
 * Class ZakupkiConsumer
 * @package ZakupkiBundle\Consumer
 * @deprecated
 */
class ZakupkiConsumer implements ConsumerInterface
{
    /**
     * @var ZakupkiService
     */
    private $xmlGenerator;

    /** @var RpcClient  */
    private $storageClient;

    /**
     * @param ZakupkiService $xmlGenerator
     * @param RpcClient $storageClient
     */
    public function __construct(ZakupkiService $xmlGenerator, RpcClient $storageClient){
        $this->xmlGenerator   = $xmlGenerator;
        $this->storageClient   = $storageClient;
    }

    /**
     * @param AMQPMessage $msg
     * @return bool
     */
    public function execute(AMQPMessage $msg){
        $routingKey = $msg->get('routing_key');

        list($service, $object, $module, $reportType) = explode('.', $routingKey);
        /**
         * integrations.zakupki.report.OpenCompetitionReport
         *
         */
        if ($service == 'integrations' && $object == 'zakupki' && !empty($reportType)) {
            try {

                if ($reportType == 'send') {

                    $data = json_decode(json_decode($msg->body)->content);
                    return $this->xmlGenerator->postDocuments($data);

                } else {
                    $className = "\\ZakupkiBundle\\Reports\\".$reportType;
                    if (class_exists($className)) {
                        // @todo - HARDCODE!!!!!
                        $data = json_decode(json_decode($msg->body)->content);
                        /** @var ReportAbstract $report */
                        $report = new $className(
                          $this->storageClient,
                          $this->xmlGenerator,
                          $data
                        );
                        $xml = $report->getXML();

                        return $xml;
                    } else {
                        throw new HttpException(
                          500,
                          "Not found type report: $className"
                        );
                    }
                }
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                throw new HttpException(500, "RPC timed out. Check logs for details.");
            }
        }
    }

}
