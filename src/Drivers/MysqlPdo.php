<?php /**
 * RouterDb (https://pllano.com)
 *
 * @link https://github.com/pllano/router-db
 * @version 1.2.0
 * @copyright Copyright (c) 2017-2018 PLLANO
 * @license http://opensource.org/licenses/MIT (MIT License)
 */
namespace Pllano\RouterDb\Drivers;

use Pllano\Interfaces\DatabaseInterface;
use Pllano\RouterDb\Router as RouterDb;
use Slim\PDO\Database;
use PDO;

class MysqlPdo implements DatabaseInterface
{
    protected $data;
	protected $format = null;
	protected $resource = null;
	protected $resource_id = null;
	protected $id;
	protected $query;
	protected $sort;
	protected $order;
	protected $offset;
	protected $limit;
	protected $relations;
	protected $connected = null;

    public function __construct(array $config = [], array $options = [], string $format = null, string $prefix = null, $other_base = null)
    {
		$db = [];
		$this->config = $config;
		$this->database = $database;
		$this->options = $options;
        if (isset($format)) {
            $this->format = strtolower($format);
        }
		if (isset($this->config)) {
            if (isset($other_base)) {
                $this->other_base = $other_base;
                $db = $this->config['db'][$this->other_base];
            } elseif (isset($prefix)) {
			    $this->prefix = $prefix;
                $db = $this->config['db'][$this->database.'_'.$this->prefix];
            } else {
                $db = $this->config['db'][$this->database];
            }
        }
        if (isset($db)) {
            $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
            $user = $db['user'];
            $password = $db['password'];
            $default_options = [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];
            $options = array_replace($default_options, $options);
			$this->connected = new Database($dsn, $user, $password, $options);
        }
		return $this->connected ?? null;
    }

    public function get(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        $this->data = [];
		$this->resource = $resource ?? null;
		$this->query = $query ?? [];
		$query = null;
		$this->id = $id ?? null;
		$this->resource_id = "id";
        $this->sort = "id";

        if ($this->resource != null) {
            if ($field_id === null) {
                $show = null;
                $show = $this->query("SHOW COLUMNS FROM `".$this->resource."` where `Field` = 'id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                if ($show != "id") {
                    $show = $this->query("SHOW COLUMNS FROM `".$this->resource."` where `Field` = '".$this->resource."_id'")->fetch(PDO::FETCH_ASSOC)['Field'];
                    if ($show == $this->resource."_id") {
                        $this->resource_id = $this->resource."_id";
                        $this->sort = $this->resource."_id";
                    }
                }
            } else {
                $this->resource_id = $field_id;
                $this->sort = $field_id;
            }
        }

        // $this->data->count = $this->count($this->resource, $this->query, $this->id, $this->resource_id) ?? null;
        $sql = null;

		if (isset($this->id)) {
            if (count($this->query) >= 1) {
                foreach($this->query as $key => $value)
                {
                    if ($key == "relations") {
                        $this->relations = $value;
                    }
                }
            }

            $sql = "SELECT * FROM  `".$this->resource."` WHERE  `".$this->resource_id."` ='".$this->id."' LIMIT 1";

        } else {
            if (count($this->query) >= 1) {
                foreach($this->query as $key => $value)
                {
                    if ($key == ""){$key = null;}
                    if (isset($key) && isset($value)) {
                        if ($key == "sort") {
                            if ($value == "uid" || $value == "id" || $value == $this->resource."_id") {
                                $this->sort = $this->resource_id;
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
                                    $this->data["request"][$key] = $value;
                            } else {
                                if (is_int($value)) {
                                    $query .= "AND `".$key."` ='".$value."' ";
                                } else {
                                    $query .= "AND `".$key."` LIKE '%".$value."%' ";
                                }
                                $this->data["request"][$key] = $value;
                            }
                        }
                    }
                }
            }

            if ($this->offset >= 1){
                $this->offset = $this->offset * $this->limit;
            }
			if (isset($query)) {
            $sql = "
                SELECT * 
                FROM `".$this->resource."` 
                ".$query." 
                ORDER BY `".$this->sort."` ".$this->order." 
                LIMIT ".$this->offset." , ".$this->limit." 
            ";
			}
        }

		if (isset($sql)) {
            // Отправляем запрос в базу
            $stmt = $this->prepare($sql);
            if ($stmt->execute()) {
                // Получаем ответ в виде массива
                $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
		}
        if (isset($this->format)) {
			$this->data = $this->format($this->data, $this->format);
        }
        return $this->data;
    }

    public function search(string $resource = null, array $query = [], string $keyword = null, string $field_id = null)
    {
		return null;
    }

    public function post(string $resource = null, array $query = [], string $field_id = null): int
    {
		$this->data = null;
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
            foreach($query as $key => $unit)
            {
                if ($key == ""){$key = null;}
                if (isset($key) && isset($unit)) {
                    $insert .= ", `".$key."`";
                    $values .= ", '".$unit."'";
                }
            }
        }
        if ($resource != null) {
            $sql = "INSERT INTO `".$resource."` (`".$resource_id."`".$insert.") VALUES ('NULL'".$values.");";
            $stmt = $this->prepare($sql);
            if ($stmt->execute()) {
                $this->data = $this->lastInsertId();
            }
        }
        if (isset($this->format)) {
			$this->data = $this->format($this->data, $this->format);
        }
        return $this->data;
    }
	
    public function put(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        $this->data = null;
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
        // если есть id, тогда в массиве $query данные для одной записи
        if ($id >= 1) {
            if (count($query) >= 1) {
                foreach($query as $key => $value)
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
                $this->data = 1;
            }
        } else {
            $i=0;
            if (count($query) >= 1) {
                foreach($query as $item)
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
            $this->data = $i;
        }
        if (isset($this->format)) {
			$this->data = $this->format($this->data, $this->format);
        }
        // Возвращаем колличество обновленных записей
        return $this->data;
    }

    public function patch(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        $this->data = null;
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
        // если есть id, тогда в массиве $query данные для одной записи
        if ($id >= 1) {
            if (count($query) >= 1) {
                foreach($query as $key => $value)
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
                $this->data = 1;
            }
        } else {
            $i=0;
            if (count($query) >= 1) {
                foreach($query as $item)
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
            $this->data = $i;
        }

        if (isset($this->format)) {
			$this->data = $this->format($this->data, $this->format);
        }
        // Возвращаем колличество обновленных записей
        return $this->data;
 
    }

    public function delete(string $resource = null, int $id = null, string $field_id = null)
    {
        $this->data = null;
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
                    $this->data = 1;
                }
            } else {
                $i=0;
                if (count($query) >= 1) {
                    foreach($query as $item)
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
                    $this->data = $i;
                }
            }
        }
        if (isset($this->format)) {
			$this->data = $this->format($this->data, $this->format);
        }
        // Возвращаем ответ
        return $this->data;
    }

    public function count(string $resource = null, array $query = [], int $id = null, string $field_id = null): int
    {
        $count = null;
		$this->resource_id = $field_id ?? 'id';
		$i=0;
        // Приходится делать запрос и при наличии id, так как может отдать null
        if ($id >= 1) {
            // Формируем запрос к базе данных
            $sql = "SELECT COUNT(*) FROM  `".$resource."` WHERE  `".$this->resource_id."` ='".$id."' LIMIT 1";
        } else {
            $query = "";
            if (count($query) >= 1) {
                foreach($query as $key => $value)
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
            $sql = "SELECT COUNT(*) FROM `".$resource."` ".$query;
        }
        $stmt = $this->prepare($sql);
        if ($stmt->execute()) {
            $count = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $count;
    }

    public function countAll($resource = null, $where = null)
    {
        $num = null;
		if (isset($resource) && isset($where)) {
			$select = 'SELECT COUNT(*) AS `num` FROM `'.$resource.'` WHERE '. $where;
            $row = $this->db->query($select)->fetch();
			$num = $row['num'] ?? null;
		}
        return $num;
    }

    public function lastId(string $resource = null): int
    {
		return (int)$this->query("SHOW TABLE STATUS LIKE '".$resource."'")->fetch(PDO::FETCH_ASSOC)['Auto_increment'] ?? null;
    }

    public function fieldMap($resource = null)
    {
        $fieldMap = null;
        if (isset($resource)) {
             $fieldMap = $this->query('DESCRIBE ' . $resource)->fetchAll(PDO::FETCH_ASSOC);
        }
        return $fieldMap;
    }

    public function tableSchema($resource)
    {
        $fieldMap = $this->fieldMap($resource);
        $table_schema = [];
        foreach($fieldMap as $column)
        {
            $field = $column['Field'];
            $field_type = $column['Type'];
            $table_schema[$field] = $field_type;
        }
        return $table_schema;
    }

    public function setFormat($format = null)
    {
        if (isset($format)) {
            $this->format = $format;
        }
    }

    public function ping(string $resource = null)
    {
		if (isset($resource)) {
		    $sql = "SHOW TABLES '{$resource}' ";
            if ($this->connected) {
                $query = $this->query($sql);
		        $test = $query->fetchAll(PDO::FETCH_COLUMN);
			    if (isset($test['0'])) {
			        return 'mysql';
			    }
            }
		}
        return null;
    }

    public function list_tables()
    {
        $sql = 'SHOW TABLES';
        if ($this->connected) {
            $query = $this->query($sql);
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }
        return false;
    }

    public function format($data, $format = null)
    {
        return $data;
    }

}
 