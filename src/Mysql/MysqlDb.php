<?php
/**
 * This file is part of the RouterDb
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/pllano/router-db
 * @version 1.0.1
 * @package pllano/router-db
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace RouterDb\Mysql;
 
use RouterDb\Mysql\PdoDb;
use PDO;
 
class MysqlDb
{
 
    protected $db;
    private $sort = "id";
    private $order = "DESC";
    private $offset = 0;
    private $limit = 10;
    private $relations = null;
    private $resource = null;
    private $config = null;
    private $key_null = null;
    private $resource_id = "id";
 
    function __construct(array $config = array())
    {
        if (count($config) >= 1){
            $this->config = $config;
            PdoDb::set($config);
            $this->db = PdoDb::getInstance();
        }
    }
 
    // Загрузить
    public function get($resource = null, array $arr = array(), $id = null, $field_id = null)
    {
        $this->resource = $resource;
        if ($resource != null) {
            if ($field_id == null) {
                
                $show = null;
                $resource_id = "id";
                $this->resource_id = "id";
                $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show == "id") {
                    $this->resource_id = "id";
                    $resource_id = "id";
                    $this->sort = "id"; 
                } else {
                    $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$resource."` where `Field` = '".$resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
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
        //print_r($count["0"]["COUNT(*)"]);
        
        
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
            $sql = "
                SELECT * 
                FROM  `".$resource."` 
                WHERE  `".$resource_id."` ='".$id."' 
                LIMIT 1
            ";
        } 
        else {
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
        $stmt = $this->db->dbh->prepare($sql);
        if ($stmt->execute()) {
            // Ответ будет массивом
            $response = array();
            // Получаем ответ в виде массива
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
            $resp["headers"]["status"] = "200 OK";
            $resp["headers"]["code"] = 200;
            $resp["headers"]["message"] = "OK";
            $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["source"] = "mysql";
            $resp["response"]["total"] = $count["0"]["COUNT(*)"];
            $resp["response"]["pages"] = ceil($count["0"]["COUNT(*)"] / $this->limit);
            $resp["request"]["query"] = "GET";
        } else {
            // Если ничего не нашли отдаем 404
            $response = null;
            $resp["headers"]["status"] = "404 Not Found";
            $resp["headers"]["code"] = 404;
            $resp["headers"]["message"] = "Not Found";
            $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["source"] = "mysql";
            $resp["response"]["total"] = 0;
            $resp["request"]["query"] = "GET";
            return $resp;
        }
        
        $resp["request"]["resource"] = $resource;
        if (isset($id)) {
            $resp["request"]["id"] = $id;
        }
        if ($id === null) {
            if (isset($this->relations)) {
                $resp["request"]["relations"] = $this->relations;
            }
            if (isset($this->sort)) {
                $resp["request"]["sort"] = $this->sort;
            }
            if (isset($this->order)) {
                $resp["request"]["order"] = $this->order;
            }
            if (isset($this->offset)) {
                $resp["request"]["offset"] = $this->offset;
            }
            if (isset($this->limit)) {
                $resp["request"]["limit"] = $this->limit;
            }

        }

            if (count($response) >= 1) {
                if (isset($this->relations)) {
                    // Получаем связи
                    $id = null;
                    $relation = null;
                    $foreach = 0;
                    if (base64_decode($this->relations, true) != false){
                        $relation = base64_decode($this->relations);
                        if (json_decode($relation, true) != null){
                            $relation = json_decode($relation, true);
                            $foreach = 1;
                        } else {
                            $relation = $this->relations;
                        }
                    } else {
                        $relation = $this->relations;
                    }
 
                    foreach($relation as $key => $arr)
                    {
                        if (isset($key) && isset($arr)) {
                            $id = $arr->{$resource_id};
                            $newArr = (array)$arr;
                            //print_r($newdArr);
                            if (isset($id)) {
                                if ($foreach == 1) {
                                    foreach($relation as $key => $value)
                                    {
                                        $rel = $this->get_relations($key, $resource_id, $id);
                                        foreach($rel as $k => $v) {
                                            if (in_array($k, $value)) {
                                                $a = array($k, $v);
                                                unset($a["0"]);
                                                $a = $a["1"];
                                                $r[$key][] = $a;
                                            }
                                        }
                                        $newArr = array_merge($newArr, $r);
                                    }
                                } else {
                                    $rel = null;
                                    $ex = explode(",", $relation);
                                    foreach($ex as $ex_keys => $ex_val)
                                    {
                                        $ex_pos = strripos($ex_val, ":");
                                        $new_ex = [];
                                        if ($ex_pos === false) {
                                            $val = $ex_val;
                                            $c = 0;
                                        } else {
                                            $ex_new = explode(":", $ex_val);
                                            $val = $ex_new["0"];
                                            unset($ex_new["0"]);
                                            $new_ex = array_flip($ex_new);
                                            $c = 1;
                                        }
                                        $val_name = $val.'_id';
                                        if (isset($newArr[$val_name])) {
                                            $val_id = $newArr[$val_name];
                                        }
                                        $rel_table_config = json_decode(file_get_contents($this->config["db"]["json"]["dir"].''.$val.'.config.json'), true);

                                        if (array_key_exists($resource_id, $rel_table_config["schema"]) && isset($id)) {
                                            
                                            $rel = $this->get_relations($val, $resource_id, $id);
                                            if ($c == 1){
                                                $control = $new_ex;
                                            } else {
                                                $control = $rel_table_config["schema"];
                                            }
                                                            
                                        } elseif(array_key_exists($val_name, $table_config["schema"]) && isset($val_id)) {
                                                        
                                            $rel = $this->get_relations($val, $val_name, $val_id);
                                            if ($c == 1){
                                                $control = $new_ex;
                                            } else {
                                                $control = $rel_table_config["schema"];
                                            }
                                        }
                                        if (count($rel) >= 1) {
                                            $r = array();
                                            foreach($rel as $k => $v)
                                            {
                                                $vv = (array)$v;
                                                $ar = array();
                                                foreach($vv as $key => $va)
                                                {
                                                    if (array_key_exists($key, $control) && $key != "password" && $key != "cookie") {
                                                        $ar[$key] = $va;
                                                    }
                                                }
                                                $a = array($k, $ar);
                                                unset($a["0"]);
                                                $a = $a["1"];
                                                $r[$val][] = $a;
                                            }
                                            $newArr = array_merge($newArr, $r);
                                        }
                                    }
                                }
                            }
                            $newArr = (object)$newArr;
                        }
                        $array = array($key, $newArr);
                        unset($array["0"]);
                        $array = $array["1"];
                        $item["item"] = $array;
                        $items['items'][] = $item;
                    }
                    $resp['body'] = $items;
 
                } 
                else {
                    foreach($response as $key => $arr){
                        if (isset($key) && isset($arr)) {
                            $array = array($key, $arr);
                            unset($array["0"]);
                            $array = $array["1"];
                            $item["item"] = $array;
                            $items['items'][] = $item;
                        }
                    }
                    $resp['body'] = $items;
                }
            } elseif ($response == null) {
                $resp["body"]["items"] = "[]";
            }
 
            return $resp;
 
    }
 
    // Искать
    public function search($resource = null, array $query_arr = array(), $keyword = null, $field_id = null)
    {
        // Новый запрос, аналог get рассчитан на полнотекстовый поиск
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
 
        // Еще в разработке ...
    }
 
    // Создаем одну запись
    public function post($resource = null, array $arr = array(), $field_id = null)
    {
        if ($field_id == null) {
            $show = null;
            $resource_id = "id";
            $this->resource_id = "id";
            $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
            if ($show == "id") {
                $this->resource_id = "id";
                $resource_id = "id";
            } else {
                $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$resource."` where `Field` = '".$resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
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
            $stmt = $this->db->dbh->prepare($sql);
            if ($stmt->execute()) {
                // Если все ок отдаем id
                $response = $this->db->dbh->lastInsertId();
 
                $resp["headers"]["status"] = "201 Created";
                $resp["headers"]["code"] = 201;
                $resp["headers"]["message"] = "Created";
                $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 1;
                $resp["response"]["id"] = $response;
                $resp["response"]["source"] = "mysql";
                $resp["request"]["query"] = "POST";
                $resp["request"]["resource"] = $resource;
            
            } else {
                // Если ничего не нашли отдаем 0
                $response = null;
                $resp["headers"]["status"] = '400 Bad Request';
                $resp["headers"]["code"] = 400;
                $resp["headers"]["message"] = 'Bad Request';
                $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = $response;
                $resp["response"]["id"] = null;
                $resp["response"]["source"] = "mysql";
                $resp["request"]["query"] = "POST";
                $resp["request"]["resource"] = $resource;
            }
        } else {
            // Неуказан ресурс
            $response = null;
            $resp["headers"]["status"] = '400 Bad Request';
            $resp["headers"]["code"] = 400;
            $resp["headers"]["message"] = 'Bad Request';
            $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = $response;
            $resp["response"]["id"] = null;
            $resp["response"]["source"] = "mysql";
            $resp["request"]["query"] = "POST";
            $resp["request"]["resource"] = $resource;
        }
        // Возвращаем ответ на запрос
        return $resp;
    }
 
    // Обновляем
    public function put($resource = null, array $arr = array(), $id = null, $field_id = null)
    {
        $this->resource = $resource;
        if ($resource != null) {
            if ($field_id == null) {
                $show = null;
                $resource_id = "id";
                $this->resource_id = "id";
                $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show == "id") {
                    $this->resource_id = "id";
                    $resource_id = "id";
                } else {
                    $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$resource."` where `Field` = '".$resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
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
            $sql = "
                UPDATE `".$resource."` 
                SET ".$query." 
                WHERE `".$resource_id."` =".$id."
            ";
            // Отправляем запрос в базу
            $stmt = $this->db->dbh->prepare($sql);

            if ($stmt->execute()) {
                // Если все ок отдаем 1
                $total = 1;
                $resp["headers"]["status"] = "202 Accepted";
                $resp["headers"]["code"] = 202;
                $resp["headers"]["message"] = "Accepted";
                $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = $total;
                $resp["response"]["id"] = $id;
                $resp["request"]["query"] = "PUT";
                $resp["request"]["resource"] = $this->resource;
            } else {
                // Если нет отдаем 0
                $total = null;
                $resp["headers"]["status"] = '400 Bad Request';
                $resp["headers"]["code"] = 400;
                $resp["headers"]["message"] = 'Bad Request';
                $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = $total;
                $resp["response"]["id"] = $id;
                $resp["response"]["source"] = "mysql";
                $resp["request"]["query"] = "PUT";
                $resp["request"]["resource"] = $this->resource;
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
                            if ($key == $resource_id){
                                $key_id = $key;
                                $id = $value;
                            } else {
                                $query .= "`".$key."` ='".$value."' ";
                            }
                        }
                    }
                    // Формируем запрос к базе данных
                    $sql = "
                        UPDATE `".$this->resource."` 
                        SET ".$query." 
                        WHERE `".$key_id."` =".$id."
                    ";
                    // Отправляем запрос в базу
                    $stmt = $this->db->dbh->prepare($sql);
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
            $resp["headers"]["status"] = '400 Bad Request';
            $resp["headers"]["code"] = 400;
            $resp["headers"]["message"] = 'Bad Request';
            $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = $total;
            $resp["response"]["source"] = "mysql";
            $resp["request"]["query"] = "PUT";
            $resp["request"]["resource"] = $this->resource;
        }
 
        // Возвращаем колличество обновленных записей
        return $resp;
 
    }
    
    // Обновляем
    public function patch($resource = null, array $arr = array(), $id = null, $field_id = null)
    {
        $this->resource = $resource;
        if ($this->resource == null) {
            if ($field_id == null) {
                $show = null;
                $resource_id = "id";
                $this->resource_id = "id";
                $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$this->resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show == "id") {
                    $this->resource_id = "id";
                    $resource_id = "id";
                } else {
                    $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$this->resource."` where `Field` = '".$this->resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                    if ($show == $this->resource."_id") {
                        $this->resource_id = $this->resource."_id";
                        $resource_id = $this->resource."_id";
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
            $sql = "
                UPDATE `".$this->resource."` 
                SET ".$query." 
                WHERE `".$resource_id."` =".$id."
            ";
            // Отправляем запрос в базу
            $stmt = $this->db->dbh->prepare($sql);

            if ($stmt->execute()) {
                // Если все ок отдаем 1
                $total = 1;
                $resp["headers"]["status"] = "202 Accepted";
                $resp["headers"]["code"] = 202;
                $resp["headers"]["message"] = "Accepted";
                $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = $total;
                $resp["response"]["id"] = $id;
                $resp["request"]["query"] = "PATCH";
                $resp["request"]["resource"] = $this->resource;
            } else {
                // Если нет отдаем 0
                $total = null;
                $resp["headers"]["status"] = '400 Bad Request';
                $resp["headers"]["code"] = 400;
                $resp["headers"]["message"] = 'Bad Request';
                $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = $total;
                $resp["response"]["id"] = $id;
                $resp["response"]["source"] = "mysql";
                $resp["request"]["query"] = "PATCH";
                $resp["request"]["resource"] = $this->resource;
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
                            if ($key == $resource_id){
                                $key_id = $key;
                                $id = $value;
                            } else {
                                $query .= "`".$key."` ='".$value."' ";
                            }
                        }
                    }
                    // Формируем запрос к базе данных
                    $sql = "
                        UPDATE `".$this->resource."` 
                        SET ".$query." 
                        WHERE `".$key_id."` =".$id."
                    ";
                    // Отправляем запрос в базу
                    $stmt = $this->db->dbh->prepare($sql);
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
            $resp["headers"]["status"] = '400 Bad Request';
            $resp["headers"]["code"] = 400;
            $resp["headers"]["message"] = 'Bad Request';
            $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = $total;
            $resp["response"]["source"] = "mysql";
            $resp["request"]["query"] = "PATCH";
            $resp["request"]["resource"] = $this->resource;
        }
 
        // Возвращаем колличество обновленных записей
        return $resp;
 
    }
 
    // Удаляем
    public function delete($resource = null, array $arr = array(), $id = null, $field_id = null)
    {
        if ($resource != null) {
            if ($id >= 1) {
                $show = null;
                $resource_id = "id";
                $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show == "id") {
                    $resource_id = "id";
                } else {
                    $show = $this->db->dbh->query("SHOW COLUMNS FROM `".$resource."` where `Field` = '".$resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                    if ($show == $resource."_id") {
                        $resource_id = $resource."_id";
                    }
                }
            }
            if ($id >= 1) {
                // Формируем запрос к базе данных
                $sql = "
                    DELETE 
                    FROM `".$resource."` 
                    WHERE `".$resource_id."` ='".$id."'
                    ";
                // Отправляем запрос в базу
                $stmt = $this->db->dbh->prepare($sql);
                if ($stmt->execute()) {
                    // Если все ок отдаем 1
                    $response = 1;
                    $resp["headers"]["status"] = "200 Removed";
                    $resp["headers"]["code"] = 200;
                    $resp["headers"]["message"] = "Removed";
                    $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["total"] = $response;
                    $resp["response"]["id"] = $id;
                    $resp["request"]["query"] = "DELETE";
                    $resp["request"]["resource"] = $resource;
                } else {
                    // Если нет отдаем null
                    $response = null;
                    $resp["headers"]["status"] = '404 Not Found';
                    $resp["headers"]["code"] = 404;
                    $resp["headers"]["message"] = 'Not Found';
                    $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["total"] = $response;
                    $resp["response"]["id"] = $id;
                    $resp["request"]["id"] = $response;
                    $resp["request"]["query"] = "DELETE";
                    $resp["request"]["resource"] = $resource;
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
                        $sql = "
                            DELETE
                            FROM `".$resource."` 
                            WHERE `".$resource_id."` =".$id."
                        ";
                        // Отправляем запрос в базу
                        $stmt = $this->db->dbh->prepare($sql);
                        if ($stmt->execute()) {
                            // Если все ок +1
                            $i+=1;
                        } else {
                            // Если нет +0
                            $i+=0;
                        }
                    }
                    $response = $i;
                    $resp["headers"]["status"] = "200 Removed";
                    $resp["headers"]["code"] = 200;
                    $resp["headers"]["message"] = "Deleted all rows";
                    $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["total"] = $response;
                    $resp["request"]["query"] = "DELETE";
                    $resp["request"]["resource"] = $resource;
                } else {
                    $response = null;
                    $resp["headers"]["status"] = '400 Bad Request';
                    $resp["headers"]["code"] = 400;
                    $resp["headers"]["message"] = 'Bad Request';
                    $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["total"] = $response;
                    $resp["request"]["query"] = "DELETE";
                    $resp["request"]["resource"] = $resource;
                }
            }
        } else {
            // Неуказан ресурс
            $response = null;
            $resp["headers"]["status"] = '404 Not Found';
            $resp["headers"]["code"] = 404;
            $resp["headers"]["message"] = 'Not Found';
            $resp["headers"]["message_id"] = $this->config["db"]['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = $response;
            $resp["request"]["query"] = "DELETE";
            $resp["request"]["resource"] = null;
        }
        // Возвращаем ответ
        return $resp;
    }
 
    // count для пагинатора
    public function count($resource = null, array $arr = array(), $id = null, $field_id = null)
    {
        $i=0;
        // Приходится делать запрос и при наличии id, так как может отдать null
        if ($id >= 1) {
            // Формируем запрос к базе данных
            $sql = "
                SELECT COUNT(*) 
                FROM  `".$resource."` 
                WHERE  `".$this->resource_id."` ='".$id."' 
                LIMIT 1
            ";
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
            $sql = "
                SELECT COUNT(*) 
                FROM `".$resource."` 
                ".$query." 
            ";
        }
        // Отправляем запрос в базу
        $stmt = $this->db->dbh->prepare($sql);
        if ($stmt->execute()) {
            // Ответ будет массивом
            $response = array();
            // Получаем ответ в виде массива
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Если ничего не нашли отдаем null
            $response = null;
        }
        return $response;
    }
 
    public function get_relations($resource, $key, $value)
    {
        // Формируем запрос к базе данных
        $sql = "
            SELECT * 
            FROM `".$resource."` 
            WHERE `".$key."` ='".$value."'
        ";
        // Отправляем запрос в базу
        $stmt = $this->db->dbh->prepare($sql);
        if ($stmt->execute()) {
            // Ответ будет массивом
            $response = array();
            // Получаем ответ в виде массива
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $response = null;
        }
 
        return $response;
 
    }
 
    // Получить последний идентификатор
    public function last_id($resource)
    {
        return $this->db->dbh->query("SHOW TABLE STATUS LIKE '".$resource."'")->fetch(PDO::FETCH_ASSOC)['Auto_increment'];
    }
 
}
     