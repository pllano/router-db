<?php /**
 * RouterDb (https://pllano.com)
 *
 * @link https://github.com/pllano/router-db
 * @version 1.2.0
 * @copyright Copyright (c) 2017-2018 PLLANO
 * @license http://opensource.org/licenses/MIT (MIT License)
 */
namespace Pllano\RouterDb\Adapters;

use Pllano\Interfaces\DatabaseInterface;
use Pllano\RouterDb\Utility;

class PllanoApis implements DatabaseInterface
{
    protected $data = [];
	protected $format = null;
    protected $resource = null;
    protected $keyword = null;
    protected $array = [];
    protected $field_id = null;
    protected $query = null;
    protected $resource_id = "";
    protected $url = null;
    protected $auth = null;
    protected $public_key = null;
    protected $config = [];
    protected $options = [];
    protected $prefix = null;
    protected $other_base = null;
    protected $http_client;
	protected $database;
    protected $response = [];
    protected $count = null;
	protected $type;

    public function __construct(array $config = [], array $options = [], string $format = null, string $prefix = null, $other_base = null)
    {
        if (count($config) >= 1) {
            $this->config = $config;
			$this->options = $options;
            if (isset($other_base)) {
                $api = $config["db"][$other_base];
            } elseif (isset($prefix)) {
                $api = $config['db']['pllanoapi_'.$prefix];
            } else {
                $api = $config['db']['pllanoapi'];
            }
            if (isset($api["url"])) {
                $this->url = $api["url"];
            }
            if (isset($api["auth"])) {
                $this->auth = $api["auth"];
            }
            if (isset($api["public_key"])) {
                $this->public_key = $api["public_key"];
            }
            $this->http_client = new $this->config['vendor']['http_client']['client']();
        }
        if (isset($format)) {
            $this->format = strtolower($format);
        }
    }

    
	public function run(string $type, string $resource = null, array $query = [], int $id = null)
    {
        $this->type = $type;
		$this->resource = $resource;
        $this->query = $query;
        $this->id = $id;
		$this->field_id = $field_id;
		$this->keyword = $keyword;
        $response = null;
		$array = [];
		$url = '';
		$code = 200;

        if (isset($this->resource) && isset($this->url)) {
            if ($this->id != null) {
                $this->resource_id = "/".$this->id;
            }
			if ($this->type == "GET") {
				$array_url = "&".http_build_query($this->query);
				$url = $this->url."".$this->resource."".$this->resource_id."".$this->public_key($this->resource)."".$array_url;
				$code = 200;
			} elseif ($this->type == "POST") {
				$url = $this->url."".$this->resource;
				$array = ['form_params' => array_replace_recursive($this->query, $this->public_key($this->resource, 'Array'))];
				$code = 201;
			} elseif ($this->type == "PUT" || $this->type == "PATCH") {
			    $url = $this->url."".$this->resource."".$resource_id;
				$array = ['form_params' => array_replace_recursive($this->query, $this->public_key($this->resource, 'Array'))];
				$code = 202;
			} elseif ($this->type == "DELETE") {
			    $url = $this->url."".$this->resource."".$resource_id."".$this->public_key($this->resource);
				$code = 202;
			}
            $response = $this->http_client->request($this->type, $url, $array);
        }

        if (isset($response) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $this->data = json_decode($output, true);
            if (isset($data["headers"]["code"]) && (int)$this->data["headers"]["code"] == $code) {
                if ($this->format != "apis") {
                    $this->response = $this->format($this->data, $this->format);
                } else {
                    $this->response = $this->data;
                }
            }
        }

		if ($this->type == "GET") {
            $this->count = count($this->response);
		}

        return $this->response;
	}

	public function get(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        $this->resource = $resource;
        $this->query = $query;
        $this->id = $id;
        $response = null;

        if (isset($this->resource) && isset($this->url)) {
            if ($this->id != null) {
                $this->resource_id = "/".$this->id;
            }
            $array = "";
            if (isset($this->query)) {
                $array = "&".http_build_query($this->query);
            }
            $response = $this->http_client->request("GET", $this->url."".$this->resource."".$this->resource_id."".$this->public_key($this->resource)."".$array);
        }
        if (isset($response) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $this->data = json_decode($output, true);
            if (isset($data["headers"]["code"]) && (int)$this->data["headers"]["code"] == 200) {
                if ($this->format != "apis") {
                    $this->response = $this->format($this->data, $this->format);
                } else {
                    $this->response = $this->data;
                }
            }
        }
        $this->count = count($this->response);
        return $this->response;
    }

    public function search(string $resource = null, array $query = [], string $keyword = null, string $field_id = null)
    {
        $this->resource = $resource;
        $this->query = $query;
        $this->keyword = $keyword;
        $response = null;

        if (isset($this->resource) && isset($this->url)) {
            $array = "";
            if (isset($this->query)) {
                $array = "&".http_build_query($this->query);
            }
            $keywords = "";
            if (isset($this->keyword)) {
                $keywords = "&keyword=".$this->keyword;
            }
            $response = $this->http_client->request("GET", $this->url."".$this->resource."".$this->public_key($this->resource)."".$keywords."".$array);
        }
        if (isset($response) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $this->data = json_decode($output, true);
            if (isset($data["headers"]["code"]) && (int)$this->data["headers"]["code"] == 200) {
                if ($this->format != "apis") {
                    $this->response = $this->format($this->data, $this->format);
                } else {
                    $this->response = $this->data;
                }
            }
        }
        return $this->response;
    }

    public function post(string $resource = null, array $query = [], string $field_id = null): int
    {
        $this->resource = $resource;
        $this->query = $query;
        $response = null;
        if (isset($this->resource) && isset($this->url)) {
            $array = [];
            if (isset($this->query)) {
                $array = ['form_params' => array_replace_recursive($this->query, $this->public_key($this->resource, 'Array'))];
            }
            $response = $this->http_client->request("POST", $this->url."".$this->resource, $array);
        }
        if (isset($response)) {
            $output = $response->getBody();
            $output = (new Utility())->clean_json($output);
            $this->data = json_decode($output, true);
            if (isset($this->data["headers"]["code"]) && (int)$this->data["headers"]["code"] == 201) {
                if ($this->format != "apis") {
                    $this->response = $this->format($this->data, $this->format);
                } else {
                    $this->response = $this->data;
                }
            }
        }
        return $this->response;
    }

    public function put(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        $this->resource = $resource;
        $this->query = $query;
        $response = null;
        if (isset($this->resource) && isset($this->url)) {
            $resource_id = "";
            if (isset($id)) {
                $resource_id = "/".$id;
            }
            $array = [];
            if (isset($this->query)) {
                $array = ['form_params' => array_replace_recursive($this->query, $this->public_key($this->resource, 'Array'))];
            }
            $response = $this->http_client->request("PUT", $this->url."".$this->resource."".$resource_id, $array);
        }
        
        if (isset($response)) {
            $output = $response->getBody();
            $output = (new Utility())->clean_json($output);
            $this->data = json_decode($output, true);
            if (isset($this->data["headers"]["code"]) && (int)$this->data["headers"]["code"] == 202) {
                if ($this->format != "apis") {
                    $this->response = $this->format($this->data, $this->format);
                } else {
                    $this->response = $this->data;
                }
            }
        }
        return $this->response;
    }

    public function patch(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        $this->resource = $resource;
        $this->query = $query;
        $response = null;
        if (isset($this->resource) && isset($this->url)) {
            $resource_id = "";
            if (isset($id)) {
                $resource_id = "/".$id;
            }
            $array = [];
            if (isset($this->query)) {
                $array = ['form_params' => array_replace_recursive($this->query, $this->public_key($this->resource, 'Array'))];
            }
            $response = $this->http_client->request("PATCH", $this->url."".$this->resource."".$resource_id, $array);
        }
        
        if (isset($response)) {
            $output = $response->getBody();
            $output = (new Utility())->clean_json($output);
            $this->data = json_decode($output, true);
            if (isset($this->data["headers"]["code"]) && (int)$this->data["headers"]["code"] == 202) {
                if ($this->format != "apis") {
                    $this->response = $this->format($this->data, $this->format);
                } else {
                    $this->response = $this->data;
                }
            }
        }
        return $this->response;
    }

    public function delete(string $resource = null, int $id = null, string $field_id = null)
    {
        $this->resource = $resource;
        $response = null;
        if (isset($this->resource) && isset($this->url)) {
            $resource_id = "";
            if (isset($id)) {
                $resource_id = "/".$id;
            }
            $response = $this->http_client->request("DELETE", $this->url."".$this->resource."".$resource_id."".$this->public_key($this->resource));
        }
        if (isset($response)) {
            $output = $response->getBody();
            $output = (new Utility())->clean_json($output);
            $this->data = json_decode($output, true);
            if (isset($this->data["headers"]["code"]) && (int)$this->data["headers"]["code"] == 202) {
                if ($this->format != "apis") {
                    $this->response = $this->format($this->data, $this->format);
                } else {
                    $this->response = $this->data;
                }
            }
        }
        return $this->response;
    }

    public function count(string $resource = null, array $query = [], int $id = null, string $field_id = null): int
    {
        return $this->count;
    }

    public function lastId(string $resource = null): int
    {
        $this->resource = $resource;
        $response = null;
        if (isset($this->resource) && isset($this->url)) {
            $response = $this->http_client->request("GET", $this->url."".$this->resource."/_last_id".$this->public_key($this->resource));
        }
        if (isset($response)) {
            $output = $response->getBody();
            $output = (new Utility())->clean_json($output);
            $this->data = json_decode($output, true);
            if (isset($this->data["headers"]["code"]) && (int)$this->data["headers"]["code"] == 202) {
                if ($this->format != "apis") {
                    $this->response = (int)$this->data["response"]["last_id"];
                } else {
                    $this->response = (int)$this->data;
                }
            }
        }
        return $this->response;
    }

    public function fieldMap($resource = null)
    {
        return [];
    }

    public function tableSchema($resource)
    {
        $fieldMap = $this->fieldMap($resource);
        $table_schema = [];
        if (isset($fieldMap)) {
            foreach($fieldMap as $key => $val)
            {
                $table_schema[$key] = $val;
            }
        }
        return $table_schema;
    }

    public function ping(string $resource = null)
    {
        $response = null;
        $this->resource = $resource;
        $url = $this->config["db"]["pllanoapi"]["url"] ?? null;
        $query = "?limit=1&offset=0";
        if (isset($url) && isset($this->resource)) {
            if ($this->config["db"]["pllanoapi"]["auth"] == "QueryKeyAuth" && $this->config["db"]["pllanoapi"]["public_key"] != null) {
                $query = "?public_key=".$this->config["db"]["pllanoapi"]["public_key"]."&limit=1&offset=0";
            }
            $resp = $this->http_client->request("GET", $url."".$this->resource."".$query);
            $output = $resp->getBody();
            $output = (new Utility())->clean_json($output);
            $records = json_decode($output, true);
            if (isset($records["headers"]["code"])) {
                $this->database = "pllanoapi";
                $response = $this->database;
            }
        }
        return $response;
    }

    public function setFormat($format = null)
    {
        if (isset($format)) {
            $this->format = strtolower($format);
        }
    }

    public function format($data, $format = null)
    {
        if (isset($format)) {
            $this->format = strtolower($format);
        }
        $resp = [];
        $r = [];
        if ($this->format == 'apis') {
            $this->response = $data;
        } else {
            if (isset($data["body"]["items"])) {
                $resp = $data["body"]["items"];
            }
            if (isset($resp)) {
                foreach($resp as $key => $value)
                {
                    $r[$key] = $value["item"];
                }
				if ($this->format == 'object') {
                    $this->response = (object)$r;
				} else {
				    $this->response = $r;
				}
            }
        }
        return $this->response;
    }

    public function public_key(string $resource = null, $type = null)
    {
        $public_key = "?";
        if (isset($this->public_key) && $this->config["db"]["resource"][$resource]["authorization"] == true) {
            if ($this->auth == "QueryKeyAuth") {
                if ($type = 'Array') {
                    $public_key = ["public_key" => $this->public_key];
                } else {
                    $public_key = "?public_key=".$this->public_key;
                }
            }
        }
        return $public_key;
    }

}
 