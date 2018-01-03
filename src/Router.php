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

class Router
{
    /**
     * @param $db name
     * @var string
    */
    private $db = null;
 
    public function __construct($db = null)
    {
        if ($db !== null) {
            $this->db = $db;
        }
    }
    
    public function get($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = '\RouterDb\\'.ucfirst($this->db)."Db";
            $db = new $class();
            return $db->get($resource, $arr, $id);
        } else {
            return false;
        }
    }
 
    public function post($resource = null, array $arr = array())
    {
        if ($this->db !== null && $resource !== null) {
            $class = '\RouterDb\\'.ucfirst($this->db)."Db";
            $db = new $class();
            return $db->post($resource, $arr);
        } else {
            return false;
        }
    }
    
    public function put($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = '\RouterDb\\'.ucfirst($this->db)."Db";
            $db = new $class();
            return $db->put($resource, $arr, $id);
        } else {
            return false;
        }
    }
    
    public function patch($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = '\RouterDb\\'.ucfirst($this->db)."Db";
            $db = new $class();
            return $db->patch($resource, $arr, $id);
        } else {
            return false;
        }
    }
    
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        if ($this->db !== null && $resource !== null) {
            $class = '\RouterDb\\'.ucfirst($this->db)."Db";
            $db = new $class();
            return $db->delete($resource, $arr, $id);
        } else {
            return false;
        }
    }

}
 