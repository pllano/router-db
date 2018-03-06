<?php /**
 * This file is part of the RouterDb
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/pllano/router-db
 * @version 1.2.0
 * @package pllano/router-db
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Pllano\RouterDb\Interfaces;

/**
 * Collection Interface
 *
 * @package RouterDb
 * @since   1.2.0
 */
interface ZendDbInterface
{
    public function __construct(array $config = [], array $options = [], $prefix = null);

	public function ping($resource = null);

	public function get($resource = null, array $arr = [], $id = null);

    public function post($resource = null, array $arr = []);

    public function put($resource = null, array $arr = [], $id = null);

	public function patch($resource = null, array $arr = [], $id = null);

    public function delete($resource = null, array $arr = [], $id = null);

    public function search($resource = null, array $query = [], $keyword = null);

	public function last_id($resource);

}
 