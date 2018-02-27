# routerDb
Мы выделили routerDb в отдельный репозиторий потому что его можно использовать независимо от наших других разработок. Работа с несколькими базами данных через один простой интерфейс уже сейчас актуальна для многих проектов.

## Простой и понятный код
```php
use Pllano\RouterDb\Router as RouterDb;

// Таблица (ресурс) к которой обращаемся
$table = "user";
// Отдаем роутеру конфигурацию и название адаптера
// Подключаемся к БД через выбранный Adapter: Pdo, Apis, ZendDb, DoctrineDbal, NetteDb (Default: Pdo)
$routerDb = new RouterDb($config, 'Pdo');
// Пингуем доступную базу данных для ресурса
$db = $routerDb->run($routerDb->ping($table));
// или указываем базу без пинга
// $db = $routerDb->run("mysql");
// Массив для запроса
$query = [];
$id = 1;
// Получить данные пользователя id=1 из базы mysql
$data = $db->get($table, $query, $id);
```
```php
// Тоже самое в одну строчку
$data = ((new \Pllano\RouterDb\Router($config, 'Pdo'))->run("mysql"))->get("user", [], 1);
```
```php
// Или более читабельный код
use Pllano\RouterDb\Router as RouterDb;
// Отдаем роутеру конфигурацию и название адаптера
$routerDb = new RouterDb($config, 'Pdo');

$data = ($routerDb->run("mysql"))->get("user", [], 1);
```
```php
use Pllano\RouterDb\Router as RouterDb;
// Отдаем роутеру конфигурацию и название адаптера
$routerDb = new RouterDb($config, 'Pdo');
// Чтобы подключиться к второй базе mysql_duo необходимо в третьем параметре передать префикс duo 
$db = $routerDb->run('mysql', [], 'duo');
$data = $db->get($table, $query, $id);
```
## Типы запросов
```php
$db->post($table, $query, $field_id);
$db->last_id($table);
$db->get($table, $query, $id, $field_id);
$db->put($table, $query, $id, $field_id);
$db->del($table, $query, $id, $field_id);
$db->count($table, $query, $id, $field_id);

// Exclusive method
$db->pdo($sql)->fetchAll(); // $db->prepare($sql)->execute()->fetchAll();
$db->pdo($sql, $params)->fetchAll(); // $db->prepare($sql)->execute($params)->fetchAll();

// In style PDO
$db->prepare($sql)->execute($params)->fetch();
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// In style Slim-PDO
// https://github.com/FaaPz/Slim-PDO/blob/master/docs/README.md

// SELECT * FROM users WHERE id = ?
$selectStatement = $db->select()
                       ->from('users')
                       ->where('id', '=', 1234);

$stmt = $selectStatement->execute();
$data = $stmt->fetch();

// INSERT INTO users ( id , usr , pwd ) VALUES ( ? , ? , ? )
$insertStatement = $db->insert(['id', 'usr', 'pwd'])
                       ->into('users')
                       ->values([1234, 'your_username', 'your_password']);
$insertId = $insertStatement->execute(false);

// UPDATE users SET pwd = ? WHERE id = ?
$updateStatement = $db->update(['pwd' => 'your_new_password'])
                       ->table('users')
                       ->where('id', '=', 1234);
$affectedRows = $updateStatement->execute();

// DELETE FROM users WHERE id = ?
$deleteStatement = $db->delete()
                       ->from('users')
                       ->where('id', '=', 1234);
$affectedRows = $deleteStatement->execute();
```
```php
public function post(string $resource = null, array $query = [], string $field_id = null): int {}
public function last_id(string $resource = null): int {}
public function get(string $resource = null, array $query = [], int $field_id = null, string $field_id = null): array {}
public function put(string $resource = null, array $query = [], int $field_id = null, string $field_id = null): int {}
public function del(string $resource = null, array $query = [], int $field_id = null, string $field_id = null): int {}
public function count(string $resource = null, array $query = [], int $field_id = null, string $field_id = null): int {}
```
```php
use Pllano\RouterDb\Router as RouterDb;
// Отдаем роутеру конфигурацию и название адаптера
$routerDb = new RouterDb($config, 'Pdo');
$db = $routerDb->run('mysql');
// Поддерживаются запросы напрямую
$data = $db->pdo("SELECT * FROM users WHERE user_id=?",[$user_id])->fetchAll();
// или
$data = $db->prepare($sql)->execute($params)->fetch();
```
```php
// Конфигурация
$config = [
    "db" => [
        "master" => "mysql",
        "slave" => "elasticsearch",
        "mysql" => [
            "host" => "localhost",
            "dbname" => "",
            "port" => "",
            "charset" => "utf8",
            "connect_timeout" => "15",
            "user" => "",
            "password" => ""
        ],
        "mysql_duo" => [
            "host" => "localhost",
            "dbname" => "",
            "port" => "",
            "charset" => "utf8",
            "connect_timeout" => "15",
            "user" => "",
            "password" => ""
        ]
    ],
    "resource" => [
        "user" => [
            "db" => "mysql"
        ],
        "article" => [
            "db" => "elasticsearch"
        ],
        "price" => [
            "db" => "api"
        ]
    ]
];
```
Обратите внимание на очень важный параметр запроса [`relations`](https://github.com/pllano/APIS-2018/blob/master/structure/relations.md) позволяющий получать в ответе необходимые данные из других связанных ресурсов.

Примечание: `relations` чем-то напоминает `JOIN` но не является полным его аналогом !

## Уже скоро еще больше возможностей
Сейчас routerDb поддерживает только запросы по стандарту APIS. Мы планируем расширить возможности и добавить поддержку zend-db, doctrine-dbal 2, Slim-PDO и других. Единственный минус, это не будет быстро. Мы будем дорабатывать библиотеку по мере того как в наших проектах будет необходимость в тех или иных связках. Сейчас мы столкнулись с необходимостью внедрить Zend_DB от Zend Framework 1 и Slim-PDO для Slim 4

## routerDb — Один интерфейс для работы со всеми базами данных
Подключить с помощью [Composer](https://getcomposer.org/)
```diff
"require" {
    ...
-    "pllano/router-db": "1.1.*",
+    "pllano/router-db": "1.2.0",
    ...
}
```
Подключить с помощью [AutoRequire](https://github.com/pllano/auto-require)
```json
"require" [
    {
        "namespace": "Pllano\\RouterDb",
        "dir": "/pllano/router-db/src",
        "link": "https://github.com/pllano/router-db/archive/master.zip",
        "name": "router-db",
        "version": "master",
        "vendor": "pllano"
    }
]
```
## Один код для работы со всеми базами данных
С `routerDb` вы можете писать один код для работы со всеми базами данных `mysql`  `elasticsearch` `json` и даже при работе через `api`
## Один стантарт запросов ко всем базам
Для унификации работы с базами данных используется наш стандарт [APIS-2018](https://github.com/pllano/APIS-2018/) он работает с структурой базы данных [jsonDb](https://github.com/pllano/json-db). Вы можете использовать [jsonDb](https://github.com/pllano/json-db) только для хранения структуры, а также как резервную базу данных, в случае если основная будет недоступна.

### Скоро ! Мы планируем разработать API интерфейс для `routerDb`
В настройках API вам будет достаточно указать какие данные (поля) отдаются с каждого ресурса (таблицы).
#### Зачем ?
- Подключение вашей админ панели к [API Shop](https://github.com/pllano/api-shop).
- Для быстрого запуска RESTful API на вашем сайте, платформе или сервисе.
- Для создания мобильных Android-приложений или приложений для iTunes (экосистема Apple) ! Да-да, множество мобильных приложений для различных сервисов работают при использовании API этих самых сервисов. Вы сделали простенькое мобильное приложение и клиент со смартфоном будет получать информацию в свое устройство именно через API. Это удобно, это разумно, это имеет смысл.
- Максимальное разделение фронтенда и бэкенда. Например, при использовании фронтенд-фреймворков, таких как 
`Angular` `React` `Socket` `Vue` `Ember` `Meteor` `Polymer` `Backbone` `Knockout` `LiquidLava` `dhtmlxSuite` итд.

##### Как это работает ?
```php
// Название класса формируется автоматически
$this->package = $package;
$this->db = $name_db;
Первая буква слова переводится в верхний регистр
$class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
// Результат
// $class = "\YourPackage\Nameclass\NameclassDb";
$db = new $class($config["db"][$name_db]);
 
```


### Глобальная конфигурация

```php
// Название основной базы данных. По умолчанию api
$config["db"]["master"] = "api";
// Название резервной базы данных. Рекомендуем использовать json
$config["db"]["slave"] = "json";
```

### Конфигурация ресурсов
Индивидуально по каждому ресурсу (таблице)
```php
// API Shop позволяет одновременно работать с любым количеством баз данных
// Название базы данных для каждого ресурса. По умолчанию api
 
// Хранилище для ресурса site
$config["db"]["resource"]["site"]["db"] = "api"; // +
// Синхронизировать ресурс site или нет. По умолчанию false
$config["db"]["resource"]["site"]["synchronize"] = false;
 
// Хранилище для ресурса price
$config["db"]["resource"]["price"]["db"] = "api";
// Синхронизировать ресурс price или нет. По умолчанию false
$config["db"]["resource"]["price"]["synchronize"] = false;

// Хранилище для ресурса language
$config["db"]["resource"]["language"]["db"] = "json";
// Синхронизировать ресурс language или нет. По умолчанию false
$config["db"]["resource"]["language"]["synchronize"] = false;
 
// Хранилище для ресурса user
$config["db"]["resource"]["user"]["db"] = "jsonapi";
// Синхронизировать ресурс user или нет. По умолчанию false
$config["db"]["resource"]["user"]["synchronize"] = false;
 
// Хранилище для ресурса cart
$config["db"]["resource"]["cart"]["db"] = "jsonapi";
// Синхронизировать ресурс cart или нет. По умолчанию false
$config["db"]["resource"]["cart"]["synchronize"] = false;
 
// Хранилище для ресурса order
$config["db"]["resource"]["order"]["db"] = "jsonapi";
// Синхронизировать ресурс order или нет. По умолчанию false
$config["db"]["resource"]["order"]["synchronize"] = false;
 
// Хранилище для ресурса address
$config["db"]["resource"]["address"]["db"] = "jsonapi";
// Синхронизировать ресурс address или нет. По умолчанию false
$config["db"]["resource"]["address"]["synchronize"] = false;
 
// Хранилище для ресурса pay
$config["db"]["resource"]["pay"]["db"] = "jsonapi";
// Синхронизировать ресурс pay или нет. По умолчанию false
$config["db"]["resource"]["pay"]["synchronize"] = false;
 
// Хранилище для ресурса product
$config["db"]["resource"]["product"]["db"] = "jsonapi";
// Синхронизировать ресурс product или нет. По умолчанию false
$config["db"]["resource"]["product"]["synchronize"] = false;
 
// Хранилище для ресурса type
$config["db"]["resource"]["type"]["db"] = "jsonapi";
// Синхронизировать ресурс type или нет. По умолчанию false
$config["db"]["resource"]["type"]["synchronize"] = false;
 
// Хранилище для ресурса brand
$config["db"]["resource"]["brand"]["db"] = "jsonapi";
// Синхронизировать ресурс brand или нет. По умолчанию false
$config["db"]["resource"]["brand"]["synchronize"] = false;
 
// Хранилище для ресурса serie
$config["db"]["resource"]["serie"]["db"] = "jsonapi";
// Синхронизировать ресурс serie или нет. По умолчанию false
$config["db"]["resource"]["serie"]["synchronize"] = false;
 
// Хранилище для ресурса images
$config["db"]["resource"]["images"]["db"] = "jsonapi";
// Синхронизировать ресурс images или нет. По умолчанию false
$config["db"]["resource"]["images"]["synchronize"] = false;
 
// Хранилище для ресурса seo
$config["db"]["resource"]["seo"]["db"] = "jsonapi";
// Синхронизировать ресурс seo или нет. По умолчанию false
$config["db"]["resource"]["seo"]["synchronize"] = false;
 
// Хранилище для ресурса description
$config["db"]["resource"]["description"]["db"] = "jsonapi";
// Синхронизировать ресурс description или нет. По умолчанию false
$config["db"]["resource"]["description"]["synchronize"] = false;
 
// Хранилище для ресурса params
$config["db"]["resource"]["params"]["db"] = "jsonapi";
// Синхронизировать ресурс params или нет. По умолчанию false
$config["db"]["resource"]["params"]["synchronize"] = false;
 
// Хранилище для ресурса contact
$config["db"]["resource"]["contact"]["db"] = "jsonapi";
// Синхронизировать ресурс contact или нет. По умолчанию false
$config["db"]["resource"]["contact"]["synchronize"] = false;
 
// Хранилище для ресурса category
$config["db"]["resource"]["category"]["db"] = "jsonapi";
// Синхронизировать ресурс category или нет. По умолчанию false
$config["db"]["resource"]["category"]["synchronize"] = false;
 
// Хранилище для ресурса role
$config["db"]["resource"]["role"]["db"] = "jsonapi";
// Синхронизировать ресурс role или нет. По умолчанию false
$config["db"]["resource"]["role"]["synchronize"] = false;
 
// Хранилище для ресурса currency
$config["db"]["resource"]["currency"]["db"] = "jsonapi";
// Синхронизировать ресурс currency или нет. По умолчанию false
$config["db"]["resource"]["currency"]["synchronize"] = false;
 
// Хранилище для ресурса article
$config["db"]["resource"]["article"]["db"] = "mysql";
// Синхронизировать ресурс article или нет. По умолчанию false
$config["db"]["resource"]["article"]["synchronize"] = false;
 
// Хранилище для ресурса article_category
$config["db"]["resource"]["article_category"]["db"] = "mysql";
// Синхронизировать ресурс article_category или нет. По умолчанию false
$config["db"]["resource"]["article_category"]["synchronize"] = false;
```

### Конфигурация баз данных
#### [jsonDb](https://github.com/pllano/json-db)
Настройки подключения к [jsonDb](https://github.com/pllano/json-db) которая идет в комплекте по умолчанию и выступает как резервная база данных.
```php
// Директория для хранения файлов json базы данных.
$config["db"]["json"]["dir"] = __DIR__ . "/../../json-db/db/";
// Кеширование запросов
$config["db"]["json"]["cached"] = false; // true|false
// Время жизни кеша
$config["db"]["json"]["cache_lifetime"] = 60;
// Очередь на запись
$config["db"]["json"]["temp"] = false;
// Работает через API
$config["db"]["json"]["api"] = false;
// Шифруем базу
$config["db"]["json"]["crypt"] = false;
```
Настройки подключения к [jsonDb](https://github.com/pllano/json-db) через [API](https://github.com/pllano/json-db/tree/master/api)
```php
// URL API jsondb
$config["db"]["jsonapi"]["url"] = "https://xti.com.ua/json-db/";
// Доступные методы аутентификации: null, CryptoAuth, QueryKeyAuth, HttpTokenAuth, LoginPasswordAuth
$config["db"]["jsonapi"]["auth"] = null;
// Публичный ключ аутентификации
$config["db"]["jsonapi"]["public_key"] = "";
// Приватный ключ шифрования
$config["db"]["jsonapi"]["private_key"] = "";
```
Настройки подключения к RESTful API
```php
// Если работает через API будет брать часть конфигурации из api
$config["db"]["api"]["config"] = true; // true|false
// URL API
$config["db"]["api"]["url"] = "";
// Доступные методы аутентификации: CryptoAuth, QueryKeyAuth, HttpTokenAuth, LoginPasswordAuth
$config["db"]["api"]["auth"] = "QueryKeyAuth";
// Публичный ключ аутентификации
$config["db"]["api"]["public_key"] = "";
// Приватный ключ шифрования
$config["db"]["api"]["private_key"] = "";
```
Настройки подключения к базе MySQL
```php
$config["db"]["mysql"]["host"] = "localhost";
$config["db"]["mysql"]["dbname"] = "";
$config["db"]["mysql"]["port"] = "";
$config["db"]["mysql"]["charset"] = "utf8";
$config["db"]["mysql"]["connect_timeout"] = 15;
$config["db"]["mysql"]["user"] = "";
$config["db"]["mysql"]["password"] = "";
```
#### Elasticsearch
На вашем сервере должен быть установлен и настроен [Elasticsearch](https://www.elastic.co/downloads/elasticsearch)

Дальше вы можете подключить Elasticsearch PHP с помощью Composer
```php
"require": {
    "elasticsearch/elasticsearch": "~6.0"
}
```
Настройки подключения к Elasticsearch
```php
// По умолчанию http://localhost:9200/
$config["db"]["elasticsearch"]["host"] = "localhost";
$config["db"]["elasticsearch"]["port"] = 9200;
// Учитывая то что в следующих версиях Elasticsearch не будет type
// вы можете отключить type поставив false
// в этом случае index будет формироватся так index_type
$config["db"]["elasticsearch"]["type"] = true; // true|false
$config["db"]["elasticsearch"]["index"] = "elastic";
// Если подключение к elasticsearch требует логин и пароль установите auth=true
$config["db"]["elasticsearch"]["auth"] = false; // true|false
$config["db"]["elasticsearch"]["user"] = "elastic";
$config["db"]["elasticsearch"]["password"] = "elastic_password";
```
<a name="feedback"></a>
## Поддержка, обратная связь, новости

Общайтесь с нами через почту open.source@pllano.com

Если вы нашли баг в API json DB загляните в [issues](https://github.com/pllano/router-db/issues), возможно, про него мы уже знаем и
постараемся исправить в ближайшем будущем. Если нет, лучше всего сообщить о нём там. Там же вы можете оставлять свои пожелания и предложения.

За новостями вы можете следить по
[коммитам](https://github.com/pllano/router-db/commits/master) в этом репозитории.
[RSS](https://github.com/pllano/router-db/commits/master.atom).

Лицензия
-------

The MIT License (MIT). Please see [LICENSE](https://github.com/pllano/router-db/blob/master/LICENSE) for more information.
