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
 
namespace RouterDb;

use RouterDb\Utility;
use RouterDb\routerEx;
use GuzzleHttp\Client as Guzzle;

class Db
{
    private $config = null;
    private $db = null;
    
    public function __construct(array $config = array())
    {
        if (count($config) >= 1){
            $this->config = $config;
        }
    }
 
    public function get($resource = null)
    {
        if ($resource !== null) {
            $this->db = $this->config["db"]["master"];
        } else {
            $this->db = null;
        }
 
        if (isset($this->config["resource"][$resource]["db"])) {
            $db = $this->config["resource"][$resource]["db"];
        } else {
            $db = $this->config["db"]["master"];
        }
        if ($db == "api") {
            try {
                $url = $this->config["db"]["api"]["url"];
                $public_key = $this->config["db"]["api"]["public_key"];
 
                $guzzle = new Guzzle();
                $response = $guzzle->request("GET", $url."".$resource."?public_key=".$public_key."&limit=1&offset=0");
                $output = $response->getBody();
                $output = (new Utility())->clean_json($output);
                $records = json_decode($output, true);
                if (isset($records["header"]["code"])) {
                    $this->db = "api";
                    return $this->db;
                }
            } catch (routerEx $ex) {
                $db = $this->config["db"]["master"];
            }
        } elseif ($db == "jsonapi") {
            try {
                $url = $this->config["db"]["jsonapi"]["url"];
                $public_key = "?";
                if ($this->config["db"]["jsonapi"]["auth"] == "QueryKeyAuth") {
                    $public_key = "?public_key=".$this->config["db"]["jsonapi"]["public_key"];
                }
                $guzzle = new Guzzle();
                $response = $guzzle->request("GET", $url."".$resource."".$public_key."&limit=1&offset=0");
                $output = $response->getBody();
                $output = (new Utility())->clean_json($output);
                $records = json_decode($output, true);
                if (isset($records["headers"]["code"])) {
                    $this->db = "jsonapi";
                    return $this->db;
                }
            } catch (routerEx $ex) {
                $db = $this->config["db"]["master"];
                return $this->db;
            }
        } elseif ($db == "json") {
            try {\jsonDB\Validate::table($resource)->exists();
                $this->db = "json";
                return $this->db;
            } catch(\jsonDB\dbException $e){
                $this->db = $this->config["db"]["master"];
                return $this->db;
            }
        } elseif ($db == "mysql") {
            $this->db = "mysql";
            return $this->db;
        } elseif ($db == "elasticsearch") {
            $this->db = "elasticsearch";
            return $this->db;
        } else {
            $this->db = $this->config["db"]["master"];
            return $this->db;
        }
    }

}
 
