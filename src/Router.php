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
 
class Router
{
 
    private $config;
    private $db = null;
    private $package = "\RouterDb\\";
 
    public function __construct(array $config = array(), $package = null)
    {
        if (count($config) >= 1){
            $this->config = $config;
        }
        if ($package !== null) {
            $this->package = $package;
        }
    }
 
    public function ping($resource = null)
    {
        // Проверяем наличие slave базы и включен ли роутинг
        if ($this->config["db"]["slave"] != false && $this->config["db"]["router"] === true) {
 
		    if ($resource !== null && isset($this->config["db"]["resource"][$resource]["db"])) {
                $this->db = $this->config["db"]["resource"][$resource]["db"];
            } else {
                $this->db = $this->config["db"]["master"];
            }
 
            if ($this->db != null && $resource != null) {
                // Пингуем наличие ресурса в указанной базы данных
                $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Ping";
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
                    $class = $this->package."".ucfirst($this->config["db"]["master"])."\\".ucfirst($this->config["db"]["master"])."Ping";
                    // $class = "\Package\Nameclass\NameclassPing";
                    $db = new $class($this->config);
                    $ping = $db->ping($resource);
                    // Если все ок, вернет название master базы
                    if ($ping == $this->config["db"]["master"]) {
                        return $this->config["db"]["master"];
                    } else {
                        // Если мастер база недоступна пингуем slave базу
                        $class = $this->package."".ucfirst($this->config["db"]["master"])."\\".ucfirst($this->config["db"]["master"])."Ping";
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
		    // Если в конфигурации не указана slave база ["db"]["slave"] = false
		    // Если выключен роутинг ["db"]["router"] = false, Ping также отключен
		    // Берем название базы из конфигурации ресурса, если она не указанна берем название master базы.
		    if ($resource !== null && isset($this->config["db"]["resource"][$resource]["db"])) {
                $this->db = $this->config["db"]["resource"][$resource]["db"];
            } else {
                $this->db = $this->config["db"]["master"];
            }
 
		    return $this->db;
 
		}
    }
 
}
 