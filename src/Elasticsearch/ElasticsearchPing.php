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
 
namespace RouterDb\Elasticsearch;
 
class ElasticsearchPing
{
 
    private $config;
 
    public function __construct(array $config = array())
    {
            if (count($config) >= 1){
            $this->config = $config;
        }
    }
 
    public function ping($resource = null)
    {
            return "elasticsearch";
    }
 
}
 