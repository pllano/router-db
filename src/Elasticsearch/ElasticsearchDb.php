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

use RouterDb\Utility;
use GuzzleHttp\Client as Guzzle;
use Elasticsearch\ClientBuilder as Elastic;
 
/**
 * ElasticsearchDb
*/
class ElasticsearchDb
{
    
    private $resource = null;
    private $host = null;
    private $port = null;
    private $type = null;
    private $index = null;
    private $auth = null;
    private $user = null;
    private $password = null;
 
    public function __construct(array $config = array())
    {
        if (count($config) >= 1){
            if (isset($config["host"])) {
                $this->host = $config["host"];
            }
            if (isset($config["port"])) {
                $this->port = $config["port"];
            }
            if (isset($config["type"])) {
                $this->type = $config["type"];
            }
            if (isset($config["index"])) {
                $this->index = $config["index"];
            }
            if (isset($config["auth"])) {
                $this->auth = $config["auth"];
            }
            if (isset($config["user"])) {
                $this->user = $config["user"];
            }
            if (isset($config["password"])) {
                $this->password = $config["password"];
            }
        }
    }
    
    public function get($resource = null, array $arr = array(), $id = null)
    {
        $client = Elastic::create()->build();
 
        $params["index"] = $this->index;
        $params["type"] = $this->type;
        if (isset($id)) {
            $params["id"] = $id;
        }
        $params["client"] = ['ignore' => [400, 404, 500]];

        $get = $client->get($params);
    }

    // Создаем одну запись
    public function post($resource = null, array $arr = array())
    {
        $client = Elastic::create()->build();
        
        $params = [
            "index" => $elasticsearch_index,
            "type" => "marketplace_item",
            "id" => $id,
            'client' => ['ignore' => [400, 404, 500]],
            "body" => [
                "site_id" => $site_id,
                "price_id" => $price_id,
                "item_id" => $item_id,
                "seller_id" => $seller_id,
                "state" => '1'
            ]
        ];
 
        $client->index($params);
 
    }
    
    // Обновляем
    public function put($resource = null, array $arr = array(), $id = null)
    {
        $client = Elastic::create()->build();
        
        $params = [
            "index" => $elasticsearch_index,
            "type" => "marketplace_item",
            "id" => $id,
            'client' => [ 'ignore' => [400, 404, 500] ],
            "body" => [
                "doc" => [
                    "site_id" => $site_id,
                    "price_id" => $price_id,
                    "product_articul" => $articul,
                    "alias" => $alias,
                    "activation" => '1',
                    "moderation" => $moderation,
                    "state" => '1'
                ]
            ]
        ];
 
        $client->update($params);
 
    }
    
    // Удаляем
    public function delete($resource = null, array $arr = array(), $id = null)
    {
        $client = Elastic::create()->build();
        
        $client->delete($params);
    }

    public function search($resource = null, array $arr = array(), $search = null)
    {
        // Новый запрос, аналог get рассчитан на полнотекстовый поиск
        // Должен возвращать count для пагинации в параметре ["response"]["total"]
        $client = Elastic::create()->build();
        // Здесь будет много кода с маневрами :)
        $client->search($params);
    }
 
    // Получить последний идентификатор
    public function last_id($resource)
    {
        // Здесь есть проблема !
        // В Elasticsearch id не являются целым числом
        // Возникнут проблемы с синхронизацией записей
        // Сейчас думаем как решить этот вопрос
    }
 
}
 
