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
            if (isset($config["dir"])) {
                $this->dir = $config["dir"];
            }
            if (isset($config["cached"])) {
                $this->cached = $config["cached"];
            }
            if (isset($config["cache_lifetime"])) {
                $this->cache_lifetime = $config["cache_lifetime"];
            }
            if (isset($config["temp"])) {
                $this->temp = $config["temp"];
            }
            if (isset($config["api"])) {
                $this->api = $config["api"];
            }
            if (isset($config["crypt"])) {
                $this->crypt = $config["crypt"];
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
 
    