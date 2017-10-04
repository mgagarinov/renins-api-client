# Как развернуть проект для тестирования

* Установите [composer](https://getcomposer.org/download/)

* Установите php версии не ниже 7.1

* Клонируйте репозиторий

```bash
git clone git@github.com:Andrey-Kharitonov/renins-api-client.git
```

* Выполните в папке проекта:
```bash
composer install
```

* Выполняйте команды "phpunit". Подробнее в разделе "Тестирование".

# Клиент

Может быть создан через фабрику ReninsApi\Factory или напрямую как экземпляр класса ReninsApi\Client\ApiVersion2.
Клиент может работать в режиме теста. В этом случае будут использован тестовый стенд сервиса.

Клиент использует REST-запросы и SOAP-запросы. REST для получения некоторых справочных данных.
Например: список марок и моделей авто. SOAP используется для расчетов, для импорта полисов, для получения печатных форм.

# Контейнеры

Основная логика приложения построена на контейнерах - классах унаследованных от ReninsApi\Request\Container.
Контейнер может иметь свойства любого типа, в т.ч. другие контейнеры и коллекцию контейнеров.

* Контейнер может проверить сам себя на валидность. Для этого он содержит защищенное свойство $rules.
Валидация запускается явным образом (методы validate() или validateThrow()). Валидация проводится рекурсивно по
всем вложенным контейнерам и коллекциям
* После проверки можно запросить массив ошибок (метод getErrors()) 
* Контейнер может быть сериализован в XML или массив (методы toXml() и toArray()). Это также производится рекурсивно.
* Контейнер может быть создан из XML (метод fromXml() или стат. метод createFromXml()). Конечно контейнер создаст все
вложенные контейнеры и коллекции 
* Аналогично контейнер может быть создан из объектов stdClass (метод fromXml() или стат. метод createFromXml()). 

Если контейнер предназначен для составления запроса в формате XML, он должен правильно сериализоваться в XML.
По умолчанию все совйства сохраняются как отдельные тэги, если вам нужно другое поведение, то следует переунаследовать
метод toXml(). Подобным образом может понадобится переунаследование метода toArray().

Контейнеры, которые служат для парсинга ответов, как правило переунаследуют метод fromXml().  

## Правила валидации

$rules представляет собой массив, ассоц. по именам свойств. Каждое свойство обязательно должно быть указано в $rules, но можно указать
пустой массив правил для свойства. Правила для каждого свойства можно указывать в виде строки через запятую или в виде массива. 

Пример правил:

```php
    protected $rules = [
        'IsIP' => ['toLogical'],
        'FirstName' => ['toString', 'notEmpty'],
        'MiddleName' => ['toString', 'notEmpty'],
        'LastName' => ['toString', 'notEmpty'],
        'BirthDate' => ['toString', 'date'],
        'Gender' => ['toString', 'in:M|F'],
        'MaritalStatus' => ['toInteger', 'between:1|4'],
        'HasChildren' => ['toLogical'],
        'DriveExperience' => ['toString', 'date'],
        'Documents' => ['containerCollection:' . Document::class],
    ];
```
 
Правила, начинающиеся на 'to', являются фильтрами. Они служат только для конвертации значения при записи в свойство.
Остальные правила являются валидаторами и проверяют значение свойства. Правила фильтрации обрабатываются ReninsApi\Request\Filter, правила валидации - ReninsApi\Request\Validator.

Некоторые правила должны(могут) иметь параметры, указанные после двоеточия. Подробнее о правилах см. в ReninsApi\Request\Validator 

## Процесс валидации 

После вызова validate() можно запросить список ошибок методом getErrors(). Метод вернет плоский массив ошибок, ассоц. по названию свойств.

```php
[
    'Policy.Vehicle.Manufacturer' => '... текст ошибки ...'
    'Policy.Vehicle.Model' => '... текст ошибки ...'
    'Policy.Covers.0.code' => '... текст ошибки ...'
    'Policy.Covers.1.code' => '... текст ошибки ...'
]
```

Как видно из примера коллекция (Policy.Covers) также проверяются и результаты её проверки также собираются в список ошибок.

Метод validateThrow() создаст исключение ReninsApi\Request\ValidatorMultiException. Это исключение имеет метод getErrors(),
которое вернет тот самый массив ошибок. Метод validateThrow() вызывается перед каждым SOAP или REST запросом, а также после получения ответа
и конвертации его в соотв. контейнер.

# Тестирование

Тесты разделены на 3 части (--testsuite): client, request, response.

1. client - тестирование взаимодействия с soap и rest сервисами.
2. request - внутренние тесты сериализаций в xml и array
3. response - внутренние тесты десериализаций
4. casco-full - тестирование полного цикла по КАСКО

Часть client разделена на группы calculation-casco, calculation-osago, import-casco, import-osago,
printing-casco, printing-osago, rest.

Если мы хотим запустить тест только для client и толко для расчета каско, то необходимо выполнить

```bash
phpunit --testsuite client --group calculation-casco --debug
```

В папке tests/logs будут созданы логи тестирования в формате Html (Monolog).

В папке tests/data/temp будут созданы временные файлы для обмена между тестами и печатные формы в PDF.

Некоторые тесты зависимы друг от друга. Можно было бы использовать функцию dependency phpunit,
но такие тесты получились бы слишком длительные по времени, поэтому удобнее запускать тесты
по отдельности. Тесты обмениваются между собой данными через временные файлы.

Например: Тест по расчету КАСКО сохраняет номер ктировки, который потом нужен для теста импорта и теста печатных форм.

Поэтому удобно запустить тесты по очереди:

```bash
phpunit --testsuite client --group calculation-casco --debug
phpunit --testsuite client --group import-casco --debug
phpunit --testsuite client --group printing-casco --debug
```

casco-full проверяет сразу все операции по КАСКО:
* Расчет с созданием котировки
* Печать результатов расчета
* Получение номера полиса по котировке
* Импорт полиса по котировке
* Печать оригинала полиса - окончательное подтверждение договора 




