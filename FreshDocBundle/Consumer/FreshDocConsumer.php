<?php
namespace FreshDocBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use FreshDocBundle\Services\FreshDocService;

/**
 * Class FreshDocConsumer
 * @package FreshDocBundle\Consumer
 */
class FreshDocConsumer implements ConsumerInterface
{
    /** @var FreshDocService - сервис фрешдока */
    protected $freshDocService;

    /**
     * FreshDocService constructor.
     * @param $freshDocService $freshdocService
     */
    public function __construct(FreshDocService $freshDocService)
    {
        $this->freshDocService = $freshDocService;
    }

    /**
     * @param AMQPMessage $msg
     * @return array
     * @throws \Exception
     */
    public function execute(AMQPMessage $msg)
    {
        // пример: integrations.freshdoc.#
        $routingKey = $msg->get('routing_key');

        list($system, $subSystem , $action) = explode('.', $routingKey);

        // если запрос не в нашу подсистему что мало вероятно но возможно
        // то ничего не делаем
        if ($subSystem != "freshdoc" && $system != "integrations") {
            return;
        }

        $message = json_decode($msg->body);
        $result = null;

        // если данный метод доступен то выполняем его
        if (method_exists($this->freshDocService, $action)) {
            try {
                $result = $this->freshDocService->$action($message);
            } catch (\Exception $e) {
                $result = [
                    'success' => false,
                    'result' => "FreshdocConsumer: Ошибка в методе $action",
                ];
            }
        }

        return $result;
    }
}