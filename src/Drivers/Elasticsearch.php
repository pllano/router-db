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
 
namespace Pllano\RouterDb\Apis;

use Pllano\RouterDb\Utility;
use GuzzleHttp\Client as Guzzle;
use Elasticsearch\ClientBuilder as Elastic;
 
/**
 * ElasticsearchDb
*/
class Elasticsearch
{

    private $client;
    private $resource = null;
    private $host = null;
    private $port = null;
    private $type = null;
    private $index = null;
    private $auth = null;
    private $user = null;
    private $password = null;
 
    public function __construct(array $config = [], array $options = [], string $prefix = null, $other_base = null)
    {
        if (isset($config)) {
            if (isset($prefix)) {
                $db = $config['db']['elasticsearch_'.$prefix];
            } else {
                $db = $config['db']['elasticsearch'];
            }

            $this->config = $db;
            
            if (isset($this->config["host"])) {
                $this->host = $this->config["host"];
            }
            if (isset($this->config["port"])) {
                $this->port = $this->config["port"];
            }
            if (isset($this->config["type"])) {
                $this->type = $this->config["type"];
            }
            if (isset($this->config["index"])) {
                $this->index = $this->config["index"];
				}
            if (isset($config["auth"])) {
                $this->auth = $this->config["auth"];
            }
            if (isset($this->config["user"])) {
                $this->user = $this->config["user"];
            }
            if (isset($this->config["password"])) {
                $this->password = $this->config["password"];
            }
        }

        $hosts = ['http://'.$this->user.':'.$this->password.'@'.$this->host.':'.$this->port.''];
        $this->client = Elastic::create()->setHosts($hosts)->build();

    }

    public function api($data)
    {
        return $data;
    }

    public function pdo($data)
    {
        return $data;
    }

    public function apis($data)
    {
        return $data;
    }

    public function ping($resource = null)
    {
            return "elasticsearch";
    }

    public function get(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {

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

                $get = $this->client->get($params);
 
            } elseif (count($arr) >= 1 && $id === null) {
                // Если мы получили массив $arr то это search
                
                $this->client->search($params);
 
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public function search(string $resource = null, string $keyword = null, array $query = [], string $field_id = null)
    {
        // Здесь будет много кода с маневрами :)
        $this->client->search($params);
    }

    public function post(string $resource = null, array $query = [], string $field_id = null): int
    {

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
 
        $this->client->index($params);
 
    }
 
    public function put(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {

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
 
                $this->client->update($params);
            }
        }
    }
    
    public function patch(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {

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
 
                $this->client->update($params);
            }
        }
    }
 
    public function delete(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {

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
 
                $this->client->delete($params);
 
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
 
                        $this->client->delete($params);
                    }
                }
            } else {
               return null;
            }
        } else {
            return null;
        }
    }
 
    public function count(string $resource = null, array $query = [], int $id = null, string $field_id = null): int
    {

	}

    public function last_id($resource)
    {
        return null;
    }

    public function fieldMap($resource = null)
    {
        return [];
    }

    public function tableSchema($table)
    {
        $fieldMap = $this->fieldMap($table);
        $table_schema = [];
        foreach($fieldMap as $key => $val)
        {
            $table_schema[$key] = $val;
        }
        
        return $table_schema;
    }

    static public function selectDate($minutes = null)
    {
        return "0000-00-00 00:00:00";
    }

}
 