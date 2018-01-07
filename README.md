# routerDb
routerDb - One simple interface for working with any number of databases at the same time
## routerDb — Один интерфейс для работы со всеми базами данных

Подключить с помощью Composer
```php
"require": {
    "pllano/router-db": "1.0.2"
}
```
## Что умеет `routerDb` ?
### Один код для работы со всеми базами данных
С `routerDb` вы можете писать один код для работы со всеми базами данных `mysql`  `elasticsearch` `json` и даже при работе через `api`
### Один стантарт запросов ко всем базам
Для унификации работы с базами данных используется наш стандарт [APIS-2018](https://github.com/pllano/APIS-2018/) он работает с структурой базы данных [jsonDb](https://github.com/pllano/json-db). Вы можете использовать [jsonDb](https://github.com/pllano/json-db) только для хранения структуры, а также как резервную базу данных, в случае если основная будет недоступна.
### Использовать несколько баз данных одновременно
Позволяет одновременно работать с любым количеством баз данных
### Переключатся между базами данных на лету
Имеет встроенный роутер переключения между базами данных на лету. Если база данных ресурса (указанная в конфигурации `$resource` ) недоступна, `routerDb` переключит на `master` или `slave` базу.
### Писать запросы в `slave` базу когда основная недоступна
Если при запросах `POST` `PUT` `PATCH` `DELETE` использовалась не основная база данных ресурса указанная в конфигурации, `routerDb\Db` запишет запрос в резервную базу `slave` и таблицу запросов `queue.json`
### Синхронизировать данные между `master` и `slave`
Как только основная база `master` ресурса снова станет доступна, `routerDb\Queue` синхронизирует данные в базу указанную в конфигурации `$resource` или `master` если нет.
### Управление структурой баз данных
По умолчанию используеться структура баз данных из файла [db.json](https://github.com/pllano/db.json/blob/master/db.json), вы можете настроить свою структуру для каждой таблицы (ресурса) отредактировав файл db.json и скопировав его в директорию базы данных jsonDb `/../_json_db_/_db_/core/`
### Скоро ! Мы планируем разработать API интерфейс для `routerDb`
В настройках API вам будет достаточно указать какие данные (поля) отдаются с каждого ресурса (таблицы).
#### Зачем ?
- Для быстрого запуска RESTful API на вашем сайте, платформе или сервисе.
- Для создания мобильных Android-приложений или приложений для iTunes (экосистема Apple) ! Да-да, множество мобильных приложений для различных сервисов работают при использовании API этих самых сервисов. Вы сделали простенькое мобильное приложение и клиент со смартфоном будет получать информацию в свое устройство именно через API. Это удобно, это разумно, это имеет смысл.
- Максимальное разделение фронтенда и бэкенда. Например, при использовании фронтенд-фреймворков, таких как 
`Angular` `React` `Socket` `Vue` `Ember` `Meteor` `Polymer` `Backbone` `Knockout` `LiquidLava` `dhtmlxSuite` итд.

#### Минимум кода
```php
// Получить данные пользователя id=1 из базы mysql одной строчкой кода
$user = (new \RouterDb\Db("mysql", $config))->get("user", [], 1);

// Получить расширенные данные пользователя id=1, дополнительно запрашиваем адрес и корзину
$user = (new \RouterDb\Db("mysql", $config))->get("user", ["relation" => "address,cart"], 1);
```
Обратите внимание на очень важный параметр запроса [`relations`](https://github.com/pllano/APIS-2018/blob/master/structure/relations.md) позволяющий получать в ответе необходимые данные из других связанных ресурсов.

Примечание: `relations` чем-то напоминает `JOIN` но не является полным его аналогом !

#### Общий код для всех примеров
```php
use RouterDb\Db;
use RouterDb\Router;

// Ресурс (таблица) к которому обращаемся
$resource = "user";
// Отдаем роутеру RouterDb конфигурацию.
$router = new Router($config);
// Получаем название базы для указанного ресурса
$name_db = $router->ping($resource);
// Подключаемся к базе
$db = new Db($name_db, $config);
```

#### Получение данных `GET`
```php
// Массив с данными запроса
$getArr = [
    "limit" => 5,
    "offset" => 0,
    "order" => "DESC",
    "sort" => "created",
    "state" => 1,
    "iname" => "Alex"
];

$response = $db->get($resource, $getArr);

// Вернет массив
$code = $response["headers"]["code"]; // 200 или другой в зависимости от ошибки
$count = $response["response"]["total"]; // общее колличество записей соответствующих запросу
$items = $response["body"]["items"]; // массив с данными

foreach($items as $value)
{
  $id = items["item"]["id"];
  $iname = items["item"]["iname"];
}

```
Поддерживается следующие параметры в запросе:
- `limit` - лимит записей на страницу
- `offset` - смещение (с какой записи выводим)
- `order` - сотрировка DESC или ASC
- `sort` - поле по которому сортируем
- `field` - любое поле которое есть в ресурсе. Параметров `field` может быть несколько

#### Получение данных `GET` по `id`
```php
$id = 1;
// Вернет данные пользователя с указанным id
$response = $db->get($resource, [], $id);

// Вернет массив
$code = $response["headers"]["code"]; // 200 или другой в зависимости от ошибки
$count = $response["response"]["total"]; // общее колличество записей соответствующих запросу
$item = $response["body"]["items"]["0"]["item"]; // Данные пользователя

$role_id = $item["role_id"];
$email = $item["email"];
$phone = $item["phone"];
$iname = $item["iname"];
$fname = $item["fname"];
```
#### Поиск `GET` в режиме `search`
Добавлен новый вид GET запроса для полнотекстового поиска.

Не поддерживает параметр `relations` и получение данных по `id`

Потдерживаются параметры
- `query_fields` - Через запятую поля, по которым нужен поиск. По умолчанию все поля с типом `string`
- `search_type` - Тип поиска (В разработке !)

```php
// Ресурс
$resource = "product";
// Параметры поиска
$query_arr = [
    "query_fields" => "iname,fname,oname,text",
];
// Ключевое слово
$keyword = "anna";
 
$response = $db->search($resource, $query_arr, $keyword);
 
// Вернет массив
$code = $response["headers"]["code"]; // 200 или другой в зависимости от ошибки
$count = $response["response"]["total"]; // колличество найденых записей, или null при ошибке
$items = $response["body"]["items"]; // массив с данными
 
foreach($items as $value)
{
  $id = items["item"]["id"];
  $iname = items["item"]["iname"];
}

```
Самый простой пример поискового запроса
```php
$response = $db->search("product", [], "laptops");
```
#### Создание `POST`
```php
// Массив с данными запроса
$postArr["role"] = 1;
$postArr["name"] = "Admin";
$postArr["email"] = "admin@example.com";
 
$response = $db->post($resource, $postArr);
 
// Вернет массив
$code = $response["headers"]["code"]; // 201 или другой в зависимости от ошибки
$count = $response["response"]["total"]; // 1 если все ок, или null при ошибке
$id = $response["response"]["id"]; // id новой записи, или null при ошибке
```
Поддерживается один метод создания:
- Создание одной записи с передачей данных в параметрах
```php
$response = $db->post($resource, ["name" => "Alex"]);
```
#### Обновление `PUT`
```php
// id записи
$id = 1;
// Массив с данными запроса
$putArr["name"] = "Admin2";
$putArr["email"] = "admin2@example.com";
 
$response = $db->put($resource, $putArr, $id);
 
// Вернет массив
$code = $response["headers"]["code"]; // 202 или другой в зависимости от ошибки
$count = $response["response"]["total"]; // колличество обновленных записей, или null при ошибке
```
Поддерживается несколько методов обновления:
- Обновление одной записи по id с параметрами
```php
$response = $db->put($resource, ["name" => "Alex"], $id);
```
- Обновление нескольких записей по id который передается с параметрами
```php
$response = $db->put($resource, [
    ["id" => 10, "name" => "Alex"],
    ["id" => 11, "name" => "Viktor"]
]);
```
#### Удаление `DELETE`
```php
// id записи
$id = 1;
$response = $db->delete($resource, [], $id);

// Вернет массив
$code = $response["headers"]["code"]; // 200 или другой в зависимости от ошибки
$count = $response["response"]["total"]; // колличество удаленных записей, или null при ошибке
```
Поддерживается несколько методов удаления:
- Удаление одной записи по id
```php
$response = $db->delete($resource, [], $id);
```
- Удаление все записей в которых совпали параметры
```php
// Один параметр
$response = $db->delete($resource, ["name" => "Alex"]);
// При совпадении нескольких параметров
$response = $db->delete($resource, [["name" => "Alex", "language" => "ru"]]);
```
- Удаление всех записей
```php
$response = $db->delete($resource);
```
## Поддерживает сторонние пакеты
```php
// Вы должны подключить пакет
require __DIR__ . '/NamedatabaseDb.php';
require __DIR__ . '/NamedatabasePing.php';
```
#### NamedatabaseDb.php - Обрабатывает запросы к базе данных
Структура запросов и ответов описана в стандарте [APIS-2018](https://github.com/pllano/APIS-2018/) только возвращать необходимо массив PHP
```php
namespace YourPackage\Namedatabase;
 
class NamedatabaseDb
{
 
    private $config;
 
    public function __construct(array $config = array())
    {
        // Получить и установить конфигурацию
        if (count($config) >= 1){
            $this->config = $config;
        }
    }
    
    public function get($resource = null, array $arr = array(), $id = null)
    {
        // Получение данных
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
    }
 
    public function post($resource = null, array $arr = array())
    {
        // Создание одной записи
        // Должен возвращать id новой записи в параметре ["response"]["id"]
    }
 
    public function put($resource = null, array $arr = array(), $id = null)
    {
        // Обновление одной или нескольких записей
        // Должен возвращать колличество измененных записей в параметре ["response"]["total"]
    }
 
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        // Удаление одной или нескольких записей
        // Должен возвращать колличество удаленных записей в параметре ["response"]["total"]
    }
 
    public function search($resource = null, array $arr = array(), $search = null)
    {
        // Новый запрос, аналог get рассчитан на полнотекстовый поиск
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
    }
 
    public function last_id($resource)
    {
        // Должен возвращать последний идентификатор без параметров
    }
 
}
```
#### NamedatabasePing.php - Пингует доступность ресурса в базе данных
```php
namespace YourPackage\Namedatabase;

use RouterDb\Ex;
 
class NamedatabasePing
{
 
    private $config;
 
    public function __construct(array $config = array())
    {
        if (count($config) >= 1){
            $this->config = $config;
        }
    }
 
    public function ping($resource = null)
    {
        if ($resource != null) {
            try {
                // Здесь должен быть код для проверки доступности таблицы (ресурса) в базе данных
                // Должен возвращать название базы namedatabase или null если база недоступна
                
                // Этот код работает без проверки доступности базы
                $response = $resource;
 
                return $response;
 
            } catch (Ex $ex) {
                return null;
            }
        } else {
            return null;
        }
    }
 
}
```
#### Использование пакета
```php
use RouterDb\Db;
use RouterDb\Router;

// В конфигурации необходимо указать название базы данных для ресурса
$config["resource"]["user"]["db"] = "namedatabase";
// Подключить ваш пакет
$package = "\YourPackage\\";

// Ресурс или таблица к которой обращаемся
$resource = "user";
// Отдаем роутеру RouterDb конфигурацию и название пакета.
$router = new Router($config, $package);
// Получаем название базы для указанного ресурса
$name_db = $router->get($resource); // Вернет namedatabase если база доступна
// Подключаем базу
$db = new Db($name_db, $config, $package);
// Отправить запрос
$response = $db->get($resource, [], 1);
```
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

### Базы данных
Поддерживаются следующие системы хранения и управления данными через роутер [routerDb\Router](https://github.com/pllano/api-shop/blob/master/app/classes/Database/Router.php):
- работа через API транзитом через клас [ApiDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/ApiDb.php)
- позволяет работать напрямую с [jsonDB](https://github.com/pllano/json-db) транзитом через клас [JsonDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/JsonDb.php)
- jsonapiDb - Вы можете хранить данные в [jsonDB](https://github.com/pllano/json-db) в любом месте (даже на удаленном сервере) и работать с ней через API интерфейс, транзитом через клас [JsonapiDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/JsonapiDb.php)
- позволяет работать напрямую с MySQL транзитом через клас [MysqlDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/MysqlDb.php)
- позволяет работать с Elasticsearch с использованием [Elasticsearch-PHP](https://github.com/elastic/elasticsearch-php) транзитом через клас [ElasticsearchDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/ElasticsearchDb.php)
- Без особых сложностей возможно написать клас для работы с любой другой базой данных.
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
#### На будущее, заложена возможность расширять индивидуальную конфигурацию каждого ресурса.
Например:
```php
// Сейчас используется только два параметра для каждого ресурса
$config["db"]["resource"]["user"]["db"] = "mysql";
$config["db"]["resource"]["user"]["synchronize"] = false;
```
В будущем мы хотим дать возможность:
```php
// Подключится к другой базе mysql
$config["db"]["resource"]["user"]["mysql"]["host"] = "localhost";
$config["db"]["resource"]["user"]["mysql"]["dbname"] = "";
$config["db"]["resource"]["user"]["mysql"]["port"] = "";
$config["db"]["resource"]["user"]["mysql"]["charset"] = "utf8";
$config["db"]["resource"]["user"]["mysql"]["connect_timeout"] = 30;
$config["db"]["resource"]["user"]["mysql"]["user"] = "";
$config["db"]["resource"]["user"]["mysql"]["password"] = "";
 
// Включать кеширование для каждого ресурса индивидуально
$config["db"]["resource"]["user"]["cached"] = true;
 
// Включать шифрование для каждого ресурса индивидуально
$config["db"]["resource"]["user"]["crypt"] = true;
$config["db"]["resource"]["user"]["key"] = true;
 
// Отдавать в ответе только указанные поля
$config["db"]["resource"]["user"]["fields"] = "phone,email,iname,fname";
 
// Запретить или разрешить отдавать связанные данные из других ресурсов через параметр relations
$config["db"]["resource"]["user"]["relations"] = false;
 
// Запретить переключатся на slave базу
$config["db"]["resource"]["user"]["db"]["slave"] = false;
// Или указать slave базу индивидуально для этого ресурса
$config["db"]["resource"]["user"]["db"]["slave"] = "api";
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
