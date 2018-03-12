<?php /**
 * RouterDb (https://pllano.com)
 *
 * @link https://github.com/pllano/router-db
 * @version 1.2.0
 * @copyright Copyright (c) 2017-2018 PLLANO
 * @license http://opensource.org/licenses/MIT (MIT License)
 */
namespace Pllano\RouterDb;

use Pllano\Interfaces\RouterDbInterface;

class Router implements RouterDbInterface
{
    /**
     * @param $config
     * @var array
    */
    private $config = [];
    /**
     * @param $database
     * @var string
    */
    private $database = null;
    /**
     * @param $router
     * @var string
    */
    private $router = 0;
    /**
     * @param database $namespace
     * @var namespace
    */
    private $namespace = "\\Pllano\\RouterDb\\Drivers";
    /**
     * @param $driver
     * @var string
    */
    private $driver = null;
    /**
     * @param $adapter
     * @var string
    */
    private $adapter = null;
    /**
     * @param $format
     * @var string
    */
    private $format = 'Default';
    /**
     * @param $prefix
     * @var string
    */
    private $prefix = null;
    /**
     * @param other database
     * @var string
    */
	private $other_base = null;
    /**
     * @param $options
     * @var array
    */
	private $options = [];
    /**
     * @param logger
     * @var namespace
    */
    private $logger = null;
    /**
     * @param mailer
     * @var namespace
    */
    private $mailer = null;
	
	private $resource = null;

    public function __construct(array $config = [], string $adapter = null, string $driver = null, string $format = null)
    {
        if (isset($config)) {
            $this->config = $config;
        } elseif (file_exists(__DIR__ . 'config.json')) {
            $this->config = json_decode(file_get_contents(__DIR__ . 'config.json'), true);
        }
        if (isset($adapter)) {
            $this->adapter = $adapter;
        }
        if (isset($driver)) {
            $this->driver = $driver;
        }
        if (isset($format)) {
            $this->format = $format;
        }
		$this->router = (int)$this->config["db"]["router"];
    }

    public function setConfig(array $config = [], string $adapter = null, string $driver = null, string $format = null)
    {
        if (isset($config)) {
			$this->config = array_replace_recursive($this->config, $config);
        }
        if (isset($adapter)) {
            $this->adapter = $adapter;
        }
        if (isset($driver)) {
            $this->driver = $driver;
        }
        if (isset($format)) {
            $this->format = $format;
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function ping($resource = null, $prefix = null, $other_base = null)
    {
		$this->resource = $resource;
        $this->prefix = $prefix;
        $this->other_base = $other_base;
		$resource = $this->config['db']['resource'][$this->resource] ?? null;
		$this->driver = $resource['driver'] ?? null;
		$this->adapter = $resource['adapter'] ?? null;
		$this->format = $resource['format'] ?? null;
		$this->database = $resource["db"] ?? $this->config["db"]["master"];

        if (isset($this->other_base)) {
            $this->database = $this->other_base;
        } elseif ($this->prefix) {
            $this->database = $this->database.''.$this->prefix;
        }

        // Проверяем наличие slave базы и включен ли роутинг
        if (isset($this->config["db"]["slave"]) && $this->router == 1) {

            if (isset($this->namespace) && isset($this->driver) && isset($this->adapter) && isset($this->resource)) {

                $class = $this->namespace."\\".ucfirst(strtolower($this->driver))."".ucfirst(strtolower($this->adapter));
                $ping = (new $class($this->config))->ping($resource);

                // Вернет название ресурса или null
                if ($ping == $this->config["db"]["resource"][$this->resource]["db"]) {
                    // Если все ок вернет название $resource
					$this->database = $this->config["db"]["resource"][$this->resource]["db"];
                } else {
                    // Если все ок, вернет название master базы
                    if ($ping == $this->config["db"]["master"]) {
                        $this->database = $this->config["db"]["master"];
                    } else {
                        if ($ping == $this->config["db"]["slave"]) {
                            $this->database = $this->config["db"]["slave"];
                        }
                    }
                }
            }
        }

		return $this->database;

    }

    public function run($database = null, array $options = [])
    {
        $this->options = $options ?? [];
        $this->database = $database ?? null;
		if (isset($this->namespace) && isset($this->adapter)&& isset($this->driver)) {
			$class = $this->namespace."\\".ucfirst(strtolower($this->driver))."".ucfirst(strtolower($this->adapter));
            return new $class($this->config, $this->database, $this->options, $this->format, $this->prefix, $this->other_base);
		} else {
		    return null;
		}
    }

    public function setOptions(array $options = [])
    {
        if (isset($options)) {
            $this->options = $options;
        }
    }

    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    // Set DataBase Name
    public function setDatabase($database = null)
    {
        if (isset($database)) {
            $this->database = $database;
        }
    }

    // Get DataBase Name
    public function getDatabase()
    {
        return $this->database;
    }

    // Установить пространство имен
    public function setNamespace($namespace = null)
    {
        if (isset($namespace)) {
            $this->namespace = $namespace;
        }
    }

    // Get DataBase Namespace
    public function getNamespace()
    {
        return $this->namespace ?? null;
    }

    // Установить название адаптера
    public function setAdapter($adapter = null)
    {
        if (isset($adapter)) {
            $this->adapter = $adapter;
        }
    }

    // Get DataBase Adapter
    public function getAdapter()
    {
        return $this->adapter ?? null;
    }

    // Set DataBase Driver
	public function setDriver(string $driver = null)
	{
        if (isset($driver)) {
            $this->driver = $driver;
        }
	}

    // Get DataBase Driver
    public function getDriver()
    {
        return $this->driver ?? null;
    }

    // Set DataBase Prefix
	public function setPrefix(string $prefix = null)
	{
        if (isset($prefix)) {
            $this->prefix = $prefix;
			}
	}

	// Get DataBase Prefix
	public function getPrefix()
	{
        return $this->prefix ?? null;
	}

    // Set Logger
    public function setLogger($logger = null)
    {
        if (isset($logger)) {
            $this->logger = $logger;
        }
    }

    // Set Mailer
    public function setMailer($mailer = null)
    {
        if (isset($mailer)) {
            $this->mailer = $mailer;
        }
    }

}
 