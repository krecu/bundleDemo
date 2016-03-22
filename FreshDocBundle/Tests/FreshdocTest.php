<?php
namespace FreshDocBundle\Tests;

use Gosbook\AppRpc\SchemaRpcMessage;
use Gosbook\AppRpc\StorageRpcMessage;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FreshdocTest extends WebTestCase
{
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
    private $container = NULL;

    public function __construct()
    {
        $client = static::createClient();
        $this->container = $client->getContainer();

        parent::__construct();
    }

    public function testFreshdoc()
    {
        /** @var \OldSound\RabbitMqBundle\RabbitMq\RpcClient $schemaRpc */
        $schemaRpc = $this->container->get('old_sound_rabbit_mq.storage_schema_rpc');

        $schemaParams = new SchemaRpcMessage();
        $schemaParams
            ->setUrl('/api/schemas/Lot')
            ->setMethod('GET')
            ->setRequestTimeout(30);

        $schemaRId = $schemaParams->createRequestId($schemaParams::ROUTING_KEY);

        $schemaRpc->addRequest(
            $schemaParams->getJsonParams(),
            'isz',
            $schemaRId,
            "schema.Lot",
            $schemaParams->getRequestTimeout()
        );

        $schemaData = $schemaRpc->getReplies()[$schemaRId];

        /** @var \FreshDocBundle\Services\FreshDocService $freshdocService */
        $freshdocService = $this->container->get('freshdoc.service');
        $resourceId = 1019975;

        if ($freshdocService->postSchema($schemaData, $resourceId)) {
            dump('Schema Post Passed');
        } else {
            dump('It was error while posting schema');
        }

        /** @var \OldSound\RabbitMqBundle\RabbitMq\RpcClient $schemaRpc */
        $storageRpc = $this->container->get('old_sound_rabbit_mq.storage_api_rpc');

        $storageParams = new StorageRpcMessage();
        $storageParams
            ->setUrl('/api/lots/b5fb9153-10de-4d06-b314-293d04fce250')
            ->setMethod('GET')
            ->setUserId($this->container->getParameter('freshdoc.xuserid'))
            ->setSubSystem($this->container->getParameter('freshdoc.xsubsystem'))
            ->setRequestTimeout(15);

        $storageRId = $storageParams->createRequestId($storageParams::ROUTING_KEY);

        $storageRpc->addRequest(
            $storageParams->getJsonParams(),
            'isz',
            $storageRId,
            'storage.api.query',
            $storageParams->getRequestTimeout()
        );

        $storageData = json_decode($storageRpc->getReplies()[$storageRId]);
        $storageData = $storageData->content;

        if ($resp = $freshdocService->postData($storageData, $resourceId, 'pdf')) {
            $result = $freshdocService->getTempDocument($resp);
            if($result) {
                $response = $freshdocService->getDocument($result, 'pdf');
            }
        } else {

        }

        $this->assertNotNull($response);
    }

}