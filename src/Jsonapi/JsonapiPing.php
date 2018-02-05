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
 
namespace Pllano\RouterDb\Jsonapi;
 
use Pllano\RouterDb\Utility;
use Pllano\RouterDb\Ex;
 
class JsonapiPing
{
    private $db = null;
    private $config;
 
    public function __construct(array $config = array())
    {
        if (count($config) >= 1){
            $this->config = $config;
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
                $http_client = new $this->config['vendor']['http_client']();
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
 
}
 