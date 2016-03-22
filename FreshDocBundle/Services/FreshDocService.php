<?php
namespace FreshDocBundle\Services;

use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class FreshDocService
 * @package FreshDocBundle\Services
 */
class FreshDocService extends FreshDocAbstract
{

    /**
     * Отправка POST запроса на формирование документа
     *
     * @param $message
     * @return array
     * @throws \Exception
     */
    public function generateDocument($message){
        try {
            // валидируем входящие параметры
            if (
                !is_object($message) ||
                empty($message->entityData) ||
                empty($message->templateId) ||
                empty($message->format) ||
                empty($message->docId)
            ) {
                throw new \Exception("FreshDocError: на генерацию поступили не корректные данные:" . json_encode($message));
            }

            // отправляем запрос на генерацию документа
            $response = $this->generateDocumentQuery([
                'entity' => $message->entityData,
                'id' => $message->templateId,
                'format' => $message->format,
                'docId' => $message->docId
            ]);

            // при удачной генерации отправляем запрос на пресохранение документа
            if ($response == "success") {
                return [
                    'success' => true,
                    'result' => "Документ FreshDoc сохранен",
                ];
            }
            else {
                throw new \Exception("FreshDocError: неудалось сохранить временный документ");
            }
        } catch (Exception $e) {
            throw new \Exception("FreshDocError: ошибка отправки задания на генерацию документа:" . $e->getMessage());
        }
    }

    /**
     * Формирование задачи на генерацию документа по лоту, шаблону, формату
     *
     * @param $message
     * @return array
     * @throws \Exception
     */
    public function generateDocumentTask($message)
    {
        try {
            $this->_producer->publish(json_encode($message), self::FRESHDOC_TASK_ROUTING_KEY);

            return [
                'success' => true,
                'result' => "Задача на генерацию документа FreshDoc отправленна",
            ];
        } catch (Exception $e) {
            throw new \Exception("FreshDocError: ошибка отправки задания на генерацию документа:" . $e->getMessage());
        }
    }


    /**
     * @param $message
     * @return bool
     * @throws \Exception
     */
    public function downloadDocument($message)
    {
        try {
            // валидируем входящие параметры
            if (!is_object($message) || empty($message->uri) || empty($message->type)) {
                throw new \Exception("FreshDocError: на скаивание поступили не корректные данные:" . json_encode($message));
            }

            // отправляем запрос на скачивание документа
            return $this->getDocumentQuery([
                'uri' => $message->uri,
                'type' => $message->type,
            ]);
        } catch (Exception $e) {
            throw new \Exception("FreshDocError: ошибка загрузки документа:" . $e->getMessage());
        }

    }

}