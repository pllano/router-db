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
 
class Jsonapi
{

    private $data;
	private $resource = null;
    private $url = null;
    private $auth = null;
    private $public_key = null;
    private $config;
	private $http_client;
 
    public function __construct(array $config = [], array $options = [], string $prefix = null, $other_base = null)
    {
        if (count($config) >= 1) {
            $this->config = $config;
            if (isset($config["db"]["jsonapi"]["url"])) {
                $this->url = $config["db"]["jsonapi"]["url"];
            }
            if (isset($config["db"]["jsonapi"]["auth"])) {
                $this->auth = $config["db"]["jsonapi"]["auth"];
            }
            if (isset($config["db"]["jsonapi"]["public_key"])) {
                $this->public_key = $config["db"]["jsonapi"]["public_key"];
            }
			$this->http_client = new $this->config['vendor']['http_client']['client']();
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

    public function ping(string $resource = null)
    {
        if ($resource != null) {
            try {
                $url = $this->config["db"]["jsonapi"]["url"];
                $query = "?limit=1&offset=0";
                if ($this->config["db"]["jsonapi"]["auth"] == "QueryKeyAuth" && $this->config["db"]["jsonapi"]["public_key"] != null) {
                    $query = "?public_key=".$this->config["db"]["jsonapi"]["public_key"]."&limit=1&offset=0";
                }
                $response = $this->http_client->request("GET", $url."".$resource."".$query);
                $output = $response->getBody();
                $output = (new Utility())->clean_json($output);
                $records = json_decode($output, true);
                if (isset($records["headers"]["code"])) {
                    $this->db = "jsonapi";
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
        if ($id != null) {
            $resource_id = "/".$id;
        }
        if ($this->auth == "QueryKeyAuth" && $this->public_key != null) {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            if (count($query) >= 1){
                $array = "&".http_build_query($query);
            }
            $response = $this->http_client->request("GET", $this->url."".$this->resource."".$resource_id."".$public_key."".$array);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($query) >= 1){
                $array = "?".http_build_query($query);
            }
            $response = $this->http_client->request("GET", $this->url."".$this->resource."".$resource_id."".$array);
        }
        if ($response != null) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $records = json_decode($output, true);
            if (isset($records["headers"]["code"])) {
                if ($records["headers"]["code"] == 200 || $records["headers"]["code"] == "200") {
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
            if (count($query) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($query);
                $array = parse_str($arrKey);
            }
            $response = $this->http_client->request("POST", $this->url."".$this->resource, $array);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($query) >= 1){
                $response = $this->http_client->request("POST", $this->url."".$this->resource, ['form_params' => $query]);
            }
        }
        if ($response != null) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $records = json_decode($output, true);
            if (isset($records["headers"]["code"])) {
                if ($records["headers"]["code"] == 201 || $records["headers"]["code"] == "201") {
                    if (isset($records["response"]["id"])) {
                        return $records["response"]["id"];
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
            if (count($query) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($query);
                $array = parse_str($arrKey);
            }
            $response = $this->http_client->request("PUT", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($query) >= 1){
                $array = "?".http_build_query($query);
                $response = $this->http_client->request("PUT", $this->url."".$this->resource."".$resource_id, ['form_params' => $query]);
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
            if (count($query) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($query);
                $array = parse_str($arrKey);
            }
            $response = $this->http_client->request("PATCH", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($query) >= 1){
                $array = "?".http_build_query($query);
                //$response = $this->http_client->request("GET", $this->url."_put/".$this->resource."".$resource_id."".$array);
                $response = $this->http_client->request("PATCH", $this->url."".$this->resource."".$resource_id, ['form_params' => $query]);
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
            if (count($query) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($query);
                $array = parse_str($arrKey);
            }
            $response = $this->http_client->request("DELETE", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($query) >= 1){
                $array = "?".http_build_query($query);
                $response = $this->http_client->request("DELETE", $this->url."".$this->resource."".$resource_id, ['form_params' => $query]);
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
	
	public function count(string $resource = null, array $query = [], int $id = null, string $field_id = null): int
	{
	
	}

    public function last_id(string $resource = null, string $field_id = null): int
    {
        $public_key = "";
        if ($resource != null) {
            $this->resource = $resource;
        }
        if ($this->auth == "QueryKeyAuth" && $this->public_key != null) {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            $response = $this->http_client->request("GET", $this->url."".$this->resource."/_last_id".$public_key);
        } elseif ($this->auth == "CryptoAuth") {
 
        } elseif ($this->auth == "HttpTokenAuth") {
 
        } elseif ($this->auth == "LoginPasswordAuth") {
 
        } else {
            $response = $this->http_client->request("GET", $this->url."".$this->resource."/_last_id");
        }
        if ($response != null) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $records = json_decode($output, true);
            if (isset($records["headers"]["code"])) {
                if ($records["headers"]["code"] == 200 || $records["headers"]["code"] == "200") {
                    return $records["response"]["last_id"];
                }
            } else {
                return null;
            }
        } else {
            return null;
        }  
    }

    public function fieldMap($resource = null)
    {
        return [];
    }

    public function tableSchema($table)
    {
        return [];
    }

    static public function selectDate($minutes = null)
    {
        return "0000-00-00 00:00:00";
    }

}
 