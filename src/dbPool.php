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
	protected static array $pools = [];

	protected ?string   $dbName = null;
	protected ?dbDriver $driver = null;

	protected array $conns = [];
	protected int $maxConns = \PHP_INT_MIN;

	protected  array $schemas = [];
	protected ?array $existing_tables = null;



	public static function Load(array $cfg): self {
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
		$pool = new self($dbName, $conn);
		self::$pools[$dbName] = $pool;
		return $pool;
	}



	public static function getPools(): array {
		return self::$pools;
	}
	public static function GetPool(?string $dbName=null): ?self {
		// default main db
		if (empty($dbName))
			$dbName = self::DEFAULT_DB_NAME;
		if (isset(self::$pools[$dbName]))
			return self::$pools[$dbName];
		return null;
	}
	public static function Get(string|self $pool='main'): dbConn {
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
			$found = $conn->clone_conn();
		}
		$found->lock();
		$found->clean();
		return $found;
	}



	public function __construct(string $dbName, dbConn $conn) {
		$this->dbName = $dbName;
		$this->conns  = [ $conn ];
		$this->driver = $conn->getDriverType();
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



	public function getTables(): array {
		$this->loadTables();
		return $this->existing_tables;
	}
	public function hasTable(string $tableName): bool {
		// load existing tables
		$this->loadTables();
		if ($this->existing_tables == null)
			throw new \RuntimeException('Failed to load existing tables from database');
		return isset($this->existing_tables[$tableName]);
	}
	public function hasTableSchema(string $tableName): bool {
		return isset($this->schemas[$tableName]);
	}

	protected function loadTables(): void {
		if (\is_array($this->existing_tables))
			return;
		$db = $this->getDB();
		// get list of existing tables
		$found = [];
		switch ($this->driver) {
			case dbDriver::sqLite:
				$sql = "SELECT `name` FROM `sqlite_master` WHERE `type`='table' ORDER BY name;";
				break;
			case dbDriver::MySQL:
				$sql = 'SHOW TABLES;';
				break;
			default: throw new \RuntimeException('Unknown database driver type');
		}
		$db->exec($sql);
		$database = $db->getDatabaseName();
		while ($db->hasNext()) {
//TODO: for mysql
//			$table = $db->getString('Tables_in_'.$database);
			$table = $db->getString('name');
			if (\str_starts_with($table, '_'))
				continue;
			$found[$table] = null;
		}
		$this->existing_tables = $found;
		$db->release();
	}
	public function clearTableCache(): int {
		$count = (
			$this->existing_tables == null
			? -1 : \count($this->existing_tables)
		);
		$this->existing_tables = null;
		return $count;
	}



}
/*
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
