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
//use pxn\phpUtils\San;
//use pxn\phpUtils\Defines;


class dbConn extends dbPrepared {

	protected ?string $dbName   = null;
	protected ?string $user     = null;
	protected ?string $pass     = null;
	protected ?string $database = null;
	protected ?string $prefix   = null;

	protected ?string $dsn = null;

//	protected $connection = null;
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
		$this->dbName = San::AlphaNumUnderscore( (string) $dbName );
		if (empty($this->dbName)) {
			fail('Database name is missing or invalid!');
			exit(1);
		}
		$this->user     = (empty($user) ? 'root' : $user);
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
		if (empty($this->dsn)) {
			fail("Failed to generate DSN for database: $dbName");
			exit(1);
		}
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
		$dsn = \strtolower($driver).':';
		// unix socket
		if (Strings::StartsWith($host, '/')) {
			$dsn .= "unix_socket={$host}";
		// normal tcp
		} else {
			$dsn .= "host={$host}";
			if ($port != NULL && $port > 0 && $port != 3306) {
				$dsn .= ";port={$port}";
			}
		}
		return "{$dsn};dbname={$database};charset=utf8mb4";
	}



	// connect to database
	private function doConnect(): void {
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
			fail("Failed to connect to database: $dbName - $dsn", $e);
			exit(1);
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
		if ($this->locked == true) {
			fail('Database already locked: '.$this->dbName,
				Defines::EXIT_CODE_USAGE_ERROR);
		}
		$this->locked = true;
	}
	public function release() {
		$this->clean();
		$this->locked = false;
	}



}
