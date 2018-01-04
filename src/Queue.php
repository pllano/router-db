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
 
class Queue
{
 
    private $config;
    private $db = null;
    private $package = "\RouterDb\\";
 
    public function __construct(array $config = array(), $package = null)
    {
        if (count($config) >= 1){
            $this->config = $config;
        }
        if ($package !== null) {
            $this->package = $package;
        }
    }
 
    public function run()
    {
 
    }
 
    public function synchronize()
    {
 
    }
 
    public function add($request, $resource = null, array $arr = array(), $id = null)
    {
 
    }
}
 