# routerDb
## One interface for working with all databases
## Simple and clear code
```php
use Pllano\RouterDb\Router as RouterDb;

// Table (resource)
$table = "user";
// Adapter: Pdo, Apis, ZendDb, DoctrineDbal, NetteDb (Default: Pdo)
$routerDb = new RouterDb($config, 'Pdo');
// Ping the available database for the resource
$db = $routerDb->run($routerDb->ping($table));
// Or indicate the base, without ping
// $db = $routerDb->run("mysql");

// Array for the query
$query = [];
$id = 1;
// Get user data id = 1 from mysql database
$data = $db->get($table, $query, $id);
```
```php
// The same in one line
$data = ((new \Pllano\RouterDb\Router($config, 'Pdo'))->run("mysql"))->get("user", [], 1);
```
```php
// More readable code
use Pllano\RouterDb\Router as RouterDb;
$routerDb = new RouterDb($config, 'Pdo');
$data = ($routerDb->run("mysql"))->get("user", [], 1);
```
```php
use Pllano\RouterDb\Router as RouterDb;
$routerDb = new RouterDb($config, 'Pdo');
// To connect to the second mysql_duo database, you need to pass in the third parameter the prefix duo
$db = $routerDb->run('mysql', [], 'duo');
$data = $db->get($table, $query, $id);
```
## Types of requests
```php
$post = $db->post($table, $query, $field_id);
$get = $db->get($table, $query, $id, $field_id);
$put = $db->put($table, $query, $id, $field_id);
$del = $db->del($table, $query, $id, $field_id);
$count = $db->count($table, $query, $id, $field_id);
$last_id = $db->last_id($table);

// Exclusive method
$data = $db->pdo($sql)->fetchAll(); // $db->prepare($sql)->execute()->fetchAll();
$data = $db->pdo($sql, $params)->fetchAll(); // $db->prepare($sql)->execute($params)->fetchAll();

// In style PDO
$data = $db->prepare($sql)->execute($params)->fetch();
$data = $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

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
$routerDb = new RouterDb($config, 'Pdo');
$db = $routerDb->run('mysql');
$data = $db->pdo("SELECT * FROM users WHERE user_id=?",[$user_id])->fetchAll();
// or
$data = $db->prepare($sql)->execute($params)->fetch();
```
```php
// Configuration
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
## Protection against SQL injections
### function search_injections()
Very simple function
``` php
public function search_injections(string $value = null, array $new_keywords = []): int
{
    if (isset($value)) {
        if (isset($new_keywords)) {
            $list_keywords = $new_keywords;
        } else {
            $list_keywords = [
            '*', 
            'SELECT', 
            'UPDATE', 
            'DELETE', 
            'INSERT', 
            'INTO', 
            'VALUES', 
            'FROM', 
            'LEFT', 
            'JOIN', 
            'WHERE', 
            'LIMIT', 
            'ORDER BY', 
            'AND', 
            'OR ',
            'DESC', 
            'ASC', 
            'ON',
            'LOAD_FILE', 
            'GROUP',
            'BY',
            'foreach',
            'echo',
            'script',
            'javascript',
            'public',
            'function',
            'secret',
            'admin',
            'root',
            'password',
            'push',
            '"false"',
            '"true"',
            'return',
            'onclick'
            ];
        }
        $value = str_ireplace($list_keywords, "👌", $value, $i);
        return $i;
    } else {
        return 0;
    }
}
```
### Example injection
An SQL injection against which prepared statements won't help
```html
<form method=POST>
<input type=hidden name="name=(SELECT'hacked!')WHERE`id`=1#" value="">
<input type=submit>
</form>
```
### Method 1 (Can help in 99% of cases.)
Use only where it is very necessary !
Check the existence of the key in the table
```php
use Pllano\RouterDb\Utility;
use Pllano\RouterDb\Router as RouterDb;

$utility = new Utility();
$routerDb = new RouterDb($config, 'Pdo');
$db = $routerDb->run('mysql');

$params = [];
$setStr = "";
$x = 2; // If search_injections finds $x keywords from the list
$table_schema = ["id", "name", "user_id", "surname", "email"];

foreach ($_POST as $key => $value)
{
    if (array_key_exists($key, $table_schema)) {
        if ($utility->search_injections($value) >= $x) {
            // Write to the log. A letter to the administrator.
            return 'injection'; // Stop Execution
        } else {
            if ($key != "id") {
                $setStr .= "`".str_replace("`", "``", $key)."` = :".$key.","; 
            }
            $params[$key] = $value;
        }
    } else {
        if ($utility->search_injections($key) >= 1 || $utility->search_injections($value) >= 1) {
            // Write to the log. A letter to the administrator.
            return 'injection'; // Stop Execution
        }
    }
}

if (isset($_POST['id'])) {
    $params['id'] = intval($_POST['id']);
}
$setStr = rtrim($setStr, ",");
$data = $db->prepare("UPDATE users SET $setStr WHERE id = :id")->execute($params);
```
## Installation
Use [Composer](https://getcomposer.org/)
```diff
"require" {
    ...
-    "pllano/router-db": "1.1.*",
+    "pllano/router-db": "1.2.0",
    ...
}
```
Use [AutoRequire](https://github.com/pllano/auto-require)
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
<a name="feedback"></a>
## Support, feedback, news
Contact: open.source@pllano.com

- [issues](https://github.com/pllano/router-db/issues) 
- [Commits](https://github.com/pllano/router-db/commits/master) 
- [RSS](https://github.com/pllano/router-db/commits/master.atom)

License
-------
The MIT License (MIT). Please see [LICENSE](https://github.com/pllano/router-db/blob/master/LICENSE) for more information.
