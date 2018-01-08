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
 
namespace RouterDb\Api;

use RouterDb\Utility;
use GuzzleHttp\Client as Guzzle;

class ApiDb
{
 
    private $resource = null;
    private $url = null;
    private $auth = null;
    private $api = null;
    private $public_key = null;
 
    public function __construct(array $config = array())
    {
        if (count($config) >= 1){
            if (isset($config["db"]["api"]["config"])) {
                $this->api = $config["db"]["api"]["config"];
            }
            if (isset($config["db"]["api"]["url"])) {
                $this->url = $config["db"]["api"]["url"];
            }
            if (isset($config["db"]["api"]["auth"])) {
                $this->auth = $config["db"]["api"]["auth"];
            }
            if (isset($config["db"]["api"]["public_key"])) {
                $this->public_key = $config["db"]["api"]["public_key"];
            }
        }
    }
 
    // Загрузить
    public function get($resource = null, array $arr = array(), $id = null)
    {
        $guzzle = new Guzzle();
        $resource_id = "";
        $public_key = "";
        $array = "";
 
        if ($resource != null) {
            $this->resource = $resource;
        }
 
        if ($this->auth == "QueryKeyAuth") {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            if (count($arr) >= 1){
                $array = "&".http_build_query($arr);
            }
            if ($id != null) {
                $resource_id = "/".$id;
                $response = $guzzle->request("GET", $this->url."".$this->resource."".$resource_id."".$public_key);
            } else {
                $response = $guzzle->request("GET", $this->url."".$this->resource."".$resource_id."".$public_key."".$array);
            }
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
            }
            $response = $guzzle->request("GET", $this->url."".$this->resource."".$resource_id."".$array);
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
 
    // Искать
    public function search($resource = null, array $query_arr = array(), $keyword = null)
    {
        // Новый запрос, аналог get рассчитан на полнотекстовый поиск
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
 
        // Еще в разработке ...
    }
 
    // Создаем одну запись
    public function post($resource = null, array $arr = array())
    {
        $guzzle = new Guzzle();
        $public_key = "";
        $array = "";
        if ($resource != null) {
            $this->resource = $resource;
        }
        if ($this->auth == "QueryKeyAuth") {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            if (count($arr) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($arr);
                $array = parse_str($arrKey);
            }
            $response = $guzzle->request("POST", $this->url."".$this->resource, $array);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $response = $guzzle->request("POST", $this->url."".$this->resource, ['form_params' => $arr]);
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
    public function put($resource = null, array $arr = array(), $id = null)
    {
        $guzzle = new Guzzle();
        $resource_id = "";
        $public_key = "";
        $array = "";
        if ($resource != null) {
            $this->resource = $resource;
        }
        if ($id >= 1) {
            $resource_id = "/".$id;
        }
        if ($this->auth == "QueryKeyAuth") {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            if (count($arr) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($arr);
                $array = parse_str($arrKey);
            }
            $response = $guzzle->request("PUT", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
                $response = $guzzle->request("PUT", $this->url."".$this->resource."".$resource_id, ['form_params' => $arr]);
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
    public function patch($resource = null, array $arr = array(), $id = null)
    {
        $guzzle = new Guzzle();
        $resource_id = "";
        $public_key = "";
        $array = "";
        if ($resource != null) {
            $this->resource = $resource;
        }
        if ($id >= 1) {
            $resource_id = "/".$id;
        }
        if ($this->auth == "QueryKeyAuth") {
            if ($this->auth != null) {
                $public_key = "?public_key=".$this->public_key;
            }
            if (count($arr) >= 1){
                $arrKey = "public_key=".$this->public_key."&".http_build_query($arr);
                $array = parse_str($arrKey);
            }
            $response = $guzzle->request("PATCH", $this->url."".$this->resource."".$resource_id, ['form_params' => $array]);
        } elseif ($this->auth == "CryptoAuth") {
            
        } elseif ($this->auth == "HttpTokenAuth") {
            
        } elseif ($this->auth == "LoginPasswordAuth") {
            
        } else {
            if (count($arr) >= 1){
                $array = "?".http_build_query($arr);
                $response = $guzzle->request("PATCH", $this->url."".$this->resource."".$resource_id, ['form_params' => $arr]);
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
    public function delete($resource = null, array $arr = array(), $id = null)
    {
 
    }
 
    // Получить последний идентификатор
    public function last_id($resource)
    {
        
    }
 
}
 