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
// В работе
// https://github.com/nette/database
// https://github.com/zendframework/zend-db
// https://github.com/doctrine/dbal
// https://github.com/FaaPz/Slim-PDO

namespace Pllano\RouterDb;

class Router
{
    /**
     * @param $db name
     * @var string
    */
    private $db = "mysql";
    /**
     * @param $router
     * @var string
    */
    private $namespace = "\\Pllano\\RouterDb";
    /**
     * @param $adapter
     * @var string
    */
    private $adapter = "Pdo";
    /**
     * @param $driver
     * @var string
    */
    private $driver = '';
    /**
     * @param other base $prefix
     * @var string
    */
    private $prefix = null;
    /**
     * @param $config
     * @var array
    */
    private $config = [
        "db" => [
            "mysql" => [
                "host" => "localhost"
            ],
            "json" => [
                "host" => "localhost"
            ]
        ]
    ];
    private $logger = null;
    private $mailer = null;

    public function __construct(array $config = [], $adapter = null, $driver = null, $db = null, $prefix = null, $namespace = null)
    {
        if (isset($config)) {
            // Конфигурация
            $this->config = $config;
        } elseif (file_exists(__DIR__ . 'config.json')) {
            $this->config = json_decode(file_get_contents(__DIR__ . 'config.json'), true);
        }
        if (isset($adapter)) {
            // Адаптер
            $this->adapter = $adapter;
        }
        if (isset($adapter)) {
            // Драйвер
            $this->driver = $driver;
        }
        if (isset($db)) {
            // Тип базы данных
            $this->db = $db;
        }
        if (isset($prefix)) {
            // База с другим названием
            $this->prefix = $other_base;
        }
        if (isset($namespace)) {
            // Пространство имен
            $this->namespace = $namespace;
        }
    }

    public function ping($resource = null)
    {
        // Проверяем наличие slave базы и включен ли роутинг
        if ($this->config["db"]["slave"] != null && $this->config["db"]["router"] == '1') {
 
            if ($resource !== null && isset($this->config["db"]["resource"][$resource]["db"])) {
                $this->db = $this->config["db"]["resource"][$resource]["db"];
            } else {
                $this->db = $this->config["db"]["master"];
            }

            $_db = ucfirst(strtolower($this->db));
            $_adapter = ucfirst(strtolower($this->adapter));

            if ($this->db != null && $resource != null) {
                // Пингуем наличие ресурса в указанной базы данных
                $class = $this->namespace."\\".$_adapter."\\".$_db;
                // $class = "\Package\Nameclass\NameclassPing";
                $db = new $class($this->config);
                $ping = $db->ping($resource);
                // Вернет название ресурса или null
                if ($ping == $this->config["db"]["resource"][$resource]["db"]) {
                    // Если все ок вернет название $resource
                    return $this->config["db"]["resource"][$resource]["db"];
                } else {
                    // Если ресурс недоступен вернет null или другой ответ
                    // Тогда пингуем master и slave базы
                    $class = $this->namespace."\\".$_adapter."\\".$_db."".$this->driver;
                    // $class = "\Package\Nameclass\NameclassPing";
                    $db = new $class($this->config);
                    $ping = $db->ping($resource);
                    // Если все ок, вернет название master базы
                    if ($ping == $this->config["db"]["master"]) {
                        return $this->config["db"]["master"];
                    } else {
                        // Если мастер база недоступна пингуем slave базу
                        $class = $this->namespace."\\".$_adapter."\\".$_db."".$this->driver;
                        // $class = "\Package\Nameclass\NameclassPing";
                        $db = new $class($this->config);
                        $ping = $db->ping($resource);
                        if ($ping == $this->config["db"]["slave"]) {
                            return $this->config["db"]["slave"];
                        } else {
                            return null;
                        }
                    }
                }
            } else {
                return null;
            }
        } else {
            // Берем название базы из конфигурации ресурса, если она не указанна берем название master базы.
            if ($resource !== null && isset($this->config["db"]["resource"][$resource]["db"])) {
            $this->db = $this->config["db"]["resource"][$resource]["db"];
            } else {
                $this->db = $this->config["db"]["master"];
            }
            return $this->db;
        }

    }

    // Формируем название класса базы данных
    // Передаем класу параметры и возвращаем его интерфейс
    // return new \Pllano\RouterDb\Pdo\MysqlPdo($this->config, $this->adapter, $this->driver);
    // return new \Pllano\RouterDb\Pdo\MysqlSlimPdo($this->config, $this->adapter, $this->driver);
    // return new \Pllano\RouterDb\Apis\Mysql($this->config, $this->adapter, $this->driver);
    // return new \Pllano\RouterDb\Apis\Api($this->config, $this->adapter, $this->driver);
    // return new \Pllano\RouterDb\Apis\Json($this->config, $this->adapter, $this->driver);
    public function run($db = null, array $options = [], $prefix = null)
    {
        if (isset($db)) {
            $this->db = $db;
        }
        if (isset($prefix)) {
            $this->prefix = $prefix;
        }
        $_db = ucfirst(strtolower($this->db));
        $class = $this->namespace."\\".$this->adapter."\\".$_db."".$this->driver;
        return new $class($this->config, $options, $this->prefix);
    }
    
    // Установить logger
    public function setLogger($logger = null)
    {
        if (isset($logger)) {
            $this->logger = $logger;
        }
    }

    // Установить mailer
    public function setMailer($mailer = null)
    {
        if (isset($mailer)) {
            $this->mailer = $mailer;
        }
    }

    // Установить название базы данных
    public function setDb($db = null)
    {
        if (isset($db)) {
            $this->db = $db;
        }
    }

    // Установить пространство имен
    public function setNamespace($namespace = null)
    {
        if (isset($namespace)) {
            $this->namespace = $namespace;
        }
    }

    // Установить название адаптера
    public function setAdapter($adapter = null)
    {
        if (isset($adapter)) {
            $this->adapter = $adapter;
        }
    }

    // Получить название базы данных
    public function getDb($db = null)
    {
        return $this->db ?? null;
    }

    // Получить пространство имен
    public function getNamespace($namespace = null)
    {
        return $this->namespace ?? null;
    }

    // Получить название адаптера
    public function getAdapter($adapter = null)
    {
        return $this->adapter ?? null;
    }

/*     public function __get($name)
    {
        return $this->_data[$name] ?? null;
    }
    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }
    public function __isset($name)
    {
        return (isset($this->_data[$name]));
    } */

}
 