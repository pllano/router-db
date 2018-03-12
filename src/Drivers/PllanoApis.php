<?php /**
 * RouterDb (https://pllano.com)
 *
 * @link https://github.com/pllano/router-db
 * @version 1.2.0
 * @copyright Copyright (c) 2017-2018 PLLANO
 * @license http://opensource.org/licenses/MIT (MIT License)
 */
namespace Pllano\RouterDb\Drivers;

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
    protected $http_client = "\\GuzzleHttp\\Client";
	protected $database;
    protected $response = [];
    protected $count = null;
	protected $type;

    public function __construct(array $config = [], string $database = null, array $options = [], string $format = null, string $prefix = null, $other_base = null)
    {
        $db = [];
		$this->config = $config;
		$this->database = $database;
		$this->options = $options;
        if (isset($format)) {
            $this->format = strtolower($format);
        }
		if (isset($this->config)) {
            if (isset($other_base)) {
                $this->other_base = $other_base;
                $db = $this->config['db'][$this->other_base];
            } elseif (isset($prefix)) {
			    $this->prefix = $prefix;
                $db = $this->config['db'][$this->database.'_'.$this->prefix];
            } else {
                $db = $this->config['db'][$this->database];
            }
        }
		$this->url = $db["url"] ?? null;
		$this->auth = $db["auth"] ?? null;
		$this->public_key = $db["public_key"] ?? null;
		if (isset($this->config['vendor']['http_client']['client'])) {
			$this->http_client = new $this->config['vendor']['http_client']['client']();
		}
    }

	public function run(string $type, string $resource = null, array $query = [], int $id = null)
    {
        $this->query = $query;
        $response = null;
		$array = [];
		$url = '';
		$code = 200;
		$data = [];
        if (isset($resource) && isset($this->url)) {
            if (isset($id)) {
                $this->resource_id = "/".$id;
            }
			if ($type == "GET") {
				$array_url = "&".http_build_query($this->query);
				$array = null;
				$url = $this->url."".$resource."".$this->resource_id."".$this->public_key($resource)."".$array_url;
			} elseif ($type == "POST" || $type == "PUT" || $type == "PATCH") {
				if ($this->type == "POST") {
					$url = $this->url."".$resource;
					$code = 201;
				} else {
				    $url = $this->url."".$resource."".$resource_id;
					$code = 202;
				}
				$array = ['form_params' => array_replace_recursive($this->query, $this->public_key($resource, 'Array'))];
			} elseif ($type == "DELETE") {
			    $url = $this->url."".$resource."".$resource_id."".$this->public_key($resource);
				$code = 202;
			}
			if (isset($array)) {
                $response = $this->http_client->request($type, $url, $array);
			} else {
			    $response = $this->http_client->request($type, $url);
			}
        }
		//print_r($url);
        if (isset($response)) {
            $get_body = $response->getBody();
            $output = (new Utility())->clean_json($get_body);
            $data = json_decode($output, true);
            if (isset($data["headers"]["code"]) && (int)$data["headers"]["code"] == $code) {
                if ($this->format != "apis") {
                    $this->response = $this->format($data, $this->format);
                } else {
                    $this->response = $data;
                }
            }
        }
		if ($type == "GET") {
            $this->count = count($this->response);
		}
        return $this->response;
	}

	public function get(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        return $this->run('GET', $resource, $query, $id);
    }

    public function post(string $resource = null, array $query = [], string $field_id = null): int
    {
        return $this->run('POST', $resource, $query);
    }

    public function put(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        return $this->run('PUT', $resource, $query, $id);
    }

    public function patch(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        return $this->run('PATCH', $resource, $query, $id);
    }

    public function delete(string $resource = null, int $id = null, string $field_id = null)
    {
        return $this->run('DELETE', $resource, [], $id);
    }

    public function count(string $resource = null, array $query = [], int $id = null, string $field_id = null): int
    {
        return $this->count;
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
        if (isset($response)) {
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
            if (isset($this->data["headers"]["code"]) && (int)$this->data["headers"]["code"] == 200) {
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
		//print_r($this->response);
        return $this->response;
    }

    public function public_key(string $resource = null, $type = null)
    {
        $public_key = "?";
        if (isset($this->public_key) && $this->config["db"]["resource"][$resource]["authorization"] == true) {
            if ($this->auth == "QueryKeyAuth") {
                if ($type == 'Array') {
                    $public_key = ["public_key" => $this->public_key];
                } else {
                    $public_key = "?public_key=".$this->public_key;
                }
            }
        }
        return $public_key;
    }

}
 