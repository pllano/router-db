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
 
namespace Pllano\RouterDb\Mysql;
 
use PDO;
 
class PdoDb {
 
    public $dbh; // handle of the db connexion
    private static $instance;
    static $confArray;
 
    private function __construct()
    {
        $host = PdoDb::read('db.host');
        $basename = PdoDb::read('db.basename');
        $port = PdoDb::read('db.port');
        $timeout = PdoDb::read('db.connect_timeout');
        $charset = PdoDb::read('db.charset');
        $charset = PdoDb::read('db.charset');                
        $user = PdoDb::read('db.user');               
        $password = PdoDb::read('db.password');
 
        $dsn = 'mysql:host='.$host.';dbname='.$basename.';port='.$port.';connect_timeout='.$timeout.';charset='.$charset;
        $this->dbh = new PDO($dsn, $user, $password);
    }
 
    public static function getInstance() 
    {
        if (!isset(self::$instance))
        {
            $object = __CLASS__;
            self::$instance = new $object;
        }
 
        return self::$instance;
 
    }
 
    public static function read($name)
    {
        if(isset(self::$confArray['db'][$name])){
 
            return self::$confArray['db'][$name];
 
        }
    }
 
    public static function write($name, $value) 
    {
        self::$confArray['db'][$name] = $value;
    }
 
    public static function set(array $config = []) 
    {
        PdoDb::write('db.host', $config["db"]["mysql"]["host"]);
        PdoDb::write('db.port', (int)$config["db"]["mysql"]["port"]);
        PdoDb::write('db.basename', $config["db"]["mysql"]["dbname"]);
        PdoDb::write('db.user', $config["db"]["mysql"]["user"]);
        PdoDb::write('db.password', $config["db"]["mysql"]["password"]);
        PdoDb::write('db.charset', $config["db"]["mysql"]["charset"]);
        PdoDb::write('db.connect_timeout', $config["db"]["mysql"]["connect_timeout"]);
    }
 
}
 