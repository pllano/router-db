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
 
    public function __construct($db = null, array $config = array())
    {
        if ($db !== null) {
            $this->db = $db;
        }
        if (count($config) >= 1){
            $this->config = $config;
        }
    }
    
    public function get($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = "\RouterDb\\".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
            $db = new $class($this->config["db"][$this->db]);
            return $db->get($resource, $arr, $id);
        } else {
            return null;
        }
    }
 
    public function post($resource = null, array $arr = array())
    {
        if ($this->db !== null && $resource !== null) {
            $class = "\RouterDb\\".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
            $db = new $class($this->config["db"][$this->db]);
            return $db->post($resource, $arr);
        } else {
            return null;
        }
    }
    
    public function put($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = "\RouterDb\\".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
            $db = new $class($this->config["db"][$this->db]);
            return $db->put($resource, $arr, $id);
        } else {
            return null;
        }
    }
    
    public function patch($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = "\RouterDb\\".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
            $db = new $class($this->config["db"][$this->db]);
            return $db->patch($resource, $arr, $id);
        } else {
            return null;
        }
    }
    
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = "\RouterDb\\".ucfirst($this->db)."\\".ucfirst($this->db)."Db";
            $db = new $class($this->config["db"][$this->db]);
            return $db->delete($resource, $arr, $id);
        } else {
            return null;
        }
    }

}
 