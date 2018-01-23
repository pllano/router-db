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
use Elasticsearch\ClientBuilder as Elastic;
 
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
 
    // Загрузить
    public function get($resource = null, array $arr = array(), $id = null)
    {
        if (isset($resource)) {
 
            $client = Elastic::create()->build();
 
            if ($this->type === true) {
                $type = $resource;
                $index = $this->index;
            } else {
                $index = $this->index."_".$resource;
                $type = null;
            }
 
            // если $id определен то это обычный get
            if (isset($id)) {
 
                $params["index"] = $index;
                $params["type"] = $type;
                $params["id"] = $id;
                $params["client"] = ['ignore' => [400, 404, 500]];

                $get = $client->get($params);
 
            } elseif (count($arr) >= 1 && $id === null) {
                // Если мы получили массив $arr то это search
                
                $client->search($params);
 
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
 
    // Искать
    public function search($resource = null, array $query_arr = array(), $keyword = null)
    {
        $client = Elastic::create()->build();
        // Здесь будет много кода с маневрами :)
        $client->search($params);
    }
 
    // Создаем одну запись
    public function post($resource = null, array $arr = array())
    {
        $client = Elastic::create()->build();
 
        $params["index"] = $this->index;
        $params["type"] = $this->type;
        if (isset($id)) {
            $params["id"] = $id;
        }
        $params["client"] = ['ignore' => [400, 404, 500]];
        
        if (count($arr) >= 1) {
            foreach($arr as $key => $value)
            {
                if (isset($key) && isset($unit)) {
                    $params["body"][$key] = $value;
                }
            }
        }
 
        $client->index($params);
 
    }
 
    // Обновляем
    public function put($resource = null, array $arr = array(), $id = null)
    {
        if (isset($resource)) {
            $client = Elastic::create()->build();
            
            if ($this->type === true) {
                $type = $resource;
                $index = $this->index;
            } else {
                $index = $this->index."_".$resource;
                $type = null;
            }
 
            if (isset($id)) {
                $params["index"] = $index;
                $params["type"] = $type;
                $params["id"] = $id;
                $params["client"] = ['ignore' => [400, 404, 500]];
        
                if (count($arr) >= 1) {
                    foreach($arr as $key => $value)
                    {
                        if (isset($key) && isset($unit)) {
                            $params["body"]["doc"][$key] = $value;
                        }
                    }
                }
 
                $client->update($params);
            }
        }
    }
    
    // Обновляем
    public function patch($resource = null, array $arr = array(), $id = null)
    {
        if (isset($resource)) {
            $client = Elastic::create()->build();
            
            if ($this->type === true) {
                $type = $resource;
                $index = $this->index;
            } else {
                $index = $this->index."_".$resource;
                $type = null;
            }
 
            if (isset($id)) {
                $params["index"] = $index;
                $params["type"] = $type;
                $params["id"] = $id;
                $params["client"] = ['ignore' => [400, 404, 500]];
        
                if (count($arr) >= 1) {
                    foreach($arr as $key => $value)
                    {
                        if (isset($key) && isset($unit)) {
                            $params["body"]["doc"][$key] = $value;
                        }
                    }
                }
 
                $client->update($params);
            }
        }
    }
 
    // Удаляем
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        if (isset($resource)) {
            $client = Elastic::create()->build();
            
            if ($this->type === true) {
                $type = $resource;
                $index = $this->index;
            } else {
                $index = $this->index."_".$resource;
                $type = null;
            }
 
            if ($id >= 1) {
                $params["index"] = $index;
                if (isset($type)) {
                    $params["type"] = $type;
                }
                $params["id"] = $id;
                $params["client"] = ['ignore' => [400, 404, 500]];
 
                $client->delete($params);
 
            } elseif (count($arr) >= 1) {
                foreach($arr as $value)
                {
                    // ПЕРЕПИСАТЬ !!!!!!
                    if (isset($value["id"])) {
                        $params["index"] = $index;
                        if (isset($type)) {
                            $params["type"] = $type;
                        }
                        $params["id"] = $value["id"];
                        $params["client"] = ['ignore' => [400, 404, 500]];
 
                        $client->delete($params);
                    }
                }
            } else {
               return null;
            }
        } else {
            return null;
        }
    }
 
    // Получить последний идентификатор
    public function last_id($resource)
    {
        // Здесь есть проблема !
        // В Elasticsearch id не являются целым числом
        // Возникнут проблемы с синхронизацией записей
        // Сейчас думаем как решить этот вопрос
    }
 
}
 
