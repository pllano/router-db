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
 
namespace RouterDb\Elasticsearch;

use RouterDb\Utility;
use GuzzleHttp\Client as Guzzle;
 
/**
 * ElasticsearchDb
*/
class ElasticsearchDb
{
    
    private $resource = null;
    private $host = null;
    private $port = null;
    private $type = null;
    private $index = null;
    private $auth = null;
    private $user = null;
    private $password = null;
 
    public function __construct(array $config = array())
    {
        if (count($config) >= 1){
            if (isset($config["host"])) {
                $this->host = $config["host"];
            }
            if (isset($config["port"])) {
                $this->port = $config["port"];
            }
            if (isset($config["type"])) {
                $this->type = $config["type"];
            }
            if (isset($config["index"])) {
                $this->index = $config["index"];
            }
            if (isset($config["auth"])) {
                $this->auth = $config["auth"];
            }
            if (isset($config["user"])) {
                $this->user = $config["user"];
            }
            if (isset($config["password"])) {
                $this->password = $config["password"];
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
 
