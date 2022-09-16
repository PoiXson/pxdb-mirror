<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2022
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

//use pxn\phpUtils\Strings;
use pxn\phpUtils\utils\SanUtils;
//use pxn\phpUtils\Defines;


class dbConn extends dbPrepared {

	protected ?string $dbName   = null;
	protected ?string $driver   = null;
	protected ?string $host     = null;
	protected  int    $port     = 0;
	protected ?string $user     = null;
	protected ?string $pass     = null;
	protected ?string $database = null;
	protected ?string $prefix   = null;

	protected ?string $dsn = null;

	protected $connection = null;
	protected bool $locked = false;



	// new connection
	public function __construct(
		string $dbName,
		string $driver,
		string $host, int    $port,
		string $user, string $pass,
		string $database,
		string $prefix
	) {
		parent::__construct();
		$this->dbName = SanUtils::alpha_num_simple( (string) $dbName );
		if (empty($this->dbName)) throw new \RuntimeException('Database name is missing or invalid');
		if (empty($driver))       throw new \RuntimeException('Database driver is missing or invalid');
		switch ($driver) {
			case 'sqlite':
				break;
			case 'mysql':
				if (empty($host) || $host == '127.0.0.1')
					$host = 'localhost';
				if ($port <= 0)   $port = 3306;
				if (empty($user)) $user = 'root';
				break;
			default:
				break;
		}
		$this->driver   = \str_to_lower(\trim($driver));
		$this->host     = $host;
		$this->port     = $port;
		$this->user     = $user;
		$this->pass     = $pass;
		$this->database = $database;
		$this->prefix   = $prefix;
		// build data source name
		$this->dsn = self::BuildDSN(
			$driver,
			$database,
			$host,
			$port
		);
		if (empty($this->dsn))
			throw new \RuntimeException('Failed to generate DSN for database: '.$dbName);
		$this->doConnect();
	}
	public function clone_conn(): self {
		return new self(
			$this->dbName,
			$this->dsn,
			$this->user,
			$this->pass,
			$this->database,
			$this->prefix
		);
	}



	public static function BuildDSN(
		string $driver,
		string $database,
		string $host, int $port
	) {
		switch ($driver) {
			case 'sqlite':
				return "$driver:$database";
			case 'mysql':
				$dsn = $driver.':';
				// unix socket
				if (\str_starts_with($host, '/')) {
					$dsn .= "$driver:unix_socket={$host}";
				// tcp
				} else {
					$dsn .= "$driver:host={$host}";
					if ($port > 0 && $port != 3306)
						$dsn .= ";port={$port}";
				}
				return "{$dsn};dbname={$database};charset=utf8mb4";
			default:
				throw new \RuntimeException("Unknown database type: $driver");
		}
	}



	// connect to database
	private function doConnect(): bool {
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
		} catch (\PDOException $e) {
			$this->connection = null;
			$dbName = $this->dbName;
			$dsn    = $this->dsn;
			throw new \RuntimeException("Failed to connect to database: $dbName - $dsn", $e);
		}
		return true;
	}



	public function getConn() {
		$this->doConnect();
		return $this->connection;
	}
	public function getDatabaseName(): string {
		return $this->database;
	}
	public function getTablePrefix(): string {
		return (empty($this->prefix) ? '' : $this->prefix);
	}



	public function isLocked() {
		return $this->locked;
	}
	public function lock() {
		if ($this->locked == true)
			throw new \RuntimeException('Database already locked: '.$this->dbName);
		$this->locked = true;
	}
	public function release() {
		$this->clean();
		$this->locked = false;
	}



	public function getDriver(): string {
		return $this->driver;
	}



}
