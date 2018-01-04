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
    
    // GET запросы не пишутся в очередь запросов
    public function get($resource = null, array $arr = array(), $id = null)
    {
 
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
 
            // Формируем название транзитного класса базы данных
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
 
            // Формируем конфигурацию
            $configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
            $configArr["db"][$this->db] = $this->config["db"][$this->db];
            if ($this->db != "json"){
                $configArr["db"]["json"] = $this->config["db"]["json"];
            }
 
            // Подключаемся к базе данных
            $db = new $class($configArr);
            // Отправляем запрос и получаем ответ
            $response = $db->get($resource, $arr, $id);
 
            // Возвращаем ответ
            return $response;
 
        } else {
            return null;
        }
    }
 
    public function search($resource = null, array $arr = array(), $search = null)
    {
        // Новый запрос, аналог get рассчитан на полнотекстовый поиск
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
 
        // В разработке !!!
        return null;
 
    }
 
    public function post($resource = null, array $arr = array())
    {
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
            // Получаем конфигурацию
            $configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
            $configArr["db"][$this->db] = $this->config["db"][$this->db];
            if ($this->db != "json"){
                $configArr["db"]["json"] = $this->config["db"]["json"];
            }
     
            // Подключаем контроллер очереди запросов
            $queue = new Queue($this->config);
 
            // Проверяем наличие невыполненных запросов в очереди
            // Выполнит до 5 запросов, самых давних по дате если нужная таблица в выбранной базе доступна
            // Вернет колличество оставшихся запросов или null если запросов в очереди нет
            $count = $queue->run();
 
            if ($count == null) {
 
                // Выполняем необходимый запрос
                // Формируем класс через который будем работать
                $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
                // Полключаемся к базе
                $db = new $class($configArr);
                // Отправляем запрос и получаем ответ
                $response = $db->post($resource, $arr);

                // Проверяем совпадает ли база полученная от Router с записанной в конфиге
                // Получаем название базы для $resource из конфига
                $resource_db = $this->config["resource"][$resource]["db"];
                // Если название базы не совпадает пишем копию запроса в очередь
                if ($this->db != $resource_db) {
                    // Получаем id созданной записи
                    $id = $response["response"]["id"];
                    // Получаем название базы куда попала запись
                    $db = $this->db;
                    // Пишем в очередь на выполнение
                    $queue->add("POST", $db, $resource, $id);
                }
 
                // Проверим очередь повторно
                $count = $queue->run();
                if ($count == null) {
                    // Запускаем синхронизацию баз slave и $this->config["resource"][$resource]["db"]
                    // Это мягкая синхронизация которая запишет в slave базу очередной id из основной базы
                    // Таким образом все последующие записи в slave будут иметь id больше чем в основной базе
                    // Выполнять саму синхронизацию (копирование) записей будет по несколько при каждом запросе
                    $queue->synchronize();
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
 
            // Формируем название транзитного класса базы данных
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
 
            // Формируем конфигурацию
            $configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
            $configArr["db"][$this->db] = $this->config["db"][$this->db];
            if ($this->db != "json"){
                $configArr["db"]["json"] = $this->config["db"]["json"];
            }
            // Подключаемся к базе данных
            $db = new $class($configArr);
            // Отправляем запрос и получаем ответ
            $response = $db->put($resource, $arr, $id);
 
            // Возвращаем ответ
            return $response;
 
        } else {
            return null;
        }
    }
    
    public function patch($resource = null, array $arr = array(), $id = null)
    {
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
 
            // Формируем название транзитного класса базы данных
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
 
            // Формируем конфигурацию
            $configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
            $configArr["db"][$this->db] = $this->config["db"][$this->db];
            if ($this->db != "json"){
                $configArr["db"]["json"] = $this->config["db"]["json"];
            }
            // Подключаемся к базе данных
            $db = new $class($configArr);
            // Отправляем запрос и получаем ответ
            $response = $db->patch($resource, $arr, $id);
 
            // Возвращаем ответ
            return $response;
 
        } else {
            return null;
        }
    }
 
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        // Если база данных и ресурс не равняются null
        if ($this->db !== null && $resource !== null) {
 
            // Формируем название транзитного класса базы данных
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
 
            // Формируем конфигурацию
            $configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
            $configArr["db"][$this->db] = $this->config["db"][$this->db];
            if ($this->db != "json"){
                $configArr["db"]["json"] = $this->config["db"]["json"];
            }
            // Подключаемся к базе данных
            $db = new $class($configArr);
            // Отправляем запрос и получаем ответ
            $response = $db->delete($resource, $arr, $id);
 
            // Возвращаем ответ
            return $response;
 
        } else {
            return null;
        }
    }
 
}
 