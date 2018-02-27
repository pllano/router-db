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

    private $resource = null;
    private $url = null;
    private $auth = null;
    private $public_key = null;
    private $config;
 
    public function __construct(array $config = [], array $options = [])
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
        }
    }

    public function ping($resource = null)
    {
        if ($resource != null) {
            try {
                $url = $this->config["db"]["jsonapi"]["url"];
                $query = "?limit=1&offset=0";
                if ($this->config["db"]["jsonapi"]["auth"] == "QueryKeyAuth" && $this->config["db"]["jsonapi"]["public_key"] != null) {
                    $query = "?public_key=".$this->config["db"]["jsonapi"]["public_key"]."&limit=1&offset=0";
                }
                $http_client = new $this->config['vendor']['http_client']['client']();
                $response = $http_client->request("GET", $url."".$resource."".$query);
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

    // Загрузить
    public function get($resource = null, array $arr = [], $id = null)
    {
        $http_client = new $this->config['vendor']['http_client']['client']();
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
            if (count($arr) >= 1){
                $array = "&".http_build_query($arr);
            }
            $response = $http_client->request("GET", $this->url."".$this->resource."".$resource_id."".$public_key."".$array);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
            }
            $response = $http_client->request("GET", $this->url."".$this->resource."".$resource_id."".$array);
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
 
    // Искать
    public function search($resource = null, array $query_arr = [], $keyword = null)
    {
        // Новый запрос, аналог get рассчитан на полнотекстовый поиск
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
 
        // Еще в разработке ...
    }
 
    // Создаем одну запись
    public function post($resource = null, array $arr = [])
    {
        $http_client = new $this->config['vendor']['http_client']['client']();
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
            $response = $http_client->request("POST", $this->url."".$this->resource, $array);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $response = $http_client->request("POST", $this->url."".$this->resource, ['form_params' => $arr]);
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
 
    // Обновляем
    public function put($resource = null, array $arr = [], $id = null)
    {
        $http_client = new $this->config['vendor']['http_client']['client']();
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
            $response = $http_client->request("PUT", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
                $response = $http_client->request("PUT", $this->url."".$this->resource."".$resource_id, ['form_params' => $arr]);
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
 
    // Обновляем
    public function patch($resource = null, array $arr = [], $id = null)
    {
        $http_client = new $this->config['vendor']['http_client']['client']();
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
            $response = $http_client->request("PATCH", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
                //$response = $http_client->request("GET", $this->url."_put/".$this->resource."".$resource_id."".$array);
                $response = $http_client->request("PATCH", $this->url."".$this->resource."".$resource_id, ['form_params' => $arr]);
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
 
    // Удаляем
    public function delete($resource = null, array $arr = [], $id = null)
    {
        $http_client = new $this->config['vendor']['http_client']['client']();
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
            $response = $http_client->request("DELETE", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
                $response = $http_client->request("DELETE", $this->url."".$this->resource."".$resource_id, ['form_params' => $arr]);
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
 
    // Получить последний идентификатор
    public function last_id($resource)
    {
        $http_client = new $this->config['vendor']['http_client']['client']();
        $public_key = "";
        if ($resource != null) {
            $this->resource = $resource;
        }
        if ($this->auth == "QueryKeyAuth" && $this->public_key != null) {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            $response = $http_client->request("GET", $this->url."".$this->resource."/_last_id".$public_key);
        } elseif ($this->auth == "CryptoAuth") {
 
        } elseif ($this->auth == "HttpTokenAuth") {
 
        } elseif ($this->auth == "LoginPasswordAuth") {
 
        } else {
            $response = $http_client->request("GET", $this->url."".$this->resource."/_last_id");
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
 
}
 