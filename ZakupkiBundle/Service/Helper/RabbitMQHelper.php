<?php
namespace ZakupkiBundle\Service\Helper;

use Gosbook\AppRpc\StorageRpcMessage;
use HttpException;
use OldSound\RabbitMqBundle\RabbitMq\RpcClient;
use PhpAmqpLib\Exception\AMQPTimeoutException;

/**
 * Class RabbitMQHelper
 * @package ZakupkiBundle\Service\Helper
 */
class RabbitMQHelper
{
    /**
     * @var  RpcClient
     */
    public $storageClient;

    /**
     * @var string
     */
    protected $userUuid = '1d03f765-fdaa-4b64-9f88-a772eba12e51';

    /**
     * @var string
     */
    protected $foivId = '4bb574eb-be09-48fb-a087-b5b258718f0b';

    /**
     * @param RpcClient $storageClient
     */
    public function __constructor(RpcClient $storageClient)
    {
        $this->storageClient  = $storageClient;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getFOIV()
    {
        $messageCommonId = 'isz-foiv-'.time();
        $message =
            (new StorageRpcMessage())
                ->setUrl('/api/commons/'.$this->foivId)
                ->setMethod('GET')
                ->setUserId($this->userUuid);
        $this->storageClient->addRequest(
            $message->getJsonParams(),
            'isz',
            $messageCommonId,
            'app.transit.storage',
            $message->getRequestTimeout()
        );

        try {
            $replies = $this->storageClient->getReplies();

            if (!is_array($replies) || !isset($replies[$messageCommonId])) {
                throw new \Exception('No replies from RPC server '.get_class($this->storageClient));
            }

            return $replies[$messageCommonId];

        } catch (AMQPTimeoutException $e) {
            throw new HttpException(500, "RPC timed out: integrations.zakupki.plan. Check logs for details.");
        }
    }

    /**
     * @param string $commonId
     * @return mixed
     * @throws \Exception
     */
    public function getCurrentPlan($commonId)
    {
        $messageId = 'isz-current-plan-'.time();
        $message   = (new StorageRpcMessage())
            ->setUrl('/api/plans')
            ->setParamsValue('query', [
                'filters' => [
                    'type' => 'current',
                    'common' => $commonId,
                ],
            ])
            ->setMethod('GET')
            ->setUserId($this->userUuid);
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

            return current($replies[$messageId]);

        } catch (AMQPTimeoutException $e) {
            throw new HttpException(500, "RPC timed out: integrations.zakupki.plan. Check logs for details.");
        }
    }

    /**
     * @param string $planId
     * @return mixed
     * @throws \Exception
     */
    public function getLots($planId)
    {
        $messageId = 'isz-lots-'.time();
        $message   = (new StorageRpcMessage())
            ->setUrl('/api/plans/'.$planId.'/consolidated/lots.json')
            ->setMethod('GET')
            ->setUserId($this->userUuid);
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

        } catch (AMQPTimeoutException $e) {
            throw new HttpException(500, "RPC timed out: integrations.zakupki.plan. Check logs for details.");
        }
    }
}
