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

interface RouterDbInterface
{
    public function __construct(array $config = [], string $adapter = null, string $driver = null, string $dbName = null, string $prefix = null, string $namespace = null);

    public function ping(string $resource = null);
	public function run(string $dbName = null, array $options = [], string $prefix = null);

	public function setConfig(array $config = [], string $adapter = null, string $driver = null, string $dbName = null, string $prefix = null, string $namespace = null);
	public function getConfig();

    public function setOptions(array $options = []);
    public function getOptions();

    public function setDb(string $dbName = null);
    public function getDb();

    public function setNamespace(string $namespace = null);
    public function getNamespace();

    public function setAdapter(string $adapter = null);
    public function getAdapter();

    public function setDriver(string $driver = null);
    public function getDriver();

    public function setPrefix(string $prefix = null);
    public function getPrefix();

    public function setMailer($mailer = null);

    public function setLogger($logger = null);

}
 