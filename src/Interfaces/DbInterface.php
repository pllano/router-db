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

interface DbInterface extends \ArrayAccess, \Countable
{

    public function __construct(array $config = [], array $options = [], string $prefix = null);

    // \ArrayAccess
    // public function offsetSet($offset, $value);
    // public function offsetExists($offset);
    // public function offsetUnset($offset);
    // public function offsetGet($offset);

    // \Countable
    // public function count();

    // \ArrayIterator
    // public function getIterator();

    // Magic Methods
    public function __set($key, $value = null);
    public function __get($key);
    public function __isset($key);
    public function __unset($key);

}
 