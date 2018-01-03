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

class Db
{
    /**
     * @param $db name
     * @var string
    */
    private $db = null;
    private $config;
    private $package = "\RouterDb\\";
 
    public function __construct($db = null, array $config = array(), $package = null)
    {
        if ($db !== null) {
            $this->db = $db;
        }
        if (count($config) >= 1){
            $this->config = $config;
        }
        if ($package !== null) {
            $this->package = $package;
        }
    }
    
    public function get($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
			$configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
			$configArr["db"][$this->db] = $this->config["db"][$this->db];
			if ($this->db != "json"){
			    $configArr["db"]["json"] = $this->config["db"]["json"];
			}
            $db = new $class($configArr);
            return $db->get($resource, $arr, $id);
        } else {
            return null;
        }
    }
 
    public function post($resource = null, array $arr = array())
    {
        if ($this->db !== null && $resource !== null) {
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
			$configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
			$configArr["db"][$this->db] = $this->config["db"][$this->db];
			if ($this->db != "json"){
			    $configArr["db"]["json"] = $this->config["db"]["json"];
			}
            $db = new $class($configArr);
            return $db->post($resource, $arr);
        } else {
            return null;
        }
    }
    
    public function put($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
			$configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
			$configArr["db"][$this->db] = $this->config["db"][$this->db];
			if ($this->db != "json"){
			    $configArr["db"]["json"] = $this->config["db"]["json"];
			}
            $db = new $class($configArr);
            return $db->put($resource, $arr, $id);
        } else {
            return null;
        }
    }
    
    public function patch($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
			$configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
			$configArr["db"][$this->db] = $this->config["db"][$this->db];
			if ($this->db != "json"){
			    $configArr["db"]["json"] = $this->config["db"]["json"];
			}
            $db = new $class($configArr);
            return $db->patch($resource, $arr, $id);
        } else {
            return null;
        }
    }
    
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = $this->package."".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
			$configArr["settings"]["http-codes"] = $this->config["settings"]["http-codes"];
			$configArr["db"][$this->db] = $this->config["db"][$this->db];
			if ($this->db != "json"){
			    $configArr["db"]["json"] = $this->config["db"]["json"];
			}
            $db = new $class($configArr);
            return $db->delete($resource, $arr, $id);
        } else {
            return null;
        }
    }

}
     