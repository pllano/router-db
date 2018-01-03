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
        if ($resource !== null && isset($this->config["resource"][$resource]["db"])) {
            $this->db = $this->config["resource"][$resource]["db"];
        } else {
            $this->db = $this->config["db"]["master"];
        }
 
        if ($this->db != null && $resource != null) {
            // Пингуем наличие ресурса в указанной базы данных
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Ping";
            //print_r($this->config);
            $db = new $class($this->config);
            $ping = $db->ping($resource);
            // Вернет название ресурса или null
            if ($ping = $resource) {
                // Если все ок вернет название $resource
                return $this->config["resource"][$resource]["db"];
            } else {
                // Если ресурс недоступен вернет null или другой ответ
                // Тогда пингуем master и slave базы
                $class = $this->package."".ucfirst($this->config["db"]["master"])."\\".ucfirst($this->config["db"]["master"])."Ping";
                $db = new $class($this->config);
                $ping = $db->ping($resource);
                // Если все ок, вернет название master базы
                if ($ping == $this->config["db"]["master"]) {
                    return $this->config["db"]["master"];
                } else {
                    // Если мастер база недоступна пингуем slave базу
                    $class = $this->package."".ucfirst($this->config["db"]["master"])."\\".ucfirst($this->config["db"]["master"])."Ping";
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
    }
 
}
 