<?php
/**
 * RouterDb (https://pllano.com)
 *
 * @link https://github.com/pllano/router-db
 * @version 1.2.0
 * @copyright Copyright (c) 2017-2018 PLLANO
 * @license http://opensource.org/licenses/MIT (MIT License)
 */
namespace Pllano\RouterDb\Interfaces;

interface ApisInterface extends ApiInterface
{

    public function apis(array $arr = [], string $type = null);

    public function setType(string $type = null);

    public function setCode(int $code = null);

    public function setMessage(string $message = null);

    public function setHttpCodes(string $httpCode = null);

    // ApiInterface extends DbInterface
    // public function ping(string $resource = null);
    // public function get(string $resource = null, array $array = [], int $id = null, string $field_id = null);
    // public function search(string $resource = null, string $keyword = null, array $array = [], string $field_id = null);
    // public function post(string $resource = null, array $array = [], string $field_id = null);
    // public function put(string $resource = null, array $array = [], int $id = null, string $field_id = null);
    // public function patch(string $resource = null, array $array = [], int $id = null, string $field_id = null);
    // public function del(string $resource = null, array $array = [], int $id = null, string $field_id = null);
    // public function last_id(string $resource = null, string $field_id = null);

	// DbInterface extends \ArrayAccess, \Countable, \ArrayIterator
    // Magic Methods
    // public function __set($key, $value = null);
    // public function __get($key);
    // public function __isset($key);
    // public function __unset($key);

    // \ArrayAccess
    // public function offsetSet($offset, $value);
    // public function offsetExists($offset);
    // public function offsetUnset($offset);
    // public function offsetGet($offset);

    // \Countable
    // public function count();

    // \ArrayIterator
    // public function getIterator();

}
 