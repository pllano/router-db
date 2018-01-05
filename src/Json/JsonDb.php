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
 
namespace RouterDb\Json;

use jsonDB\Db;
use jsonDB\Database;
use jsonDB\Validate;
use jsonDB\dbException;

class JsonDb
{
 
    private $resource = null;
    private $dir = null;
    private $cached = null;
    private $cache_lifetime = null;
    private $temp = null;
    private $api = null;
    private $crypt = null;
    private $config = null;
 
    public function __construct(array $config = array())
    {
        if (count($config) >= 1) {
            $this->config = $config;
        }
    }
 
    public function get($resource = null, array $query = array(), $id = null)
    {
        if (isset($resource)) {
            // Проверяем наличие главной базы
            try {Validate::table($resource)->exists();
 
                // Конфигурация таблицы
                $table_config = json_decode(file_get_contents($this->config["db"]["json"]["dir"].'/'.$resource.'.config.json'), true);
                
                // Формируем набор параметров для работы с кешем
                $CacheID = http_build_query($query);
                // Читаем данные в кеше
                $cacheReader = Db::cacheReader($CacheID);
                // Если кеш отдал null, формируем запрос к базе
                if ($cacheReader == null) {
                    // Если указан id
                    if ($id >= 1) {
                        $res = Database::table($resource)->where('id', '=', $id)->findAll();
                        
                        $resCount = count($res);
                        if ($resCount == 1) {
                            
                            $resp["headers"]["status"] = "200 OK";
                            $resp["headers"]["code"] = 200;
                            $resp["headers"]["message"] = "OK";
                            $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                            $resp["response"]["source"] = "db";
                            $resp["response"]["total"] = $resCount;
                            $resp["request"]["query"] = "GET";
                            $resp["request"]["resource"] = $resource;
                            $resp["request"]["id"] = $id;
                            
                                if (isset($query["relation"])) {
                                    $id = null;
                                    $resource_id = $resource.'_id';
                                    $relation = null;
                                    $foreach = 0;
                                    if (base64_decode($query["relation"], true) != false){
                                        $relation = base64_decode($query["relation"]);
                                        if (json_decode($relation, true) != null){
                                            $relation = json_decode($relation, true);
                                            $foreach = 1;
                                        } else {
                                            $relation = $query["relation"];
                                        }
                                    } else {
                                        $relation = $query["relation"];
                                    }
                                    $resp["request"]["relation"] = $relation;
 
                                    foreach($res as $key => $arr){
                                        if (isset($key) && isset($arr)) {
                                            $id = $arr->{$resource_id};
                                            $newArr = (array)$arr;
                                            //print_r($newdArr);
                                            if (isset($id)) {
                                                if ($foreach == 1) {
                                                    foreach($relation as $key => $value) {
                                                        $rel = Database::table($key)->where($resource_id, '=', $id)->findAll();
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
                                                    foreach($ex as $ex_keys => $ex_val) {
                                                        $ex_pos = strripos($ex_val, ":");
                                                        $new_ex = [];
                                                        if ($ex_pos === false) {
                                                            $val = $ex_val;
                                                            $c = 0;
                                                        } else {
                                                            $ex_new = explode(":", $ex_val);
                                                            $val = $ex_new["0"];
                                                            unset($ex_new["0"]);
                                                            //print_r($ex_new);
                                                            //print("<br>");
                                                            $new_ex = array_flip($ex_new);
                                                            $c = 1;
                                                        }

                                                        $val_name = $val.'_id';
                                                        if (isset($newArr[$val_name])) {
                                                            $val_id = $newArr[$val_name];
                                                        }
                                                        
                                                        $rel_table_config = json_decode(file_get_contents($this->config["db"]["json"]["dir"].'/'.$val.'.config.json'), true);

                                                        if (array_key_exists($resource_id, $rel_table_config["schema"]) && isset($id)) {
                                                            
                                                            $rel = Database::table($val)->where($resource_id, '=', $id)->findAll();
                                                            if ($c == 1){
                                                                $control = $new_ex;
                                                            } else {
                                                                $control = $rel_table_config["schema"];
                                                            }
                                                            
                                                        } elseif(array_key_exists($val_name, $table_config["schema"]) && isset($val_id)) {
                                                        
                                                            $rel = Database::table($val)->where($val_name, '=', $val_id)->findAll();
                                                            if ($c == 1){
                                                                $control = $new_ex;
                                                            } else {
                                                                $control = $rel_table_config["schema"];
                                                            }
                                                        }

                                                        if (count($rel) >= 1) {
                                                            $r = array();
                                                            foreach($rel as $k => $v) {
                                                                $vv = (array)$v;
                                                                $ar = array();
                                                                foreach($vv as $key => $va) {
                                                                    if (array_key_exists($key, $control) && $key != "password" && $key != "cookie") {
                                                                        $ar[$key] = $va;
                                                                    }
                                                                }
                                                            //$arr = 
                                                            //print_r($v);
                                                            //print("<br>");
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
                                            //$newArr = (object)$newArr;
                                        }
                                        $array = array($key, $newArr);
                                        unset($array["0"]);
                                        $array = $array["1"];
                                        $item["item"] = $array;
                                        $items['items'][] = $item;
                                    }
                                    $resp['body'] = $items;
                                } else {
                                    foreach($res as $key => $arr){
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
                            
                        } else {
                            $resp["headers"]["status"] = '404 Not Found';
                            $resp["headers"]["code"] = 404;
                            $resp["headers"]["message"] = 'Not Found';
                            $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                            $resp["response"]["source"] = "db";
                            $resp["response"]["total"] = 0;
                            $resp["request"]["query"] = "GET";
                            $resp["request"]["resource"] = $resource;
                            $resp["request"]["id"] = $id;
                            $resp["body"]["items"]["item"] = "[]";
                        }
                        
                    } else {
                        // id не указан, формируем запрос списка
                        // Указываем таблицу
                        $count = Database::table($resource);
                        $res = Database::table($resource);

                        // Если есть параметры
                        $quertyCount = count($query);
                        if ($quertyCount >= 1) {
                            $resp["headers"]["status"] = "200 OK";
                            $resp["headers"]["code"] = 200;
                            $resp["headers"]["message"] = "OK";
                            $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                                
                                foreach($query as $key => $value)
                                {
                                    if(!in_array($key, ['andWhere', 'orWhere', 'asArray', 'LIKE', 'relation', 'order', 'sort', 'limit', 'offset' ], true)) {
                                        if (isset($key) && isset($value)) {
                                            if (array_key_exists($key, $table_config["schema"])) {
                                                // Убираем пробелы и одинарные кавычки
                                                $key = str_replace(array(" ", "'", "%", "%27", "%20"), "", $key);
                                                $value = str_replace(array(" ", "'", "%", "%27", "%20"), "", $value);
                                                $count->where($key, '=', $value);
                                                $res->where($key, '=', $value);
                                                $resp["request"][$key] = $value;
                                            }
                                        }
                                    }
                                }
                                
                                if (isset($query["andWhere"])) {
                                    // Убираем пробелы и одинарные кавычки
                                    $andWhere = str_replace(array(" ", "'", "%"), "", $query["andWhere"]);
                                    // Ищем разделитель , запятую
                                    $pos = strripos($andWhere, ",");
                                    if ($pos === false) {
                                        // : запятая не найдена
                                        $count->andWhere('id', '=', $andWhere);
                                        $res->andWhere('id', '=', $andWhere);
                                        } else {
                                        // , запятая найдена
                                        $explode = explode(",", $andWhere);
                                        $count->andWhere($explode["0"], $explode["1"], $explode["2"]);
                                        $res->andWhere($explode["0"], $explode["1"], $explode["2"]);
                                    }
                                    $resp["request"]["andWhere"] = $query["andWhere"];
                                }
                                
                                if (isset($query["orWhere"])) {
                                    // Убираем пробелы и одинарные кавычки
                                    $orWhere = str_replace(array(" ", "'", "%"), "", $query["orWhere"]);
                                    // Ищем разделитель , запятую
                                    $pos = strripos($orWhere, ",");
                                    if ($pos === false) {
                                        // : запятая не найдена
                                        $count->orWhere('id', '=', $orWhere);
                                        $res->orWhere('id', '=', $orWhere);
                                        } else {
                                        // , запятая найдена
                                        $explode = explode(",", $relation);
                                        $count->orWhere($explode["0"], $explode["1"], $explode["2"]);
                                        $res->orWhere($explode["0"], $explode["1"], $explode["2"]);
                                    }
                                    $resp["request"]["orWhere"] = $query["orWhere"];
                                }
                                
                                if (isset($query["LIKE"])) {
                                    // Ищем разделитель , запятую
                                    $pos = strripos($query["LIKE"], ",");
                                    if ($pos === false) {
                                        // : запятая не найдена
                                        $count->where('id', 'LIKE', $query["LIKE"]);
                                        $res->where('id', 'LIKE', $query["LIKE"]);
                                        } else {
                                        // , запятая найдена
                                        $explode = explode(",", $query["LIKE"]);
                                        $count->where(str_replace(array(" ", "'"), "", $explode["0"]), 'LIKE', str_replace(array("<", ">", "'"), "", $explode["1"]));
                                        $res->where(str_replace(array(" ", "'"), "", $explode["0"]), 'LIKE', str_replace(array("<", ">", "'"), "", $explode["1"]));
                                    }
                                    $resp["request"]["LIKE"] = $query["LIKE"];
                                }
                                
                                if (isset($query["order"]) || isset($query["sort"])) {
                                    
                                    $order = "DESC";
                                    $sort = "id";
                                    
                                    if (isset($query["order"])) {
                                        if ($query["order"] == "DESC" || $query["order"] == "ASC" || $query["order"] == "desc" || $query["order"] == "asc") {
                                            $order = $query["offset"];
                                        }
                                    }
                                    
                                    if (isset($query["sort"])) {if (preg_match("/^[A-Za-z0-9]+$/", $query["sort"])) {
                                        $sort = $query["sort"];
                                    }}
                                    
                                    $res->orderBy($sort, $order);
                                    $resp["request"]["order"] = $order;
                                    $resp["request"]["sort"] = $sort;
                                }
                                
                                if (isset($query["limit"]) && isset($query["offset"]) == false) {
                                    $limit = intval($query["limit"]);
                                    $res->limit($limit);
                                    $resp["request"]["limit"] = $limit;
                                    $resp["request"]["offset"] = 0;
                                    } elseif (isset($query["limit"]) && isset($query["offset"])) {
                                    $limit = intval($query["limit"]);
                                    $offset = intval($query["offset"]);
                                    $res->limit($limit, $offset);
                                    $resp["request"]["limit"] = $limit;
                                    $resp["request"]["offset"] = $offset;
                                }
                                
                                $res->findAll();
                                
                                if (isset($query["asArray"])) {
                                    // Не работает в этом случае. Если цепочкой то работает.
                                    if ($query["asArray"] == true) {
                                        $res->asArray();
                                        $resp["request"]["asArray"] = true;
                                    }
                                }
                                
                                $count->findAll()->count();
                                $newCount = count($count);
                            
                            $resCount = count($res);
                            if ($resCount >= 1) {
                                $resp["headers"]["status"] = "200 OK";
                                $resp["headers"]["code"] = 200;
                                $resp["headers"]["message"] = "OK";
                                $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                                $resp["response"]["source"] = "db";
                                $resp["response"]["total"] = $newCount;
                                $resp["request"]["query"] = "GET";
                                $resp["request"]["resource"] = $resource;
                                if (isset($query["relation"])) {
                                    $id = null;
                                    $resource_id = $resource.'_id';
                                    $relation = null;
                                    $foreach = 0;
                                    if (base64_decode($query["relation"], true) != false){
                                        $relation = base64_decode($query["relation"]);
                                        if (json_decode($relation, true) != null){
                                            $relation = json_decode($relation, true);
                                            $foreach = 1;
                                        } else {
                                            $relation = $query["relation"];
                                        }
                                    } else {
                                        $relation = $query["relation"];
                                    }
                                    $resp["request"]["relation"] = $relation;
                                    foreach($res as $key => $arr){
                                        if (isset($key) && isset($arr)) {
                                            $id = $arr->{$resource_id};
                                            $newArr = (array)$arr;
                                            if (isset($id)) {
                                                if ($foreach == 1) {
                                                    foreach($relation as $key => $value) {
                                                        $rel = Database::table($key)->where($resource_id, '=', $id)->findAll();
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
                                                    foreach($ex as $ex_keys => $ex_val) {
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
                                                        $rel_table_config = json_decode(file_get_contents($this->config["db"]["json"]["dir"].'/'.$val.'.config.json'), true);
                                                        if (array_key_exists($resource_id, $rel_table_config["schema"]) && isset($id)) {
                                                            
                                                            $rel = Database::table($val)->where($resource_id, '=', $id)->findAll();
                                                            if ($c == 1){
                                                                $control = $new_ex;
                                                            } else {
                                                                $control = $rel_table_config["schema"];
                                                            }
                                                        } elseif(array_key_exists($val_name, $table_config["schema"]) && isset($val_id)) {
                                                            $rel = Database::table($val)->where($val_name, '=', $val_id)->findAll();
                                                            if ($c == 1){
                                                                $control = $new_ex;
                                                            } else {
                                                                $control = $rel_table_config["schema"];
                                                            }
                                                        }
                                                        if (count($rel) >= 1) {
                                                            $r = array();
                                                            foreach($rel as $k => $v) {
                                                                $vv = (array)$v;
                                                                $ar = array();
                                                                foreach($vv as $key => $va) {
                                                                    if (array_key_exists($key, $control) && $key != "password" && $key != "cookie") {
                                                                        $ar[$key] = $va;
                                                                    }
                                                                }
                                                            //$arr = 
                                                            //print_r($v);
                                                            //print("<br>");
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
                                            //$newArr = (object)$newArr;
                                        }
                                        $array = array($key, $newArr);
                                        unset($array["0"]);
                                        $array = $array["1"];
                                        $item["item"] = $array;
                                        $items['items'][] = $item;
                                    }
                                    $resp['body'] = $items;
                                } else {
                                    foreach($res as $key => $arr){
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
                            } else {
                                // База вернула 0 записей или null
                                $resp["headers"]["status"] = "404 Not Found";
                                $resp["headers"]["code"] = 404;
                                $resp["headers"]["message"] = "Not Found";
                                $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                                $resp["response"]["source"] = "db";
                                $resp["response"]["total"] = 0;
                                $resp["request"]["query"] = "GET";
                                $resp["request"]["resource"] = $resource;
                            }
                            
                            // Записываем данные в кеш
                            Db::cacheWriter($CacheID, $resp);
                            
                        } else {
                            // Параметров нет отдаем все записи
                            $res = Database::table($resource)->findAll();
                            $resCount = count($res);
                            if ($resCount >= 1) {
                                $resp["headers"]["status"] = "200 OK";
                                $resp["headers"]["code"] = 200;
                                $resp["headers"]["message"] = "OK";
                                $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                                $resp["response"]["source"] = "db";
                                $resp["response"]["total"] = $resCount;
                                $resp["request"]["query"] = "GET";
                                $resp["request"]["resource"] = $resource;

                                foreach($res as $key => $value){
                                    if (isset($key) && isset($value)) {
                                        $array = array($key, $value);
                                        unset($array["0"]);
                                        $array = $array["1"];
                                        $item["item"] = $array;
                                        $items['items'][] = $item;
                                    }
                                }
                                $resp['body'] = $items;
                            } else {
                                $resp["headers"]["status"] = "404 Not Found";
                                $resp["headers"]["code"] = 404;
                                $resp["headers"]["message"] = "Not Found";
                                $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                                $resp["response"]["source"] = "db";
                                // База вернула 0 записей или null
                                $resp["response"]["total"] = 0;
                                $resp["request"]["query"] = "GET";
                                $resp["request"]["resource"] = $resource;
                            }
                            
                            // Записываем данные в кеш
                            Db::cacheWriter($CacheID, $resp);
                        }
                    }
                } else {
                    // Если нашли в кеше отдаем с кеша
                    $resp = $cacheReader;
                }
            } catch(dbException $e) {
                // Такой таблицы не существует
                $resp["headers"]["status"] = '404 Not Found';
                $resp["headers"]["code"] = 404;
                $resp["headers"]["message"] = 'resource Not Found';
                $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
                $resp["request"]["query"] = "GET";
                $resp["request"]["resource"] = '';
            }  
        } else {
            // Название таблицы не задано.
            $resp["headers"]["status"] = '403 Access is denied';
            $resp["headers"]["code"] = 403;
            $resp["headers"]["message"] = 'Access is denied';
            $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
            $resp["request"]["query"] = "GET";
            $resp["request"]["resource"] = '';
        }
        
        return $resp;
    }
 
    // Создаем одну запись
    public function post($resource = null, array $arr = array())
    {
        if (isset($resource)) {
            // Проверяем наличие главной базы если нет даем ошибку
            try {
                Validate::table($resource)->exists();
                // Получаем параметры ресурса
                $table_config = json_decode(file_get_contents($this->config["db"]["json"]["dir"].'/'.$resource.'.config.json'), true);
 
                // Подключаем таблицу
                $row = jsonDb::table($resource);
                // Разбираем параметры полученные в теле запроса
                foreach($arr as $key => $value){
                   if (isset($key) && isset($value)) {
                        if ($key != "id") {
                            if (array_key_exists($key, $table_config["schema"])) {
                                if ($table_config["schema"][$key] == "integer") {
                                    if (is_numeric($value)) {
                                        $value = intval($value);
                                    } else {
                                        $value = 0;
                                    }
                                }
                                if ($table_config["schema"][$key] == "double") {
                                    if (is_float($value * 1)) {
                                        //$value = floatval($value);
                                        $value = (float)$value;
                                    } else {
                                        $value = (float)$value;
                                    }
                                }
                                if ($table_config["schema"][$key] == "boolean") {
                                    if (is_bool($value)) {
                                        $value = boolval($value);
                                    } else {
                                        $value = false;
                                    }
                                }
                                if ($table_config["schema"][$key] == "string") {
                                    if (is_string($value)) {
                                        $value = strval($value);
                                    } else {
                                        $value = null;
                                    }
                                } 
                                
                                try {
                                    $row->{$key} = $value;
                                } catch(dbException $error){
                                    //echo $error;
                                }
                            }
                        }
                    }
                }
                // Сохраняем
                $row->save();
 
                if ($row->id >= 1) {
                    // Добавляем вротой id
                    $update = jsonDb::table($resource)->find($row->id);
                    $update->{$resource."_id"} = $row->id;
                    $update->save();
                        
                    // Все ок. 201 Created «создано»
                    $resp["headers"]["status"] = "201 Created";
                    $resp["headers"]["code"] = 201;
                    $resp["headers"]["message"] = "Created";
                    $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["id"] = $row->id;
                    $resp["request"]["query"] = "POST";
                    $resp["request"]["resource"] = $resource;
                } else {
                    // Не удалось создать. 501 Not Implemented «не реализовано»
                    $resp["headers"]["status"] = '501 Not Implemented';
                    $resp["headers"]["code"] = 501;
                    $resp["headers"]["message"] = 'Not Implemented';
                    $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["total"] = 0;
                }
 
            } catch(dbException $e){
                // Таблица не существует даем ошибку 404
                $resp["headers"]["status"] = '404 Not Found';
                $resp["headers"]["code"] = 404;
                $resp["headers"]["message"] = 'resource Not Found';
                $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }
 
        } else {
            // Если таблица не определена даем ошибку 400
            $resp["headers"]["status"] = '400 Bad Request';
            $resp["headers"]["code"] = 400;
            $resp["headers"]["message"] = 'Bad Request';
            $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
        }
 
        return $resp;
 
    }
 
    // Обновляем
    public function put($resource = null, array $arr = array(), $id = null)
    {
        if (isset($resource)) {
            // Проверяем наличие главной базы если нет даем ошибку
            try {
                Validate::table($resource)->exists();
                $table_config = json_decode(file_get_contents($this->config["db"]["json"]["dir"].'/'.$resource.'.config.json'), true);

                // Если указан id обновляем одну запись
                if ($id >= 1) {
                    // Подключаем таблицу
                    $row = jsonDb::table($resource)->find($id);
                    // Разбираем параметры полученные в теле запроса
                    foreach($arr as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id") {
                                if (array_key_exists($key, $table_config["schema"])) {
 
                                    if ($table_config["schema"][$key] == "integer") {
                                        if (is_numeric($value)) {
                                            $value = intval($value);
                                        } else {
                                            $value = 0;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "double") {
                                        if (is_float($value * 1)) {
                                            $value = (float)$value;
                                        } else {
                                            $value = (float)$value;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "boolean") {
                                        if (is_bool($value)) {
                                            $value = boolval($value);
                                        } else {
                                            $value = false;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = strval($value);
                                        } else {
                                            $value = null;
                                        }
                                        
                                    }
                                    else {
                                        $value = null;
                                    }
 
                                    try {
                                        $row->{$key} = $value;
                                        
                                    } catch(dbException $error){
                                        //echo $error;
                                    }
                                }
                            }
                        }
                    }
                    // Сохраняем изменения
                    $row->save();
 
                    if ($row == 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = $id;
                        $resp["request"]["query"] = "PUT";
                        $resp["request"]["resource"] = $resource;
                    } else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }
 
                } else {
                    // Обновляем несколько записей
                    // Разбираем параметры полученные в теле запроса
                    foreach($arr as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id") {
                                if (array_key_exists($key, $table_config["schema"])) {
 
                                    if ($table_config["schema"][$key] == "integer") {
                                        if (is_numeric($value)) {
                                            $value = intval($value);
                                        } else {
                                            $value = 0;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "double") {
                                        if (is_float($value)) {
                                            $value = floatval($value);
                                        } else {
                                            $value = 0.00;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "boolean") {
                                        if (is_bool($value)) {
                                            $value = boolval($value);
                                        } else {
                                            $value = false;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = strval($value);
                                        } else {
                                            $value = null;
                                        }
                                        
                                    }
                                    else {
                                        $value = null;
                                    }
 
                                    try {
                                        $row->{$key} = $value;
                                        
                                    } catch(dbException $error){
                                        //echo $error;
                                    }
                                }
                            }
                        }
                    }
                    // Сохраняем изменения
                    $row->save();
 
                    if ($row->id >= 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 1;
                        $resp["response"]["id"] = '';
                        $resp["request"]["query"] = "PUT";
                        $resp["request"]["resource"] = $resource;
 
                    } else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }
                }
 
            } catch(dbException $e){
                // Таблица не существует даем ошибку 404
                $resp["headers"]["status"] = '404 Not Found';
                $resp["headers"]["code"] = 404;
                $resp["headers"]["message"] = 'Not Found';
                $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }
 
        } else {
            // Если таблица не определена даем ошибку 400
            $resp["headers"]["status"] = '400 Bad Request';
            $resp["headers"]["code"] = 400;
            $resp["headers"]["message"] = 'Bad Request';
            $resp["headers"]["message_id"] = $this->config["settings"]['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
        }
 
        return $resp;
    
    }
 
    // Требует доработки !
    // Удаление
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        if (isset($resource)) {

            // Проверяем наличие главной базы если нет даем ошибку
            try {
                Validate::table($resource)->exists();
                $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);

                // Если указан id удаляем одну запись
                if ($id >= 1) {
    
                    // Удаляем запись из таблицы
                    $row = jsonDb::table($resource)->find($id)->delete();

                    if ($row == 1) {
                    
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "200 Removed";
                        $resp["headers"]["code"] = 200;
                        $resp["headers"]["message"] = "Removed";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = $id;
                        $resp["request"]["query"] = "DELETE";
                        $resp["request"]["resource"] = $resource;

                    } else {

                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }

                } else {

                    try {
                    
                        $file = $this->get('settings')['db']["dir"].'/'.$resource.'.data.json';
                        // Открываем файл для получения существующего содержимого
                        $current = file_get_contents($file);
                        // Очищаем весь контент оставляем только []
                        $current = "[]";
                        // Пишем содержимое обратно в файл
                        file_put_contents($file, $current);
                        
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "200 Removed";
                        $resp["headers"]["code"] = 200;
                        $resp["headers"]["message"] = "Deleted all rows";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = 'All';
                        $resp["request"]["query"] = "DELETE";
                        $resp["request"]["resource"] = $resource;
                        
                    } catch(dbException $e){
                        
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }
                }
 
            } catch(dbException $e){
                // Таблица не существует даем ошибку 404
                $resp["headers"]["status"] = '404 Not Found';
                $resp["headers"]["code"] = 404;
                $resp["headers"]["message"] = 'Not Found';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }
 
        } else {
            // Если таблица не определена даем ошибку 400
            $resp["headers"]["status"] = '400 Bad Request';
            $resp["headers"]["code"] = 400;
            $resp["headers"]["message"] = 'Bad Request';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
        }
 
        return $resp;
 
    }
 
    // Получить последний идентификатор
    public function last_id($resource)
    {
        return Database::table($resource)->lastId();
    }
 
}
 