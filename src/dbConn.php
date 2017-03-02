<?php
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2017
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\pxdb;

use pxn\phpUtils\Strings;
use pxn\phpUtils\San;
use pxn\phpUtils\Defines;


class dbConn extends dbPrepared {

	const ERROR_MODE_EXCEPTION = FALSE;
	const ERROR_MODE_PASSIVE   = TRUE;

	protected $dbName = NULL;
	protected $u      = NULL;
	protected $p      = NULL;
	protected $database = NULL;
	protected $prefix = NULL;
	protected $dsn    = NULL;

	protected $connection = NULL;
	protected $used       = FALSE;



	// new connection
	public function __construct(
		$dbName,
		$driver,
		$host,
		$port,
		$u,
		$p,
		$database,
		$prefix
	) {
		parent::__construct();
		$this->dbName = San::AlphaNumUnderscore( (string) $dbName );
		if (empty($this->dbName)) {
			fail('Database name is missing or invalid!',
				Defines::EXIT_CODE_CONFIG_ERROR);
		}
		$this->u        = (empty($u) ? 'ro'.'ot' : $u);
		$this->p        = $p;
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
			fail("Failed to generate DSN for database: $dbName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
//TODO: is anything missing here?
		if (\debug()) {
			$this->doConnect();
		}
	}
	public function cloneConn() {
		$conn = new self(
			$this->dbName,
			$this->dsn,
			$this->u,
			$this->p,
			$this->database,
			$this->prefix
		);
		return $conn;
	}



	// connect to database
	private function doConnect() {
		if ($this->connection != NULL) {
			return FALSE;
		}
		try {
			$options = [
				\PDO::ATTR_PERSISTENT         => TRUE,
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
			];
			$this->connection = new \PDO(
				$this->dsn,
				$this->u,
				\base64_decode($this->p),
				$options
			);
		} catch (\PDOException $e) {
			$this->connection = NULL;
			$dbName = $this->dbName;
			$dsn    = $this->dsn;
			fail("Failed to connect to database: $dbName - $dsn",
				Defines::EXIT_CODE_CONFIG_ERROR, $e);
		}
		return TRUE;
	}



	public function getConn() {
		$this->doConnect();
		return $this->connection;
	}
	public function getDatabaseName() {
		return $this->database;
	}
	public function getTablePrefix() {
		if (empty($this->prefix)) {
			return '';
		}
		return $this->prefix;
	}



	public function inUse() {
		return $this->used;
	}
	public function isLocked() {
		return $this->inUse();
	}
	public function lock() {
		if ($this->used == TRUE) {
			$dbName = $this->dbName;
			fail("Database already locked: $dbName",
				Defines::EXIT_CODE_USAGE_ERROR);
		}
		$this->used = TRUE;
	}
	public function release() {
		$this->clean();
		$this->used = FALSE;
	}



	public static function BuildDSN(
		$driver,
		$database,
		$host,
		$port
	) {
		$driver   = (string) $driver;
		$database = (string) $database;
		$host     = (string) $host;
		$port     = (int)    $port;
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



}
