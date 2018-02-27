<?php /**
 * This file is part of the RouterDb
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/pllano/router-db
 * @version 1.2.0
 * @package pllano/router-db
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pllano\RouterDb\Pdo;

use Slim\PDO\Database;
use PDO;

class Mysql
{
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

    public function __construct(array $config = [], array $options = [], $other_base = null)
    {
        if (isset($config)) {
			if (isset($other_base)) {
                $db = $config['db'][$other_base];
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

    public function run($sql, $args = null)
    {
        if (!$args) {
             return $this->query($sql);
        }
        $stmt = $this->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }

    // Загрузить
    public function get($resource = null, array $arr = [], $id = null, $field_id = null)
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

    // Искать
    public function search($resource = null, array $query_arr = [], $keyword = null, $field_id = null)
    {
        // Новый запрос, аналог get рассчитан на полнотекстовый поиск
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
 
        // Еще в разработке ...
    }
 
    // Создаем одну запись
    public function post($resource = null, array $arr = [], $field_id = null)
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
    public function put($resource = null, array $arr = [], $id = null, $field_id = null)
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

    // Обновляем
    public function patch($resource = null, array $arr = [], $id = null, $field_id = null)
    {
        $this->resource = $resource;
        if ($this->resource == null) {
            if ($field_id == null) {
                $show = null;
                $resource_id = "id";
                $this->resource_id = "id";
                $show = $this->query("SHOW COLUMNS FROM `".$this->resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show == "id") {
                    $this->resource_id = "id";
                    $resource_id = "id";
                } else {
                    $show = $this->query("SHOW COLUMNS FROM `".$this->resource."` where `Field` = '".$this->resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
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
    public function delete($resource = null, array $arr = [], $id = null, $field_id = null)
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
    public function count($resource = null, array $arr = [], $id = null, $field_id = null)
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
    public function last_id($resource)
    {
        return $this->query("SHOW TABLE STATUS LIKE '".$resource."'")->fetch(PDO::FETCH_ASSOC)['Auto_increment'];
    }

    public function ping($resource = null)
    {
		return 'mysql';
	}

}
 