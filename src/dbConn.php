<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

use \pxn\phpUtils\utils\SanUtils;
use \pxn\phpUtils\xPaths;


class dbConn extends dbPrepared {

	protected ?string   $pool_name = null;
	protected ?dbDriver $driver    = null;
	protected ?string   $host      = null;
	protected  int      $port      = 0;
	protected ?string   $user      = null;
	protected ?string   $pass      = null;
	protected ?string   $database  = null;
	protected ?string   $prefix    = null;

	protected ?\PDO $connection = null;
	protected ?string $dsn = null;
	protected bool $locked = false;



	// new connection
	public function __construct(
		dbPool $pool,
		string $pool_name,
		string|dbDriver $driver,
		string $host, int    $port,
		string $user, string $pass,
		string $database,
		string $prefix
	) {
		parent::__construct($pool);
		$this->pool_name = dbTools::ValidatePoolName($pool_name);
		$this->driver = dbDriver::FromString($driver);
		if ($this->driver == null) throw new \RuntimeException('Database driver is missing or invalid');
		switch ($this->driver) {
			case dbDriver::SQLite: break;
			case dbDriver::MySQL:
				if (empty($host) || $host == '127.0.0.1')
					$host = 'localhost';
				if ($port <= 0)   $port = 3306;
				if (empty($user)) $user = 'root';
				break;
			default: throw new \RuntimeException('Unknown database driver type for: '.$pool_name);
		}
		$this->host     = $host;
		$this->port     = $port;
		$this->user     = $user;
		$this->pass     = $pass;
		$this->database = $database;
		$this->prefix   = $prefix;
		// build data source name
		$this->dsn = self::BuildDSN(
			$this->driver,
			$database,
			$host,
			$port
		);
		if (empty($this->dsn))
			throw new \RuntimeException('Failed to generate DSN for database: '.$pool_name);
		$this->doConnect();
	}
	public function clone_conn(): self {
		return new self(
			$this->pool,
			$this->pool_name,
			$this->driver,
			$this->host, $this->port,
			$this->user, $this->pass,
			$this->database,
			$this->prefix
		);
	}



	public static function BuildDSN(dbDriver $driver, string $database, string $host, int $port): string {
		$drv = $driver->toString();
		switch ($driver) {
			case dbDriver::SQLite:
				if (!\str_starts_with(haystack: $database, needle: '/'))
					$database = xPaths::common().'/'.$database;
				return \mb_strtolower($drv).':'.$database;
			case dbDriver::MySQL:
				$dsn = \mb_strtolower($drv).':';
				// unix socket
				if (\str_starts_with(haystack: $host, needle: '/')) {
					$dsn .= 'unix_socket='.$host;
				// tcp
				} else {
					$dsn .= 'host='.$host;
					if ($port > 0 && $port != 3306)
						$dsn .= ';port='.$port;
				}
				return $dsn.'dbname='.$database.';charset=utf8mb4';
			default:
				throw new \RuntimeException('Unknown database type: '.$drv);
		}
	}



	// connect to database
	protected function doConnect(): bool {
		if ($this->connection != null)
			return false;
		try {
			$options = [
				\PDO::ATTR_PERSISTENT         => true,
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
			];
			$this->connection = new \PDO(
				$this->dsn,
				$this->user,
				\base64_decode($this->pass),
				$options
			);
			$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch (\PDOException $e) {
			$this->connection = null;
			$pool_name = $this->pool_name;
			$dsn       = $this->dsn;
			throw new \RuntimeException("Failed to connect to database: $pool_name - $dsn - ".$e->getMessage());
		}
		return true;
	}

	public function getRealConnection(): \PDO {
		$this->doConnect();
		return $this->connection;
	}



	public function getPoolName(): string {
		return $this->pool_name;
	}

	public function getDriver(): dbDriver {
		return $this->driver;
	}

	public function getDatabaseName(): string {
		return $this->database;
	}
	public function getTablePrefix(): string {
		return (empty($this->prefix) ? '' : $this->prefix);
	}



	public function isLocked(): bool {
		return $this->locked;
	}
	public function lock(): self {
		if ($this->locked == true)
			throw new \RuntimeException('Database already locked: '.$this->pool_name);
		$this->locked = true;
		return $this;
	}
	public function release(): self {
		$this->clean();
		$this->locked = false;
		return $this;
	}



}
