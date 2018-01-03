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

class JsonDb
{
 
    private $resource = null;
    private $dir = null;
    private $cached = null;
    private $cache_lifetime = null;
    private $temp = null;
    private $api = null;
    private $crypt = null;
 
    public function __construct(array $config = array())
    {
        if (count($config) >= 1){
            if (isset($config["db"]["json"]["dir"])) {
                $this->dir = $config["db"]["json"]["dir"];
            }
            if (isset($config["db"]["json"]["cached"])) {
                $this->cached = $config["db"]["json"]["cached"];
            }
            if (isset($config["db"]["json"]["cache_lifetime"])) {
                $this->cache_lifetime = $config["db"]["json"]["cache_lifetime"];
            }
            if (isset($config["db"]["json"]["temp"])) {
                $this->temp = $config["db"]["json"]["temp"];
            }
            if (isset($config["db"]["json"]["api"])) {
                $this->api = $config["db"]["json"]["api"];
            }
            if (isset($config["db"]["json"]["crypt"])) {
                $this->crypt = $config["db"]["json"]["crypt"];
            }
        }
    }
 
    public function get($resource = null, array $arr = array(), $id = null)
    {

    }
 
    // Создаем одну запись
    public function post($resource = null, array $arr = array())
    {
        
    }
 
    // Обновляем
    public function put($resource = null, array $arr = array(), $id = null)
    {
        
    }
 
    // Удаляем
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        
    }
 
}
 