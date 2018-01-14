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
 
namespace RouterDb;
 
use RouterDb\Queue;
 
class Db
{
 
    /**
     * @param $db name
     * @var string
    */
    private $db = null;
 
    /**
     * @param $config
     * @var array
    */
    private $config;
 
    /**
     * @param $package
     * @var string
    */
    private $package = "\RouterDb\\";
 
    public function __construct($db = null, array $config = array(), $package = null)
    {
        if ($db !== null) {
            // Устанавливаем название базы данных
            $this->db = $db;
        }
        if (count($config) >= 1){
            // Устанавливаем конфигурацию
            $this->config = $config;
        }
        if ($package !== null) {
            // Устанавливаем название стороннего пакета
            $this->package = $package;
        }
    }
 
    public function get($resource = null, array $arr = array(), $id = null)
    {
 
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
 
            // Формируем название транзитного класса базы данных
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
            // $class = "\Package\Nameclass\NameclassDb";
            // Подключаемся к базе данных
            $db = new $class($this->config);
            // Отправляем запрос и получаем ответ
            $response = $db->get($resource, $arr, $id);
 
            if ($this->config["db"]["slave"] != false && $this->config["db"]["queue"]["status"] === true) {
                // Подключаем контроллер очереди запросов
                $queue = new Queue($this->config, $this->package);
                $count = $queue->run();
                if ($count === null) {
                    // Запускаем синхронизацию из $this->config["resource"][$resource]["db"] в базу данных slave
                    // Это мягкая синхронизация которая запишет очередной id из основной базы  
                    // Все последующие записи в slave будут иметь id больше чем в основной базе
                    // Выполнять саму синхронизацию (копирование) записей будет по несколько при каждом запросе
                    // Выполнит указанное в $this->config["db"]["queue"]["limit"] колличество записей за один проход
                    // Синхронизация работает по принципу чем чаще ресурс опрашивается тем важнее в нем данные
                    $queue->synchronize($resource);
                }
            }
 
            // Возвращаем ответ
            return $response;
 
        } else {
 
            return null;
 
        }
    }
 
    public function search($resource = null, array $query_arr = array(), $keyword = null)
    {
 
        // Новый запрос, аналог get рассчитан на полнотекстовый поиск
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
 
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
 
            // Формируем название транзитного класса базы данных
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
            // $class = "\Package\Nameclass\NameclassDb";
            // Подключаемся к базе данных
            $db = new $class($this->config);
            // Отправляем запрос и получаем ответ
            $response = $db->search($resource, $query_arr, $keyword);
 
            // Возвращаем ответ
            return $response;
 
        } else {
 
            return null;
 
        }
 
    }
 
    public function post($resource = null, array $arr = array())
    {
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
 
            // Подключаем контроллер очереди запросов
            $queue = new Queue($this->config, $this->package);
 
            // Проверяем наличие невыполненных запросов в очереди
            // Выполнит до 5 запросов, самых давних по дате если нужная таблица в выбранной базе доступна
            // Вернет колличество оставшихся запросов или null если запросов в очереди нет
            $count = $queue->run();
            if ($count === null) {
 
                // Формируем класс через который будем работать
                $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
                // $class = "\Package\Nameclass\NameclassDb";
                // Полключаемся к базе
                $db = new $class($this->config);
                // Отправляем запрос и получаем ответ
                $response = $db->post($resource, $arr);
 
                // Проверяем совпадает ли база полученная от Router с записанной в конфиге
                // Получаем название базы для $resource из конфига
                $resource_db = $this->config["db"]["resource"][$resource]["db"];
                // Если название базы не совпадает с основной, пишем копию запроса в очередь
                if ($this->db != $resource_db) {
                    // Получаем id созданной записи
                    $id = $response["response"]["id"];
                    // Получаем название базы куда попала запись
                    $db = $this->db;
                    // Пишем в очередь на выполнение
                    $queue->add("POST", $db, $resource, $id);
                }
 
                // Проверим очередь повторно
                // Если отдаст null это будет означать что текущая запись прошла в основную базу и можно начинать синхронизацию
                // Если ресурс queue пустой обработка будет мгновенной и не повлияет на скорость работы
                // Если в ресурсе queue будут записи то мы выбираем баланс между стабильностью работы и скоростью
                $count = $queue->run();
                if ($count === null) {
                    // Запускаем синхронизацию из $this->config["db"]["resource"][$resource]["db"] в базу данных slave
                    // Это мягкая синхронизация которая запишет очередной id из основной базы  
                    // Таким образом все последующие записи в slave будут иметь id больше чем в основной базе
                    // Выполнять саму синхронизацию (копирование) записей будет по несколько при каждом запросе
                    // Выполнит указанное в $this->config["db"]["queue"]["limit"] колличество записей за один проход
                    $queue->synchronize($resource);
                }
 
                // Возвращаем ответ
                return $response;
 
            } else {
                // Если в очереди еще остались невыполненные запросы
                // Мы вынуждены писать сразу в резервную базу
 
                // Формируем конфигурацию
                $configSlave["db"][$this->config["db"]["slave"]] = $this->config["db"][$this->config["db"]["slave"]];
                $configSlave["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
                if ($this->config["db"]["slave"] != "json"){
                    $configSlave["db"]["json"] = $this->config["db"]["json"];
                }
 
                // Формируем название класса slave базы
                $slaveClass = "\RouterDb\\".ucfirst($this->config["db"]["slave"])."\\".ucfirst($this->config["db"]["slave"])."Db";
                // Подключаемся к базе
                $slave = new $slaveClass($configSlave);
                // Отправляем запрос и получаем ответ
                $response = $slave->post($resource, $arr);
 
                // Получаем id созданной записи
                $id = $response["response"]["id"];
                // Получаем название базы куда попала запись
                $db = $this->db;
                // Пишем в очередь на выполнение
                $queue->add("POST", $db, $resource, $id);
 
                // Возвращаем ответ
                return $response;
 
            }
 
        } else {
 
            return null;
 
        }
    }
    
    public function put($resource = null, array $arr = array(), $id = null)
    {
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
 
            // Подключаем контроллер очереди запросов
            $queue = new Queue($this->config, $this->package);
 
            // Проверяем наличие невыполненных запросов в очереди
            // Выполнит до 5 запросов, самых давних по дате если нужная таблица в выбранной базе доступна
            // Вернет колличество оставшихся запросов или null если запросов в очереди нет
            $count = $queue->run();
            if ($count === null) {
 
                // Формируем класс через который будем работать
                $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
                // $class = "\Package\Nameclass\NameclassDb";
                // Полключаемся к базе
                $db = new $class($this->config);
                // Отправляем запрос и получаем ответ
                $response = $db->put($resource, $arr, $id);
 
                // Проверяем совпадает ли база полученная от Router с записанной в конфиге
                // Получаем название базы для $resource из конфига
                $resource_db = $this->config["db"]["resource"][$resource]["db"];
                // Если название базы не совпадает пишем копию запроса в очередь
                if ($this->db != $resource_db) {
 
                    // Получаем название базы куда попала запись
                    $db = $this->db;
                    // Пишем в очередь на выполнение
                    $queue->add("PUT", $db, $resource, $arr, $id);
                }
 
                // Проверим очередь повторно
                $count = $queue->run();
                if ($count === null) {
                    // Запускаем синхронизацию slave базы данных и $this->config["db"]["resource"][$resource]["db"]
                    // Это мягкая синхронизация которая запишет очередной id из основной базы
                    // Таким образом все последующие записи в slave будут иметь id больше чем в основной базе
                    // Выполнять саму синхронизацию (копирование) записей будет по несколько при каждом запросе
                    $queue->synchronize($resource);
                }
 
                // Возвращаем ответ
                return $response;
 
            } else {
                // Если в очереди еще остались невыполненные запросы
                // Мы вынуждены писать сразу в резервную базу
 
                // Формируем конфигурацию
                $configSlave["db"][$this->config["db"]["slave"]] = $this->config["db"][$this->config["db"]["slave"]];
                $configSlave["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
                if ($this->config["db"]["slave"] != "json"){
                    $configSlave["db"]["json"] = $this->config["db"]["json"];
                }
 
                // Формируем название класса slave базы
                $slaveClass = "\RouterDb\\".ucfirst($this->config["db"]["slave"])."\\".ucfirst($this->config["db"]["slave"])."Db";
                // $class = "\Package\Nameclass\NameclassDb";
                // Подключаемся к базе
                $slave = new $slaveClass($configSlave);
                // Отправляем запрос и получаем ответ
                $response = $slave->put($resource, $arr, $id);
 
                // Получаем название базы куда попала запись
                $db = $this->db;
                // Пишем в очередь на выполнение
                $queue->add("PUT", $db, $resource, $arr, $id);
 
                // Возвращаем ответ
                return $response;
 
            }
 
        } else {
 
            return null;
 
        }
    }
    
    public function patch($resource = null, array $arr = array(), $id = null)
    {
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
 
            // Подключаем контроллер очереди запросов
            $queue = new Queue($this->config, $this->package);
 
            // Проверяем наличие невыполненных запросов в очереди
            // Выполнит до 5 запросов, самых давних по дате если нужная таблица в выбранной базе доступна
            // Вернет колличество оставшихся запросов или null если запросов в очереди нет
            $count = $queue->run();
            if ($count === null) {
 
                // Формируем класс через который будем работать
                $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
                // $class = "\Package\Nameclass\NameclassDb";
                // Полключаемся к базе
                $db = new $class($this->config);
                // Отправляем запрос и получаем ответ
                $response = $db->patch($resource, $arr, $id);
 
                // Проверяем совпадает ли база полученная от Router с записанной в конфиге
                // Получаем название базы для $resource из конфига
                $resource_db = $this->config["db"]["resource"][$resource]["db"];
                // Если название базы не совпадает пишем копию запроса в очередь
                if ($this->db != $resource_db) {
 
                    // Получаем название базы куда попала запись
                    $db = $this->db;
                    // Пишем в очередь на выполнение
                    $queue->add("PATCH", $db, $resource, $arr, $id);
                }
 
                // Проверим очередь повторно
                $count = $queue->run();
                if ($count === null) {
                    // Запускаем синхронизацию slave базы данных и $this->config["db"]["resource"][$resource]["db"]
                    // Это мягкая синхронизация которая запишет очередной id из основной базы
                    // Таким образом все последующие записи в slave будут иметь id больше чем в основной базе
                    // Выполнять саму синхронизацию (копирование) записей будет по несколько при каждом запросе
                    $queue->synchronize($resource);
                }
 
                // Возвращаем ответ
                return $response;
 
            } else {
                // Если в очереди еще остались невыполненные запросы
                // Мы вынуждены писать сразу в резервную базу
 
                // Формируем конфигурацию
                $configSlave["db"][$this->config["db"]["slave"]] = $this->config["db"][$this->config["db"]["slave"]];
                $configSlave["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
                if ($this->config["db"]["slave"] != "json"){
                    $configSlave["db"]["json"] = $this->config["db"]["json"];
                }
 
                // Формируем название класса slave базы
                $slaveClass = "\RouterDb\\".ucfirst($this->config["db"]["slave"])."\\".ucfirst($this->config["db"]["slave"])."Db";
                // $class = "\Package\Nameclass\NameclassDb";
                // Подключаемся к базе
                $slave = new $slaveClass($configSlave);
                // Отправляем запрос и получаем ответ
                $response = $slave->patch($resource, $arr, $id);
 
                // Получаем название базы куда попала запись
                $db = $this->db;
                // Пишем в очередь на выполнение
                $queue->add("PATCH", $db, $resource, $arr, $id);
 
                // Возвращаем ответ
                return $response;
 
            }
 
        } else {
 
            return null;
 
        }
    }
 
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
 
            // Подключаем контроллер очереди запросов
            $queue = new Queue($this->config, $this->package);
 
            // Проверяем наличие невыполненных запросов в очереди
            // Выполнит до 5 запросов, самых давних по дате если нужная таблица в выбранной базе доступна
            // Вернет колличество оставшихся запросов или null если запросов в очереди нет
            $count = $queue->run();
            if ($count === null) {
 
                // Формируем класс через который будем работать
                $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
                // $class = "\Package\Nameclass\NameclassDb";
                // Полключаемся к базе
                $db = new $class($this->config);
                // Отправляем запрос и получаем ответ
                $response = $db->delete($resource, $arr, $id);
 
                // Проверяем совпадает ли база полученная от Router с записанной в конфиге
                // Получаем название базы для $resource из конфига
                $resource_db = $this->config["db"]["resource"][$resource]["db"];
                // Если название базы не совпадает пишем копию запроса в очередь
                if ($this->db != $resource_db) {
 
                    // Получаем название базы куда попала запись
                    $db = $this->db;
                    // Пишем в очередь на выполнение
                    $queue->add("DELETE", $db, $resource, $arr, $id);
                }
 
                // Проверим очередь повторно
                $count = $queue->run();
                if ($count === null) {
                    // Запускаем синхронизацию slave базы данных и $this->config["db"]["resource"][$resource]["db"]
                    // Это мягкая синхронизация которая запишет очередной id из основной базы
                    // Таким образом все последующие записи в slave будут иметь id больше чем в основной базе
                    // Выполнять саму синхронизацию (копирование) записей будет по несколько при каждом запросе
                    $queue->synchronize($resource);
                }
 
                // Возвращаем ответ
                return $response;
 
            } else {
                // Если в очереди еще остались невыполненные запросы
                // Мы вынуждены писать сразу в резервную базу
 
                // Формируем конфигурацию
                $configSlave["db"][$this->config["db"]["slave"]] = $this->config["db"][$this->config["db"]["slave"]];
                $configSlave["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
                if ($this->config["db"]["slave"] != "json"){
                    $configSlave["db"]["json"] = $this->config["db"]["json"];
                }
 
                // Формируем название класса slave базы
                $slaveClass = "\RouterDb\\".ucfirst($this->config["db"]["slave"])."\\".ucfirst($this->config["db"]["slave"])."Db";
                // $class = "\Package\Nameclass\NameclassDb";
                // Подключаемся к базе
                $slave = new $slaveClass($configSlave);
                // Отправляем запрос и получаем ответ
                $response = $slave->delete($resource, $arr, $id);
 
                // Получаем название базы куда попала запись
                $db = $this->db;
                // Пишем в очередь на выполнение
                $queue->add("DELETE", $db, $resource, $arr, $id);
 
                // Возвращаем ответ
                return $response;
 
            }
 
        } else {
 
            return null;
 
        }
    }
 
    // Получить последний идентификатор
    public function last_id($resource)
    {
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
            // Формируем класс через который будем работать
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
            // $class = "\Package\Nameclass\NameclassDb";
            // Полключаемся к базе
            $db = new $class($this->config);
            // Отправляем запрос и получаем last_id
            // Возвращаем last_id без параметров
            return $db->last_id($resource);
        } else {
            return null;
        }
    }
}
 
