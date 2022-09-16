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
//use pxn\phpUtils\System;
//use pxn\phpUtils\Defines;


class dbPool {

	const DEFAULT_DB_NAME = 'main';

	// max connections per pool
	const MAX_CONNECTIONS = 5;

	// pools[name]
	protected static $pools = [];

	protected $dbName = null;

	// conns[index]
	protected $conns = [];
	protected int $maxConns = \PHP_INT_MIN;

	protected $schemas = [];



	public static function Load(array $cfg): dbPool {
		$dbName   = (isset($cfg['name'    ]) ? $cfg['name'    ] : 'main');
		$conn = new dbConn(
			dbName:   $dbName,
			driver:   (isset($cfg['driver'  ]) ? $cfg['driver'  ] : ''),
			host:     (isset($cfg['host'    ]) ? $cfg['host'    ] : ''),
			port:     (isset($cfg['port'    ]) ? $cfg['port'    ] : 0 ),
			user:     (isset($cfg['user'    ]) ? $cfg['user'    ] : ''),
			pass:     (isset($cfg['pass'    ]) ? $cfg['pass'    ] : ''),
			database: (isset($cfg['database']) ? $cfg['database'] : ''),
			prefix:   (isset($cfg['prefix'  ]) ? $cfg['prefix'  ] : '')
		);
		unset($cfg);
		$pool = new self(
			$dbName,
			$conn
		);
		self::$pools[$dbName] = $pool;
		return $pool;
	}



	public static function getPools(): array {
		return self::$pools;
	}
	public static function GetPool(?string $dbName=null): ?dbPool {
		// default main db
		if (empty($dbName))
			$dbName = self::DEFAULT_DB_NAME;
		if (isset(self::$pools[$dbName]))
			return self::$pools[$dbName];
		return null;
	}
	public static function Get(?string|dbPool $pool=null): dbConn {
		// already proper type
		if ($pool instanceof dbPool)
			return $pool->getDB();
		// by db pool name
		$dbName = (string)$pool;
		$p = self::GetPool($dbName);
		// db pool doesn't exist
		if ($p == null)
			throw new \RuntimeException('Unknown database pool: '.$dbName);
		return $p->getDB();
	}
	public function getDB(): dbConn {
		// find available
		$found = null;
		foreach ($this->conns as $conn) {
			if ($conn->isLocked())
				continue;
			$found = $conn;
			break;
		}
		// all in use
		if ($found == null) {
			if (\count($this->conns) >= $this->getMaxConnections())
				throw new \RuntimeException('Max connections reached for database: '.$this->dbName);
			$conn = \reset($this->conns);
			$found = $conn->cloneConn();
		}
		$found->lock();
		$found->clean();
		return $found;
	}



	public function __construct($dbName, $conn) {
		$this->dbName = $dbName;
		$this->conns[] = $conn;
	}



	public function getMaxConnections(): int {
		if ($this->maxConns >= 0)
			return $this->maxConns;
		return self::MAX_CONNECTIONS;
	}
	public function setMaxConnections(int $max): void {
		$this->maxConns = $max;
	}

	public function getConnCount(): int {
		return \count($this->conns);
	}



	public function getName(): string {
		return $this->dbName;
	}



	public static function isDB(string $dbName): bool {
		if (!isset(self::$pools[$dbName]))
			return false;
		return (self::$pools[$dbName] != null);
	}



}
/*
	public function ReloadExistingTableCache() {
		$this->existing = NULL;
		$this->LoadExistingTables();
	}
	protected function LoadExistingTables() {
		if (\is_array($this->existing)) {
			return TRUE;
		}
		// get list of existing tables
		$this->existing = [];
		$db = $this->getDB();
		if ($db == NULL) {
			fail('Failed to get db connection for tables list!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$db->Execute(
			"SHOW TABLES",
			'LoadPoolTables()'
		);
		$databaseName = $db->getDatabaseName();
		while ($db->hasNext()) {
			$tableName = $db->getString("Tables_in_{$databaseName}");
			if (Strings::StartsWith($tableName, '_')) {
				continue;
			}
			$this->existing[$tableName] = NULL;
		}
		$db->release();
		return FALSE;
	}



	// $schema argument can be path string to class or a class instance object
	public function addSchemaTable($tableName, $schema) {
		$tableName = dbTable::ValidateTableName($tableName);
		$schema    = dbTable::ValidateSchemaClass($schema);
		// table schema already exists
		if (\array_key_exists($tableName, $this->schemas)) {
			$poolName = $this->getName();
			fail("Table already added to pool: {$poolName}:{$tableName}",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$this->schemas[$tableName] = $schema;
		return TRUE;
	}
	public function addSchemaTables(array $schemas) {
		if (\count($schemas) == 0) {
			return FALSE;
		}
		$count = 0;
		foreach ($schemas as $entryName => $entry) {
			$result = self::addSchemaTable($entryName, $entry);
			if ($result !== FALSE) {
				$count++;
			}
		}
		return $count;
	}
	public function getSchemaTable($table) {
		if ($table instanceof \pxn\pxdb\dbTable) {
			return $table;
		}
		$tableName = dbTable::ValidateTableName(
			(string) $table
		);
		if (\array_key_exists($tableName, $this->schemas)) {
			$schema = $this->schemas[$tableName];
			$clss = dbTable::GetSchemaClass(
				$schema,
				$this,
				$tableName
			);
			return $clss;
		}
		return NULL;
	}
	public function getSchemaTables() {
		return $this->schemas;
	}



	public function hasExistingTable($tableName) {
		$this->LoadExistingTables();
		$tableName = dbTable::ValidateTableName($tableName);
		return \array_key_exists($tableName, $this->existing);
	}
	public function hasSchemaTable($tableName) {
		$tableName = dbTable::ValidateTableName($tableName);
		return \array_key_exists($tableName, $this->schemas);
	}



	public function getExistingTable($table) {
		if ($table instanceof \pxn\pxdb\dbTable) {
			return $table;
		}
		$this->LoadExistingTables();
		$tableName = dbTable::ValidateTableName(
			(string) $table
		);
		if (\array_key_exists($tableName, $this->existing)) {
			$existing = $this->existing[$tableName];
			// load table object
			if ($existing === NULL) {
				$existing = new dbTableExisting($this, $tableName);
				$this->existing[$tableName] = $existing;
			}
			return $existing;
		}
        return NULL;
	}
	public function getExistingTables() {
		$this->LoadExistingTables();
		return $this->existing;
	}



}
*/
