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
 
/**
 * Поля ресурса queue
 *
 * "resource": "string"
 * "resource_id": "integer"
 * "request": "string"
 * "request_body": "string"
 */
 
namespace RouterDb;
 
class Queue
{
 
    /**
     * @param $config
     * @var array
    */
    private $config;
    /**
     * @param $class
     * @var string
    */
    private $class;
 
    public function __construct(array $config = array())
    {
        if (count($config) >= 1){
 
            // Получаем название резервной базы
            $db = $config["db"]["slave"];
 
            // Получаем конфигурацию для резервной базы
            $configArr["settings"]["http-codes"] = $config["settings"]["http-codes"];
            $configArr["db"][$db] = $config["db"][$db];
			$this->config = $configArr;
 
            $class = "\RouterDb\\".ucfirst($db)."\\".ucfirst($db)."Db";
			$this->class = $class;
 
        }
 
    }
 
    public function run()
    {
 
        $class = $this->class;
		$db = new $class($this->config);
		$response = $db->get("queue");
 
		$count = count($response);
		if (count($count) >= 1) {
		    // Возвращаем колличество записей оставшихся в очереди
            return $count;
		} else {
		    return null;
		}
 
    }
 
    public function synchronize()
    {
 
    }
 
	public function add($request, $db, $resource = null, array $arr = array(), $id = null)
    {
 
		$array["resource"] = $request;
		$array["request"] = $resource;
		if (isset($id)) {
		    $array["resource_id"] = $id;
		}
		if ($request != "POST") {
		    $array["request_body"] = base64_encode(json_encode($arr));
		}
 
        $class = $this->class;
		$queueDb = new $class($this->config);
		$queueDb->post("queue", $array);
 
    }
}
 