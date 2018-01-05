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
 
/**
 * Поля ресурса queue
 *
 * "db": "string"
 * "resource": "string"
 * "resource_id": "integer"
 * "request": "string"
 * "request_body": "string"
 */
 
namespace RouterDb;
 
class Queue
{
 
    /**
     * @param $config
     * @var array
    */
    private $config;
 
    /**
     * @param $class_db
     * @var string
    */
    private $class_db;
 
    /**
     * @param $limit
     * @var integer
    */
    private $limit = 5;
 
    /**
     * @param $package
     * @var string
    */
    private $package = "\RouterDb\\";
 
    public function __construct(array $config = array(), $package)
    {
        if (count($config) >= 1){
 
            // Получаем конфигурацию
            $this->config = $config;
            // Получаем название резервной базы
            $db = $config["db"]["slave"];
            // Получаем лимит выполнения запросов из очереди за один раз
            if (isset($config["db"]["queue"]["limit"])) {
                $this->limit = $config["db"]["queue"]["limit"];
            }
            // Формируем название класса резервной базы
            $classDb = "\RouterDb\\".ucfirst($db)."\\".ucfirst($db)."Db";
            $this->class_db = $classDb;
 
        }
 
        if ($package !== null) {
            // Устанавливаем название стороннего пакета
            $this->package = $package;
        }
 
    }
 
    public function run()
    {
        $class_db = $this->class_db;
        $db = new $class_db($this->config);
        $response = $db->get("queue", ["sort" => "id", "order" => "ASC", "offset" => 0, "limit" => $this->limit]);
 
        if (isset($response["header"]["code"])) {
            // Получаем необходимое колличество записей для выполнения указанных в limit
            $count = count($response["body"]["items"]);
            if ($count >= 1) {
                foreach($response["body"]["items"] as $item)
                {
                    // id в queue нужен для удаления записи после выполнения запроса
                    if (isset($item["item"]["id"])) {
                        $id = $item["item"]["id"];
                    } else {
                        $id = null;
                    }
                    // Название базы данных в которую попала запись
                    if (isset($item["item"]["db"])) {
                        $item_db = $item["item"]["db"];
                    } else {
                        $item_db = null;
                    }
                    // Название ресурса в базе
                    if (isset($item["item"]["resource"])) {
                        $resource = $item["item"]["resource"];
                    } else {
                        $resource = null;
                    }
                    // id записи полученной при создании
                    if (isset($item["item"]["resource_id"])) {
                        $resource_id = $item["item"]["resource_id"];
                    } else {
                        $resource_id = null;
                    }
                    // Тип запроса POST, PUT, PATCH, DELETE
                    if (isset($item["item"]["request"])) {
                        $request = $item["item"]["request"];
                    } else {
                        $request = "NULL";
                    }
                    // Копия array запроса
                    if (isset($item["item"]["request_body"])) {
                        $request_body = json_encode(base64_encode($item["item"]["request_body"]), true);
                    } else {
                        $request_body = null;
                    }
                
                    // Получаем название базы для необходимого ресурса
                    if (isset($resource)) {
                        $resource_db = $this->config["resource"][$resource]["db"];
                    } else {
                        $resource_db = null;
                    }
                
                    if ($item_db != null && $resource != null) {
                        if ($request == "POST" && $resource_id != null) {
                            $queueClass = "\RouterDb\\".ucfirst($item_db)."\\".ucfirst($item_db)."Db";
                            $queueDb = new $queueClass($this->config);
                            $resp = $queueDb->get($resource, [], $resource_id);
                            if ($resp["headers"]["code"] == 200){
                                // Получаем все данные записи
                                $arr = $resp["body"]["items"]["0"]["item"];
                                // Получаем класс базы для записи
                                if (isset($resource_db)) {
                                    $postClass = $this->package."".ucfirst($resource_db)."\\".ucfirst($resource_db)."Db";
                                    $postDb = new $postClass($this->config);
                                    // Создаем запись в основной базе
                                    $postResp = $postDb->post($resource, $arr);
                                    if ($postResp["request"]["id"] >= 1){
                                        // После выполнения запроса удаляем запись в queue
                                        $db->delete("queue", [], $id);
                                    }
                                }
                            }
                        } elseif ($request == "PUT" || $request == "PATCH") {
                            // Еще в разработке ...
                        } elseif ($request == "DELETE") {
                            // Еще в разработке ...
                        }
                
                    }
 
                }
 
                // Повторно проверяем колличество запросов в очереди
                // Чтобы уменьшить нагрузку выставляем лимит из конфигурации
                // Чтобы ускорить копирование увеличьте лимит в $config["db"]["queue"]["limit"]
                $response = $db->get("queue", ["offset" => 0, "limit" => $this->limit]);
                $count = count($response["body"]["items"]);
                if ($count >= 1) {
                    // Возвращаем колличество запросов оставшихся в очереди
                    return $count;
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
 
    // Синхронизация основной базы и slave
    // Запускаем синхронизацию из $this->config["resource"][$resource]["db"] в базу данных slave
    // Это мягкая синхронизация которая запишет очередной id из основной базы  
    // Все последующие записи в slave будут иметь id больше чем в основной базе
    // Выполнять саму синхронизацию (копирование) записей будет по несколько при каждом запросе
    // Выполнит указанное в $this->config["db"]["queue"]["limit"] колличество записей за один проход
    // Синхронизация работает по принципу чем чаще ресурс опрашивается тем важнее в нем данные
    public function synchronize($resource)
    {
        if (isset($resource)) {
            // Получаем название базы для необходимого ресурса
            $resource_db = $this->config["resource"][$resource]["db"];
            $class = $this->package."".ucfirst($resource_db)."\\".ucfirst($resource_db)."Db";
            $db = new $class($this->config);
            // Получить последний идентификатор
            $last_id = $db->last_id($resource);
            // Еще в разработке ...
            // Нужно получить last_id в обоих базах ?
            // Нужно записать полученный last_id в базу slave
 
        }
 
    }
 
    // Создаем запись в ресурсе queue база slave
    public function add($request, $db = null, $resource = null, array $arr = array(), $id = null)
    {
 
        if (isset($db)) {
            $array["db"] = $db;
        }
 
        if (isset($request)) {
            $array["request"] = $request;
        }
 
        if (isset($resource)) {
            $array["resource"] = $resource;
        }
 
        if (isset($id)) {
            $array["resource_id"] = $id;
        } else {
            $array["resource_id"] = 0;
        }
 
        if (count($arr) >= 1){
            $array["request_body"] = base64_encode(json_encode($arr));
        } else {
            $array["request_body"] = null;
        }
 
        $class_db = $this->class_db;
        $queueDb = new $class_db($this->config);
        $queueDb->post("queue", $array);
 
    }
}
 