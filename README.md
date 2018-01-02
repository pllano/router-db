# routerDb
routerDb - one interface for different databases
## routerDb — модуль «API Shop» для работы с базами данных
Поддерживаются следующие системы хранения и управления данными через роутер [routerDb\Router](https://github.com/pllano/api-shop/blob/master/app/classes/Database/Router.php):
- работа через API (без своей базы данных) транзитом через клас [ApiDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/ApiDb.php)
- [jsonDB](https://github.com/pllano/json-db) позволяет работать напрямую, транзитом через клас [JsonDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/JsonDb.php)
- jsonapiDb - Вы можете хранить данные в [jsonDB](https://github.com/pllano/json-db) в любом месте (даже на удаленном сервере) и работать с ней через API интерфейс, транзитом через клас [JsonapiDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/JsonapiDb.php)
- MySQL транзитом через клас [MysqlDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/MysqlDb.php)
- Elasticsearch с использованием [Elasticsearch-PHP](https://github.com/elastic/elasticsearch-php) транзитом через клас [ElasticsearchDb](https://github.com/pllano/api-shop/blob/master/app/classes/Database/ElasticsearchDb.php)
- Без особых сложностей возможно написать клас для работы с любой другой базой данных.

### Резервная база данных
API Shop может переключатся между базами данных на лету, если основная база данных недоступна. Для этого необходимо в конфигурации указать названия обоих баз.
```php
// Название основной базы данных. По умолчанию api
$config["db"]["master"] = "api";
// Название резервной базы данных. По умолчанию json
$config["db"]["slave"] = "json"; // Рекомендуется оставить json
```
### Использовать несколько баз данных
API Shop позволяет одновременно работать с любым количеством баз данных. Название базы данных можно задать для каждого ресурса индивидуально. По умолчанию api.

`routerDb\Ping` контролирует состояние баз данных `master` и `slave`. Если база указанная в конфигурации `$resource` недоступна, подключит `master` или `slave` базу.
```php
// Цены получать через API
$config["resource"]["price"]["db"] = "api";
// Данные пользователей хранить в MySQL
$config["resource"]["user"]["db"] = "mysql";
// Свойтва товара хранить в Elasticsearch
$config["resource"]["params"]["db"] = "elasticsearch";
// Локализацию хранить в jsonDB
$config["resource"]["language"]["db"] = "json";
// Платежи хранить в Oracle
$config["resource"]["pay"]["db"] = "oracle";
```
### Встроенный роутер переключения между базами
`routerDb\Router` — роутер подключения к базам данных, дает возможность писать один код для всех баз данных а интеграцию вывести в отдельный класс для каждой базы данных.
```php
use routerDb\Router as Database;
use routerDb\Ping;
 
// Массив с данными
$arr = [
    "limit" => 10,
    "offset" => 0,
    "order" => "DESC",
    "sort" => "created",
    "state" => 1,
    "relations" => base64_encode('{
        "product": ["type_id","brand_id","serie_id","articul"],
        "user": "all",
        "address": "all"
    }')
];
 
// Ресурс к которому обращаемся
$resource = "price";
// Получаем название базы для указанного ресурса
$db_name = new Ping($resource);
// Подключаемся к базе
$db = new Database($db_name);
// Отправляем запрос
$db->get($resource, $arr);
```
Обратите внимание на очень важный параметр запроса [`relations`](https://github.com/pllano/APIS-2018/blob/master/structure/relations.md) позволяющий получать в ответе необходимые данные из других связанных ресурсов.
