# ClickHouse

1. **Сущность/Entity**

    1. Каждая сущность = отдельная таблица

    2. Название сущности = inTheLowerCase(название таблицы)

    3. Название свойств сущности = название столбцов

    4. Каждая сущность, которая работает через ClickHouse, должна имплементировать интерфейс [ClickHouseEntityInterface](./src/Entity/Interfaces/ClickHouseEntityInterface.php) (_необходимо для нормализации и денормализации_).


2. **Репозиторий/Repository**
   
   1. Каждый репозиторий должен наследоваться от [ClickHouseRepository](./src/ClickHouseRepository.php) (_является базовым репозиторием и имеет дефолтный функционал_) 
      и интерфейс репозитория, аналогично, должен быть унаследован от [ClickHouseRepositoryInterface](./src/Repository/Interfaces/ClickHouseRepositoryInterface.php).
   2. (Perhaps) _Конструктор базового/абстрактного репозитория принимает 3 аргумента: `normalizer, denormalizer, entityClass`. Если в процессе работы возникают ошибки при записи, возможно, вам стоит изменить формат данных или же проверить, как ваша сущность выглядит в нормализованном виде и правильно ли она маппится на таблицу. При необходимости, смотреть в [ClickHouseEntityNormalizer](./src/Serializer/Normalizer/ClickHouseEntityNormalizer.php)._


3. **Клиент/Adapter**

   1. Паттер адаптер, который закрывает взаимодствеие с ClickHouse через [интерфейс](./src/Client/Adapter/Interfaces/ClickHouseClientAdapterInterface.php) с методами select, insert, query и ping. 
      Реализует прямой доступ к HTTP интерфейсу ClickHouse. 
      
   2. **!!!Важно**: в коде, мы должны везде закрываться не реализацией, т.е. типом самого [класса адаптера](./src/Client/Adapter/ClickHouseClientAdapter.php), а его [интерфейсом](./src/Client/Adapter/Interfaces/ClickHouseClientAdapterInterface.php), для возможности подмены реализации.

   3. **!!!Важно**: в частности, [ClickHouseClientAdapter](./src/Client/Adapter/ClickHouseClientAdapter.php) реализует метод `query`, который третьим аргументом(`isAwaitableQuery`) принимает логическое значение и если оно истинно, то добавляет в запрос уникальный ключ и возвращает как результат инстас [ClickHouseSyncResponse](./src/Client/Response/ClickHouseSyncResponse.php) (подробнее о Response|SyncResponse в следующем пункте #4). 


4. **Ответ/Response**

   Из любого метода адаптера, кроме метода `ping` мы получаем экземпляр класса [ClickHouseResponse](./src/Client/Response/ClickHouseResponse.php) или же [ClickHouseSyncResponse](./src/Client/Response/ClickHouseSyncResponse.php).
   1. [ClickHouseSyncResponse](./src/Client/Response/ClickHouseSyncResponse.php) - обертка для класса [Statement](./vendor/smi2/phpclickhouse/src/Statement.php), реализующая дополнительный функционал по ожиданию запросов (`ALTER TABLE t DELETE|UPDATE` - в ClickHouse выполняются в background-e, т.е. операция полностью не завершена, но ответ уже пришел).
      Данный класс позволяет вам с помощью методов `await` и `isDone` явно дождаться ответа и фиксации данных.
      1. `await` - ожидает, пока запрос не завершится, залипает в цикле, максимум на 10 минут (_конфигурируется через параметр_). 
      2. `isDone` - единоразовая проверка без залипания, полезно, если ждем фиксацию + выполняем какую-то доп. обработку или т.п..
   2. [ClickHouseResponse](./src/Client/Response/ClickHouseResponse.php) - простая обертка для ответа из ClickHouse, является родителем для ClickHouseSyncResponse (советуется использовать метод `isSyncable` для определения, является ли экземляр сихронизируемым).
      Простой proxy-класс скрывающий реализацию.


6. **Примесь/Trait**

   1. [ClickHouseClientTrait](./src/Traits/ClickHouseClientTrait.php) - единственный функционал, который позволяет получить вышеописанный [Adapter](./src/Client/Adapter/ClickHouseClientAdapter.php) для работы с ClickHouse.

   2. Требования для использования trait-a:
   
      1. Класс, в котором подключается trait, должен имплементировать интерфейс [ClickHouseClientTraitInterface](./src/Traits/Interfaces/ClickHouseClientTraitInterface.php), либо же вы получите соответствующее [исклчюение](./src/Exception/ClickHouseNotImplementedException.php).


6. **Миграция/Migration**
   
   1. Должна имплементировать [ClickHouseMigrationInterface](./src/Migrations/Interfaces/ClickHouseMigrationInterface.php) и 
      использовать вышеописанный trait явно (`$this->clickhouse()->(select|insert|query|ping)(...);`).


7. **Тест/Test**

   1. Если тест нуждается/взаимодействует с ClickHouse, то данный тест нужно унаследовать от [ClickHouseSetUpTestCase](./tests/_support/ClickHouseSetupTestCase.php), 
   далее, использовать по усмотрению, явно через trait или же через репозиторий, который можно получить черзе EntityManager в любом тесте.  

   2. **!!!Важно:** если вы переопределяете методы `setUp` или `tearDown`, нужно вызвать родительский метод (`parent::(tearDown|setUp)();`), либо явно очистить таблицы в тестовой БД в ClickHouse (`$this->clearTables();`). **!!!Важно:** очистка таблиц не произойдет и будет выброшено [исключение](./src/Exception/ClickHouseBadRequestException.php), если вы пытаетесь использовать этот метод не в тестовой среде.

   3. На данный момент для сущностей, которые работают с ClickHouse нельзя создать фикстуры (в дальнейшем, возможно, этот функционал будет реализован). Загрузку данных, можно реализовать явно, создав отдельный метод в тесте.


8. **Исключение/Exception**

   1. [Все исключения](./src/Exception), которые возникают при работе с ClickHouse, имплементируют [ClickHouseExceptionInterface](./src/Exception/Interfaces/ClickHouseExceptionInterface.php) и наследуются от [ClickHouseException](./src/Exception/ClickHouseException.php).
      Соответственно, могут быть перехвачены базовым интефейсом `\Throwable`.


9. **Запрос/Request (only syncable)**
   
   1. В данном кейсе нужно отметить, что если вы попытаетесь отправить запрос типа `Sync`, т.е. указав `isAwaitableQuery` параметр в значение `true` и в переданом SQL-e не будет условия `WHERE`, то получите соотвествующее [исклчюение](./src/Exception/ClickHouseBadRequestException.php) и том, что запросы данного вида не разрешены. Вы должны всегда явно указывать `WHERE {condition}` для запросов типа `Sync`.
