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
 
namespace RouterDb\Mysql;
 
use PDO;
 
class PdoDb {
    
    public $dbh; // handle of the db connexion
    private static $instance;

    private function __construct(array $config = array()) {
 
        // building data source name from config
        $dsn = 'mysql:host='.$config["host"].
               ';dbname='.$config["dbname"].
               ';port='.$config["port"].
               ';connect_timeout='.$config["connect_timeout"].
               ';charset='.$config["charset"];
        // getting DB user from config           
        $user = $config["user"];
        // getting DB password from config
        $password = $config["password"];

        $this->dbh = new PDO($dsn, $user, $password);
    }

    public static function getInstance() {
        if (!isset(self::$instance))
        {
            $object = __CLASS__;
            self::$instance = new $object;
        }
        return self::$instance;
    }
 
}