## Сервис FreshDoc

Данный bundle описывает принцип взаимодействия с FreshDoc. В основе реализации лежит **interface FreshDocInterface**, в 
нем описанны основные методы взаимодействия шины и API FreshDoc

Дополнительные методы описаны в **abstract FreshDocAbstract**

#### Методы интерфейса

`generateDocumentTask($message)` - Формирует задания на генерацию документа и отправляет запрос в очередь задач "на генерации"
```php
$message = [
   'entityData' => "Обьект сущности с обязательным полем {id}",
   'templateId' => "Идентификатор шаблона созданного во фрешдок",
   'format' => "формат документа"
];
generateDocumentTask((object)$message));
```
В случае удачного выполнения задачи возврашает
```php
return [
    'success' => true,
    'result' => "",
];
```


`generateDocument($message)` - Отправка запроса генерации на API FreshDoc
```php
$message = [
   'entityData' => "Обьект сущности с обязательным полем {id}",
   'templateId' => "Идентификатор шаблона созданного во фрешдок",
   'format' => "формат документа"
];
generateDocument((object)$message));
```
В случае удачного выполнения задачи возврашает
```php
return [
    'success' => true,
    'result' => "",
];
```


`downloadDocument($message)` - Получение документа через API FreshDoc
```php
$message = [
   'uri' => "URI адрес документа в системе FreshDoc",
   'format' => "формат документа"
];
downloadDocument((object)$message));
```
В случае удачного выполнения задачи возврашает
```php
return [
    'success' => true,
    'result' => "",
];
```