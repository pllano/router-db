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
use Pllano\RouterDb\Ex;
 
class Api
{

    private $client = null;
    private $resource = null;
    private $url = null;
    private $auth = null;
    private $api = null;
    private $public_key = null;
    private $config;

    public function __construct(array $config = [], array $options = [], string $prefix = null, $other_base = null)
    {
        if (isset($config)) {
            if (isset($prefix)) {
                $db = $config['db']['api_'.$prefix];
            } else {
                $db = $config['db']['api'];
            }

            $this->config = $db;

            if (isset($this->config["config"])) {
                $this->api = $this->config["config"];
            }
            if (isset($this->config["url"])) {
                $this->url = $this->config["url"];
            }
            if (isset($this->config["auth"])) {
                $this->auth = $this->config["auth"];
            }
            if (isset($this->config["public_key"])) {
                $this->public_key = $this->config["public_key"];
            }

            $this->client = new $config['vendor']['http_client']['client']();
        }
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
        if (isset($resource) && isset($this->client)) {
            try {
                $url = $this->config["url"];
                $public_key = "?";
                if ($this->config["auth"] == "QueryKeyAuth" && $this->config["public_key"] != null) {
                    $public_key = "?public_key=".$this->config["public_key"];
                }

                $response = $this->client->request("GET", $url."".$resource."".$public_key."&limit=1&offset=0");
 
                $output = $response->getBody();
                $output = (new Utility())->clean_json($output);
                $records = json_decode($output, true);
                if (isset($records["headers"]["code"]) || isset($records["header"]["code"])) {
                    $this->db = "api";
                    return $this->db;
                }
            } catch (Ex $ex) {
                return null;
            }
        } else {
            return null;
        }
    }

    public function get(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        $resource_id = "";
        $public_key = "";
        $array = "";
 
        if ($resource != null) {
            $this->resource = $resource;
        }
 
        if ($this->auth == "QueryKeyAuth" && $this->public_key != null) {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            if (count($arr) >= 1){
                $array = "&".http_build_query($arr);
            }
            if ($id != null) {
                $resource_id = "/".$id;
                $response = $this->client->request("GET", $this->url."".$this->resource."".$resource_id."".$public_key);
            } else {
                $response = $this->client->request("GET", $this->url."".$this->resource."".$resource_id."".$public_key."".$array);
            }
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
            }
            $response = $this->client->request("GET", $this->url."".$this->resource."".$resource_id."".$array);
        }
 
        if ($response != null) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $records = json_decode($output, true);
            if (isset($records["header"]["code"])) {
                if ($records["header"]["code"] == 200 || $records["header"]["code"] == "200") {
                    return $records;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
 
    }

    public function search(string $resource = null, string $keyword = null, array $query = [], string $field_id = null)
    {

    }

    public function post(string $resource = null, array $query = [], string $field_id = null): int
    {
        $public_key = "";
        $array = "";
        if ($resource != null) {
            $this->resource = $resource;
        }
        if ($this->auth == "QueryKeyAuth" && $this->public_key != null) {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            if (count($arr) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($arr);
                $array = parse_str($arrKey);
            }
            $response = $this->client->request("POST", $this->url."".$this->resource, $array);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $response = $this->client->request("POST", $this->url."".$this->resource, ['form_params' => $arr]);
            }
        }
        if ($response != null) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $records = json_decode($output, true);
            if (isset($records["headers"]["code"])) {
                if ($records["headers"]["code"] == 201 || $records["headers"]["code"] == "201") {
                    if (isset($records["response"]["id"])) {
                        if ($resource == "registration") {
                            // Для registration возвращаем весь ответ
                            return $records;
                        } else {
                            return $records["response"]["id"];
                        }
                    } else {
                        return null;
                    }
                }
            } else {
                return null;
            }
        } else {
            return null;
        }    
    }
 
    public function put(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        $resource_id = "";
        $public_key = "";
        $array = "";
        if ($resource != null) {
            $this->resource = $resource;
        }
        if ($id >= 1) {
            $resource_id = "/".$id;
        }
        if ($this->auth == "QueryKeyAuth" && $this->public_key != null) {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            if (count($arr) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($arr);
                $array = parse_str($arrKey);
            }
            $response = $this->client->request("PUT", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
                $response = $this->client->request("PUT", $this->url."".$this->resource."".$resource_id, ['form_params' => $arr]);
                $get_body = $response->getBody();
                $output = (new Utility())->clean_json($get_body);
                $records = json_decode($output, true);
                return $records;
            }
        }
        
        if ($response != null) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $records = json_decode($output, true);
            if (isset($records["headers"]["code"])) {
                if ($records["headers"]["code"] == 202 || $records["headers"]["code"] == "202") {
                    return $records;
                }
            } else {
                return $records;
            }
        } else {
            return $records;
        }
    }
 
    public function patch(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        $resource_id = "";
        $public_key = "";
        $array = "";
        if ($resource != null) {
            $this->resource = $resource;
        }
        if ($id >= 1) {
            $resource_id = "/".$id;
        }
        if ($this->auth == "QueryKeyAuth" && $this->public_key != null) {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            if (count($arr) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($arr);
                $array = parse_str($arrKey);
            }
            $response = $this->client->request("PATCH", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
                $response = $this->client->request("PATCH", $this->url."".$this->resource."".$resource_id, ['form_params' => $arr]);
                $get_body = $response->getBody();
                $output = (new Utility())->clean_json($get_body);
                $records = json_decode($output, true);
                return $records;
            }
        }
        
        if ($response != null) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $records = json_decode($output, true);
            if (isset($records["headers"]["code"])) {
                if ($records["headers"]["code"] == 202 || $records["headers"]["code"] == "202") {
                    return $records;
                }
            } else {
                return $records;
            }
        } else {
            return $records;
        }
    }

    public function delete(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        return null;
    }

	public function count(string $resource = null, array $query = [], int $id = null, string $field_id = null): int
	{
	
	}

    public function last_id(string $resource = null, string $field_id = null): int
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
 