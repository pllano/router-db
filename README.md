# routerDb
routerDb - One simple interface for working with any number of databases at the same time
## routerDb — Один интерфейс для работы с любым количеством баз данных

Подключить с помощью Composer
```php
"require": {
    "pllano/router-db": "~1.0.1"
}
```
### Структура базы данных
По умолчанию используеться структура баз данных [db.json](https://github.com/pllano/db.json), вы можете настроить свою структуру для каждого ресурса (таблицы).
### Один стантарт запросов ко всем базам
Для унификации работы с базами данных используется наш стандарт [APIS-2018](https://github.com/pllano/APIS-2018/) он работает с структурой базы данных [jsonDb](https://github.com/pllano/json-db). Вы можете использовать [jsonDb](https://github.com/pllano/json-db) только для хранения структуры, а также как резервную базу данных, в случае если основная будет недоступна.
### Использовать несколько баз данных
`routerDb` — позволяет одновременно работать с любым количеством баз данных и писать один код для всех баз данных, а всю интеграцию вывести в отдельный класс для каждой базы данных.
### Переключатся между базами данных на лету
`routerDb` — Имеет встроенный роутер переключения между базами, он может переключатся между базами данных на лету, если основная база данных недоступна. Для этого необходимо в конфигурации указать названия обоих баз. Он контролирует состояние баз данных `master` и `slave`. Если база указанная в конфигурации `$resource` недоступна, подключит `master` или `slave` базу.
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
    "state" => 1
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
#### Получение данных `GET` по `id`
```php
$id = 1;
// Вернет данные пользователя с указанным id
$response = $db->get($resource, [], $id);
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
#### Удаление `DELETE`
```php
// id записи
$id = 1;
$response = $db->delete($resource, [], $id);

// Вернет массив
$code = $response["headers"]["code"]; // 200 или другой в зависимости от ошибки
$count = $response["response"]["total"]; // колличество удаленных записей, или null при ошибке
```

#### Поддерживает сторонние пакеты
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
    }
 
    public function put($resource = null, array $arr = array(), $id = null)
    {
        // Обновление одной или нескольких записей
    }
 
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        // Удаление одной или нескольких записей
    }
 
    public function search($resource = null, array $arr = array(), $search = null)
    {
        // Новый запрос, аналог get рассчитан на полнотекстовый поиск
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
    }
 
}
```
#### NamedatabasePing.php - Пингует доступность базы данных
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
// Название резервной базы данных. По умолчанию jsonapi
$config["db"]["slave"] = "json";
```

### Конфигурация ресурсов
Индивидуально по каждому ресурсу (таблице)
```php
// Цены получать через API
$config["resource"]["price"]["db"] = "api";
// Там где нужен поиск храним в Elasticsearch
$config["resource"]["params"]["db"] = "elasticsearch";
$config["resource"]["product"]["db"] = "elasticsearch";
$config["resource"]["type"]["db"] = "elasticsearch";
$config["resource"]["brand"]["db"] = "elasticsearch";
$config["resource"]["serie"]["db"] = "elasticsearch";
$config["resource"]["article"]["db"] = "elasticsearch";
$config["resource"]["article_category"]["db"] = "elasticsearch";
// Локализацию и валюты получать от jsonapi
$config["resource"]["language"]["db"] = "jsonapi";
$config["resource"]["currency"]["db"] = "jsonapi";
$config["resource"]["category"]["db"] = "jsonapi";
// Платежи хранить в Oracle
$config["resource"]["pay"]["db"] = "oracle";
// Другие данные хранить в MySQL
$config["resource"]["user"]["db"] = "mysql";
$config["resource"]["site"]["db"] = "mysql";
$config["resource"]["user"]["db"] = "mysql";
$config["resource"]["cart"]["db"] = "mysql";
$config["resource"]["order"]["db"] = "mysql";
$config["resource"]["address"]["db"] = "mysql";
$config["resource"]["images"]["db"] = "mysql";
$config["resource"]["seo"]["db"] = "mysql";
$config["resource"]["description"]["db"] = "mysql";
$config["resource"]["contact"]["db"] = "mysql";
$config["resource"]["role"]["db"] = "mysql";
```
### Конфигурация баз данных
#### jsonDb
Подключить с помощью Composer
```php
"require": {
    "pllano/json-db": "^1.0.5"
}
```
Настройки подключения к jsonDb напрямую
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
Настройки подключения к jsondb через API
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
#### Elasticsearch PHP
Подключить с помощью Composer
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
 
