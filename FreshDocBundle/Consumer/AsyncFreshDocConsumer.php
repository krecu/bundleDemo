<?php
namespace FreshDocBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use FreshDocBundle\Services\FreshDocService;

/**
 * Class AsyncFreshDocConsumer
 * @package FreshDocBundle\Consumer
 */
class AsyncFreshDocConsumer implements ConsumerInterface
{

    /**
     * @var FreshDocService
     */
    private $freshDocService;

    /**
     * AsyncFreshDocConsumer constructor.
     * @param FreshDocService $freshDocService
     */
    public function __construct(FreshDocService $freshDocService)
    {
        $this->freshDocService = $freshDocService;
    }

    /**
     * @param AMQPMessage $msg
     * @return bool
     */
    public function execute(AMQPMessage $msg)
    {
        $routingKey = $msg->get('routing_key');

        list($system, $subSystem, $action) = explode('.', $routingKey);

        // если запрос не в нашу подсистему что мало вероятно но возможно
        // то ничего не делаем
        if ($subSystem != "freshdoc" && $system != "integrations") {
            return;
        }

        $message = json_decode($msg->body);
        $result = null;

        // если данный метод доступен то выполняем его
        if (method_exists($this->freshDocService, $action)) {
            $result = $this->freshDocService->$action($message);
        }

        return $result;
    }

}