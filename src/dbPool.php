<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

use \pxn\phpUtils\xPaths;


class dbPool {

	const DEFAULT_DB_NAME = 'main';

	// max connections per pool
	const MAX_CONNECTIONS = 5;

	protected static array $pools = [];

	protected ?array $conns = null;
	protected int $maxConns = \PHP_INT_MIN;

	protected ?string   $dbName = null;
	protected ?dbDriver $driver = null;

	public  array $schemas = [];
	public ?array $existing_tables = null;



	public static function Load(array $cfg): self {
		$dbName = (isset($cfg['name']) ? $cfg['name'] : 'main');
		$pool = new self($dbName);
		$conn = new dbConn(
			pool:     $pool,
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
		$pool->setFirstConnection($conn);
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
	public static function GetDB(string|dbConn|dbPool $pool='main'): dbConn {
		// already proper type
		if ($pool instanceof dbPool)
			return $pool->get();
		// by db pool name
		$dbName = (string) $pool;
		$p = self::GetPool($dbName);
		// db pool doesn't exist
		if ($p == null)
			throw new \RuntimeException('Unknown database pool: '.$dbName);
		return $p->get();
	}
	public function get(): dbConn {
		// find available
		$found = null;
		foreach ($this->conns as $conn) {
			if (!$conn->isLocked()) {
				$found = $conn;
				break;
			}
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



	public function __construct(string $dbName) {
		$this->dbName = $dbName;
	}
	public function setFirstConnection(dbConn $conn): void {
		if ($this->conns !== null)
			throw new \RuntimeException('Database connections already initialised?');
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



	public function getRealTables(): array {
		$this->loadRealTables();
		return $this->existing_tables;
	}
	public function hasRealTable(string $tableName): bool {
		$this->loadRealTables();
		if ($this->existing_tables == null)
			throw new \RuntimeException('Failed to load existing tables from database');
		return isset($this->existing_tables[$tableName]);
	}
	public function hasTableSchema(string $tableName): bool {
		return isset($this->schemas[$tableName]);
	}

	public function getRealTableSchema(string $tableName): ?array {
		$this->loadRealTables();
		if (isset($this->existing_tables[$tableName]))
			return $this->existing_tables[$tableName];
		return null;
	}
	public function getTableSchema(string $tableName): ?array {
		$this->loadRealTables();
		if (isset($this->schemas[$tableName]))
			return $this->schemas[$tableName];
		$this->loadRealTableFields($tableName);
		if (isset($this->schemas[$tableName]))
			return $this->schemas[$tableName];
		return null;
	}

	protected function loadRealTables(): void {
		if (\is_array($this->existing_tables))
			return;
		$db = $this->get();
		// get list of existing tables
		$found = [];
		switch ($this->driver) {
			case dbDriver::sqLite:
				$sql = "SELECT `tbl_name`,`sql` FROM `sqlite_master` WHERE `type`='table' ORDER BY `tbl_name`;";
				break;
			case dbDriver::MySQL:
				$sql = 'SHOW TABLES;';
				break;
			default:
				$db->release();
				throw new \RuntimeException('Unknown database driver type');
		}
		$db->exec($sql);
		$database = $db->getDatabaseName();
		while ($db->hasNext()) {
//TODO: for mysql
//			$table_name = $db->getString('Tables_in_'.$database);
			$table_name = $db->getString('tbl_name');
			if (\str_starts_with($table_name, '_'))
				continue;
			if (\str_starts_with($table_name, 'sqlite_'))
				continue;
			$fields = $db->getString('sql');
			$found[$table_name] = [];
			{
				$pos = \mb_strpos($fields, '(');
				if ($pos <= 0) throw new \RuntimeException('Failed to find table fields for: '.$table_name);
				$tmp = \mb_substr($fields, $pos+1);
				$pos = \mb_strpos($tmp, ')');
				if ($pos <= 0) throw new \RuntimeException('Failed to parse table fields for: '.$table_name);
				$tmp = \mb_substr($tmp, 0, $pos);
				$arr = \explode(',', $tmp);
				foreach ($arr as $part) {
					$part = \trim($part);
					$parts = \explode(' ', $part);
					$field_name = \trim(\array_shift($parts));
					if (\str_starts_with($field_name, '`')
					&&    \str_ends_with($field_name, '`'))
						$field_name = \mb_substr($field_name, 1, -1);
					$type = null;
					$primary = false;
					$autoinc = false;
					foreach ($parts as $p) {
						switch (\mb_strtolower($p)) {
							case 'integer':  $type = 'INTEGER';  break;
							case 'text':     $type = 'TEXT';     break;
							case 'datetime': $type = 'DATETIME'; break;
							case 'primary':       $primary = true; break;
							case 'autoincrement': $autoinc = true; break;
							default: break;
						}
					}
					if ($type == null)
						throw new \RuntimeException("Unknown field type in table: $table_name - $part");
					$found[$table_name][$field_name] = [
						'type' => $type,
					];
					if ($primary)
						$found[$table_name][$field_name]['primary'] = true;
					if ($autoinc)
						$found[$table_name][$field_name]['autoinc'] = true;
				}
			}
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
