<?php
/**
 * RouterDb (https://pllano.com)
 *
 * @link https://github.com/pllano/router-db
 * @version 1.2.0
 * @copyright Copyright (c) 2017-2018 PLLANO
 * @license http://opensource.org/licenses/MIT (MIT License)
 */
namespace Pllano\RouterDb;

use Pllano\RouterDb\Interfaces\PdoInterface;
use Pllano\RouterDb\Interfaces\ApisInterface;
use Pllano\RouterDb\Router as RouterDb;
use Slim\PDO\Database;
use PDO;

class Mysql extends AdapterDb implements PdoInterface, ApisInterface
{

    /**
     * Data
     *
     * @var array
     * @access protected
    */
    protected $_data = [];

    public function __construct(array $config = [], array $options = [], $prefix = null)
    {
        if (isset($config)) {
            if (isset($prefix)) {
                $db = $config['db']['mysql_'.$prefix];
            } else {
                $db = $config['db']['mysql'];
            }
            $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
            $user = $db['user'];
            $password = $db['password'];
            $default_options = [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];
            $options = array_replace($default_options, $options);
            return new Database($dsn, $user, $password, $options);
        }
    }

    public function ping(string $resource = null)
    {
        return 'mysql';
    }

    public function apis(array $arr = [], string $type = null)
    {
        return null;
    }

    public function pdo($sql, $args = null)
    {
        if (!$args) {
             return $this->query($sql);
        }
        $stmt = $this->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }

    // Загрузить
    public function get(string $resource = null, array $arr = [], int $id = null, $field_id = null): array
    {
        $this->resource = $resource;
        if ($resource != null) {
            if ($field_id == null) {
                $show = null;
                $resource_id = "id";
                $this->resource_id = "id";
                $show = $this->query("SHOW COLUMNS FROM `".$resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show == "id") {
                    $this->resource_id = "id";
                    $resource_id = "id";
                    $this->sort = "id"; 
                } else {
                    $show = $this->query("SHOW COLUMNS FROM `".$resource."` where `Field` = '".$resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                    if ($show == $resource."_id") {
                        $this->resource_id = $resource."_id";
                        $resource_id = $resource."_id";
                        $this->sort = $resource."_id";
                    }
                }
            } else {
                $this->resource_id = $field_id;
                $resource_id = $field_id;
                $this->sort = $field_id;
            }
        }
        
        $count = $this->count($resource, $arr, $id, $resource_id);
        // print_r($count["0"]["COUNT(*)"]);
        
        
        if ($id >= 1) {
            if (count($arr) >= 1) {
                foreach($arr as $key => $value)
                {
                    if ($key == "relations") {
                        $this->relations = $value;
                    }
                }
            }
            // Формируем запрос к базе данных
            $sql = "SELECT * FROM  `".$resource."` WHERE  `".$resource_id."` ='".$id."' LIMIT 1";
        } else {
            $query = "";
            if (count($arr) >= 1) {
                foreach($arr as $key => $value)
                {
                    if ($key == ""){$key = null;}
                    if (isset($key) && isset($value)) {
                        if ($key == "sort") {
                            if ($value == "uid" || $value == "id" || $value == $resource."_id") {
                                $this->sort = $resource_id;
                            } else {
                                $this->sort = $value;
                            }
                        } elseif ($key == "order") {
                            $this->order = $value;
                        } elseif ($key == "offset") {
                            $this->offset = $value;
                        } elseif ($key == "limit") {
                            $this->limit = $value;
                        } elseif ($key == "relations") {
                            $this->relations = $value;
                        } else {
                            if ($this->key_null == $key || $this->key_null == null) {
                                $query .= "WHERE `".$key."` ='".$value."' ";
                                $this->key_null = $key;
                                    $resp["request"][$key] = $value;
                            } else {
                                if (is_int($value)) {
                                    $query .= "AND `".$key."` ='".$value."' ";
                                } else {
                                    $query .= "AND `".$key."` LIKE '%".$value."%' ";
                                }
                                    $resp["request"][$key] = $value;
                            }
                        }
                    }
                }
            }
            
            if($this->offset >= 1){
                $this->offset = $this->offset * $this->limit;
            }
            // Формируем запрос к базе данных
            $sql = "
                SELECT * 
                FROM `".$resource."` 
                ".$query." 
                ORDER BY `".$this->sort."` ".$this->order." 
                LIMIT ".$this->offset." , ".$this->limit." 
            ";
            
            //print_r($sql);
        }
        // Отправляем запрос в базу
        $stmt = $this->prepare($sql);
        if ($stmt->execute()) {
            // Ответ будет массивом
            $resp = [];
            // Получаем ответ в виде массива
            $resp = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Если ничего не нашли отдаем 404
            $resp = null;
        }
            return $resp;
 
    }

    // Создаем одну запись
    public function post(string $resource = null, array $arr = [], string $field_id = null): int
    {
        if ($field_id == null) {
            $show = null;
            $resource_id = "id";
            $this->resource_id = "id";
            $show = $this->query("SHOW COLUMNS FROM `".$resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
            if ($show == "id") {
                $this->resource_id = "id";
                $resource_id = "id";
            } else {
                $show = $this->query("SHOW COLUMNS FROM `".$resource."` where `Field` = '".$resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show == $resource."_id") {
                    $this->resource_id = $resource."_id";
                    $resource_id = $resource."_id";
                }
            }
        } else {
                $this->resource_id = $field_id;
                $resource_id = $field_id;
        }

        // Задаем пустые значения чтобы не выдавало ошибок
        $insert = "";
        $values = "";
        if (count($insert) >= 1) {
            foreach($arr as $key => $unit)
            {
                if ($key == ""){$key = null;}
                if (isset($key) && isset($unit)) {
                    $insert .= ", `".$key."`";
                    $values .= ", '".$unit."'";
                }
            }
        }
        if ($resource != null) {
            // Формируем запрос к базе данных
            $sql = "INSERT INTO `".$resource."` (`".$resource_id."`".$insert.") VALUES ('NULL'".$values.");";
            // Отправляем запрос в базу
            $stmt = $this->prepare($sql);
            if ($stmt->execute()) {
                // Если все ок отдаем id
                $resp = $this->lastInsertId();
            } else {
                // Если ничего не нашли отдаем 0
                $resp = null;
            }
        } else {
            // Неуказан ресурс
            $resp = null;
        }
        // Возвращаем ответ на запрос
        return $resp;
    }
 
    // Обновляем
    public function put(string $resource = null, array $arr = [], int $id = null, string $field_id = null)
    {
        $this->resource = $resource;
        if ($resource != null) {
            if ($field_id == null) {
                $show = null;
                $resource_id = "id";
                $this->resource_id = "id";
                $show = $this->query("SHOW COLUMNS FROM `".$resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show == "id") {
                    $this->resource_id = "id";
                    $resource_id = "id";
                } else {
                    $show = $this->query("SHOW COLUMNS FROM `".$resource."` where `Field` = '".$resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                    if ($show == $resource."_id") {
                        $this->resource_id = $resource."_id";
                        $resource_id = $resource."_id";
                    }
                }
            } else {
                $this->resource_id = $field_id;
                $resource_id = $field_id;
            }
        }
        // Задаем пустое значение $query чтобы не выдавало ошибок
        $query = '';
        // если есть id, тогда в массиве $arr данные для одной записи
        if ($id >= 1) {
            if (count($arr) >= 1) {
                foreach($arr as $key => $value)
                {
                    if ($key == ''){$key = null;}
                    if (isset($key) && isset($value)) {
                        $query .= "`".$key."` ='".$value."' ";
                    }
                }
            }
            // Формируем запрос к базе данных
            $sql = "UPDATE `".$resource."` SET ".$query." WHERE `".$resource_id."` ='".$id."'";
            // Отправляем запрос в базу
            $stmt = $this->prepare($sql);

            if ($stmt->execute()) {
                // Если все ок отдаем 1
                $total = 1;
            } else {
                // Если нет отдаем 0
                $total = null;
                return $total;
            }
        } else {
            $i=0;
            if (count($arr) >= 1) {
                foreach($arr as $item)
                {
                    foreach($item as $key => $value)
                    {
                        if ($key == ""){$key = null;}
                        if (isset($key) && isset($value)) {
                            if ($key == $resource_id){
                                $key_id = $key;
                                $id = $value;
                            } else {
                                $query .= "`".$key."` ='".$value."' ";
                            }
                        }
                    }
                    // Формируем запрос к базе данных
                    $sql = "UPDATE `".$this->resource."` SET ".$query." WHERE `".$key_id."` ='".$id."'";
                    // Отправляем запрос в базу
                    $stmt = $this->prepare($sql);
                    if ($stmt->execute()) {
                        // Если все ок +1
                        $i+=1;
                    } else {
                        // Если нет +0
                        $i+=0;
                    }
                }
            }
            $total = $i;
        }

        // Возвращаем колличество обновленных записей
        return $total;
 
    }

    // Удаляем
    public function del(string $resource = null, array $arr = [], int $id = null, string $field_id = null): int
    {
        if ($resource != null) {
            if ($id >= 1) {
                $show = null;
                $resource_id = "id";
                $show = $this->query("SHOW COLUMNS FROM `".$resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show == "id") {
                    $resource_id = "id";
                } else {
                    $show = $this->query("SHOW COLUMNS FROM `".$resource."` where `Field` = '".$resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                    if ($show == $resource."_id") {
                        $resource_id = $resource."_id";
                    }
                }
            }
            if ($id >= 1) {
                // Формируем запрос к базе данных
                $sql = "DELETE FROM `".$resource."` WHERE `".$resource_id."` ='".$id."'";
                // Отправляем запрос в базу
                $stmt = $this->prepare($sql);
                if ($stmt->execute()) {
                    // Если все ок отдаем 1
                    $resp = 1;
                } else {
                    // Если нет отдаем null
                    $resp = null;
                    return $resp;
                }
            } else {
                $i=0;
                if (count($arr) >= 1) {
                    foreach($arr as $item)
                    {
                        foreach($item as $key => $value)
                        {
                            if ($key == ""){$key = null;}
                            if (isset($key) && isset($value)) {
                                $resource_id = $key;
                                $id = $value;
                            }
                        }
                        // Формируем запрос к базе данных
                        $sql = "DELETE FROM `".$resource."` WHERE `".$resource_id."` ='".$id."'";
                        // Отправляем запрос в базу
                        $stmt = $this->prepare($sql);
                        if ($stmt->execute()) {
                            // Если все ок +1
                            $i+=1;
                        } else {
                            // Если нет +0
                            $i+=0;
                        }
                    }
                    $resp = $i;
                } else {
                    $resp = null;
                }
            }
        } else {
            // Неуказан ресурс
            $resp = null;
        }
        // Возвращаем ответ
        return $resp;
    }

    // count для пагинатора
    public function count(string $resource = null, array $arr = [], int $id = null, string $field_id = null)
    {
        $i=0;
        // Приходится делать запрос и при наличии id, так как может отдать null
        if ($id >= 1) {
            // Формируем запрос к базе данных
            $sql = "SELECT COUNT(*) FROM  `".$resource."` WHERE  `".$this->resource_id."` ='".$id."' LIMIT 1";
        } else {
            $query = "";
            if (count($arr) >= 1) {
                foreach($arr as $key => $value)
                {
                    if ($key == ""){$key = null;}
                    if (isset($key) && isset($value)) {
                        
                        if ($key != "sort" && $key != "order" && $key != "offset" && $key != "limit" && $key != "relations") {
                            if ($this->key_null == $key || $this->key_null == null) {
                                $query .= "WHERE `".$key."` ='".$value."' ";
                                $this->key_null = $key;
                            } else {
                                if (is_int($value)) {
                                    $query .= "AND `".$key."` ='".$value."' ";
                                } else {
                                    $query .= "AND `".$key."` LIKE '%".$value."%' ";
                                }
                            }
                        }
                    }
                }
            }

            // Формируем запрос к базе данных
            $sql = "SELECT COUNT(*) FROM `".$resource."` ".$query;
        }
        // Отправляем запрос в базу
        $stmt = $this->prepare($sql);
        if ($stmt->execute()) {
            // Ответ будет массивом
            $response = [];
            // Получаем ответ в виде массива
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Если ничего не нашли отдаем null
            $response = null;
        }
        return $response;
    }

    // Получить последний идентификатор
    public function last_id(string $resource): int
    {
        return $this->query("SHOW TABLE STATUS LIKE '".$resource."'")->fetch(PDO::FETCH_ASSOC)['Auto_increment'];
    }

    // Получить список полей таблицы
    public function fieldMap($table = null)
    {
        $fieldMap = null;
        if (isset($table)) {
             $fieldMap = $this->query('DESCRIBE ' . $table)->fetchAll(PDO::FETCH_ASSOC);
        }
        return $fieldMap;
    }

    public function tableSchema($table)
    {
        $fieldMap = $this->fieldMap($table);
        $table_schema = [];
        foreach($fieldMap as $column)
        {
            $field = $column['Field'];
            $field_type = $column['Type'];
            $table_schema[$field] = $field_type;
        }
        
        return $table_schema;
    }

}

/*
// *************************************
// * extends Classes & implements Interfaces
// *************************************

// Mysql extends AdapterDb implements PdoInterface, ApisInterface

// PdoInterface extends ApiInterface
    // public function pdo($sql, $args = null);
    // public function fieldMap(string $resource = null);
    // public function tableSchema(string $resource = null);

// ApisInterface extends ApiInterface
    // public function apis(array $arr = [], string $type = null);
    // public function setType(string $type = null);
    // public function setCode(int $code = null);
    // public function setMessage(string $message = null);
    // public function setHttpCodes(string $httpCode = null);

// ApiInterface extends DbInterface
    // public function __construct(array $config = [], array $options = [], string $prefix = null);
    // public function ping(string $resource = null);
    // public function get(string $resource = null, array $array = [], int $id = null, string $field_id = null);
    // public function search(string $resource = null, string $keyword = null, array $array = [], string $field_id = null);
    // public function post(string $resource = null, array $array = [], string $field_id = null);
    // public function put(string $resource = null, array $array = [], int $id = null, string $field_id = null);
    // public function patch(string $resource = null, array $array = [], int $id = null, string $field_id = null);
    // public function del(string $resource = null, array $array = [], int $id = null, string $field_id = null);
    // public function last_id(string $resource = null, string $field_id = null);

// DbInterface extends \ArrayAccess, \Countable, \ArrayIterator
    // Magic Methods
    // public function __set($key, $value = null);
    // public function __get($key);
    // public function __isset($key);
    // public function __unset($key);

// \ArrayAccess
    // public function offsetSet($offset, $value);
    // public function offsetExists($offset);
    // public function offsetUnset($offset);
    // public function offsetGet($offset);

// \Countable
    // public function count();

// \ArrayIterator
    // public function getIterator();

*/

/*
// Стандартный конструктор PDO
    public function __construct($dsn, $username = null, $password = null, $options = [])
    {
        $default_options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $options = array_replace($default_options, $options);
        parent::__construct($dsn, $username, $password, $options);
    }
*/
 