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
    static $confArray;
 
    private function __construct()
	{
        // building data source name from config
        $dsn = 'mysql:
                host='.PdoDb::read('db.host').';
                dbname='.PdoDb::read('db.basename').';
                port='.PdoDb::read('db.port').';
                connect_timeout='.PdoDb::read('db.connect_timeout').';
                charset='.PdoDb::read('db.charset');
        // getting DB user from config                
        $user = PdoDb::read('db.user');
        // getting DB password from config                
        $password = PdoDb::read('db.password');
 
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
 
    public static function set(array $config = array()) 
    {
        PdoDb::write('db.host', $config["host"]);
        PdoDb::write('db.port', $config["port"]);
        PdoDb::write('db.basename', $config["dbname"]);
        PdoDb::write('db.user', $config["user"]);
        PdoDb::write('db.password', $config["password"]);
        PdoDb::write('db.charset', $config["charset"]);
        PdoDb::write('db.connect_timeout', $config["connect_timeout"]);
    }
 
}
 