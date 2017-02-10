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
use pxn\phpUtils\System;
use pxn\phpUtils\Defines;


class dbPool {

	const dbNameDefault = 'main';
	const MaxConnections = 5;  // max connections per pool

	// pools[name]
	protected static $pools = [];

	protected $dbName = NULL;
	// conns[index]
	protected $conns   = [];

	protected $usingTables = [];



	public static function configure(
		$dbName,
		$driver,
		$host,
		$port,
		$u,
		$p,
		$database,
		$prefix
	) {
		$conn = new dbConn(
			$dbName,
			$driver,
			$host,
			$port,
			$u,
			$p,
			$database,
			$prefix
		);
		unset($u, $p);
		$pool = new self(
			$dbName,
			$conn
		);
		self::$pools[$dbName] = $pool;
		return $pool;
	}
	public function __construct($dbName, $conn) {
		$this->dbName = $dbName;
		$this->conns[] = $conn;
	}



	public static function get($dbName=NULL, $errorMode=NULL) {
		$pool = self::getPool($dbName);
		if ($pool == NULL) {
			return NULL;
		}
		$db = $pool->getDB($errorMode);
		return $db;
	}
	public static function getPool($dbName=NULL) {
		// already pool instance
		if ($dbName != NULL && $dbName instanceof dbPool) {
			return $dbName;
		}
		// default db
		if (empty($dbName)) {
			$dbName = self::dbNameDefault;
		}
		$dbName = (string) $dbName;
		// db pool doesn't exist
		if (!self::dbExists($dbName)) {
			fail("Database isn't configured: $dbName",
				Defines::EXIT_CODE_CONFIG_ERROR);
		}
		return self::$pools[$dbName];
	}
	public function getDB($errorMode=NULL) {
		if ($errorMode === NULL) {
			$errorMode = dbConn::ERROR_MODE_EXCEPTION;
		}
		// get db connection
		$found = NULL;
		// find unused
		foreach ($this->conns as $conn) {
			// connection in use
			if ($conn->inUse())
				continue;
			// available connection
			$found = $conn;
			$found->setErrorMode($errorMode);
			break;
		}
		// clone if in use
		if ($found === NULL) {
			if (\count($this->conns) >= self::MaxConnections) {
				fail("Max connections reached for database: $dbName",
					Defines::EXIT_CODE_IO_ERROR);
			}
			// get first connection
			$conn = \reset($this->conns);
			// clone the connection
			$found = $conn->cloneConn();
		}
		$found->lock();
		$found->clean();
		$found->setErrorMode($errorMode);
		return $found;
	}



	public static function dbExists($dbName=NULL) {
		if (empty($dbName)) {
			$dbName = self::$dbNameDefault;
		}
		return isset(self::$pools[$dbName]) && self::$pools[$dbName] != NULL;
	}
	public static function getPools() {
		return self::$pools;
	}



	public static function getPoolName($pool=NULL) {
		$p = dbPool::getPool($pool);
		if ($p == NULL) {
			return NULL;
		}
		return $p->getName();
	}
	public function getName() {
		return $this->dbName;
	}
	public static function castPoolName($pool) {
		if (\is_string($pool)) {
			return (string) $pool;
		}
		if ($pool instanceof \pxn\pxdb\dbPool) {
			return $pool->getName();
		}
		return NULL;
	}



	public function getConnCount() {
		return \count($this->conns);
	}



	#########################
	## get tables / fields ##
	#########################



	// format: [ 'table_name' => 'path\\to\\schema\\class', .. ]
	public function addUsingTables(array $tables) {
		$this->usingTables = \array_merge(
			$this->usingTables,
			$tables
		);
	}
	public function getUsingTables() {
		$result = [];
		foreach ($this->usingTables as $tableName => $schemaClass) {
			$name = San::AlphaNumUnderscore($tableName);
			if (empty($name)) {
				fail('Invalid or missing table name!',
					Defines::EXIT_CODE_INVALID_FORMAT);
			}
			if (Strings::StartsWith($tableName, '_')) {
				fail("Invalid table name, cannot start with _ underscore: $tableName",
					Defines::EXIT_CODE_INVALID_FORMAT);
			}
			$schema = new $schemaClass();
			if (! $schema instanceof \pxn\pxdb\dbSchema) {
				fail("Invalid db schema class for table: $schemaClass",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
			$result[$name] = $schema;
		}
		return $result;
	}



}
