<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2025
 * @license AGPLv3+ADD-PXN-V1
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

use \pxn\phpUtils\xPaths;


class dbPool {

	const DEFAULT_POOL_NAME = 'main';

	// max connections per pool
	const MAX_CONNECTIONS = 5;

	protected static array $pools = [];

	protected ?array $conns = null;
	protected int $maxConns = \PHP_INT_MIN;

	protected ?string   $pool_name = null;
	protected ?dbDriver $driver    = null;

	public  array $schemas = [];
	public ?array $existing_tables = null;



	public static function LoadAll(?string $path=null): int {
		if (empty($path))
			$path = xPaths::common();
		$count = 0;
		foreach (\scandir($path) as $entry) {
			$file_path = $path.'/'.$entry;
			if (\is_file($file_path)) {
				if ($entry === '.htdb'
				|| \str_starts_with(haystack: $entry, needle: '.htdb_')) {
					$pool_name = ($entry==='.htdb' ? null : \mb_substr($entry, 6));
					$pool = self::Load($pool_name, require($file_path));
					if ($pool != null)
						$count++;
				}
			}
		}
		return $count;
	}
	public static function Load(?string $pool_name, array $cfg): self {
		if (empty($pool_name)) $pool_name = self::DEFAULT_POOL_NAME;
		$pool = new self($pool_name);
		$conn = new dbConn(
			pool:      $pool,
			pool_name: $pool_name,
			driver:    (isset($cfg['driver'  ]) ? $cfg['driver'  ] : ''),
			host:      (isset($cfg['host'    ]) ? $cfg['host'    ] : ''),
			port:      (isset($cfg['port'    ]) ? $cfg['port'    ] : 0 ),
			user:      (isset($cfg['user'    ]) ? $cfg['user'    ] : ''),
			pass:      (isset($cfg['pass'    ]) ? $cfg['pass'    ] : ''),
			database:  (isset($cfg['database']) ? $cfg['database'] : ''),
			prefix:    (isset($cfg['prefix'  ]) ? $cfg['prefix'  ] : '')
		);
		unset($cfg);
		$pool->setFirstConnection($conn);
		self::$pools[$pool_name] = $pool;
		return $pool;
	}



	public static function GetPools(): array {
		return self::$pools;
	}
	public static function GetPool(string|dbConn|dbPool|null $pool=null): ?self {
		if ($pool === null) $pool = self::DEFAULT_POOL_NAME;
		if ($pool instanceof dbPool) return $pool;
		if ($pool instanceof dbConn) return $pool->getPool();
		if (isset(self::$pools[(string)$pool]))
			return self::$pools[(string)$pool];
		return null;
	}
	public static function GetDB(string|dbConn|dbPool|null $pool=null): dbConn {
		if ($pool === null) $pool = self::DEFAULT_POOL_NAME;
		if ($pool instanceof dbConn) return $pool;
		if ($pool instanceof dbPool) return $pool->get();
		$pool = self::GetPool($pool);
		if ($pool !== null)
			return $pool->get();
		return null;
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
				throw new \RuntimeException('Max connections reached for database: '.$this->pool_name);
			$conn = \reset($this->conns);
			$found = $conn->clone_conn();
			$this->conns[] = $found;
		}
		$found->lock();
		$found->clean(true);
		return $found;
	}



	public function __construct(string $pool_name) {
		$this->pool_name = $pool_name;
	}
	public function setFirstConnection(dbConn $conn): void {
		if ($this->conns !== null)
			throw new \RuntimeException('Database connections already initialised?');
		$this->conns  = [ $conn ];
		$this->driver = $conn->getDriver();
	}



	public function getMaxConnections(): int {
		return ($this->maxConns>0 ? $this->maxConns : self::MAX_CONNECTIONS);
	}
	public function setMaxConnections(int $max): void {
		$this->maxConns = $max;
	}

	public function getConnectionCount(): int {
		return \count($this->conns);
	}



	public function getPoolName(): string {
		return $this->pool_name;
	}
	public function getDriver(): dbDriver {
		return $this->driver;
	}



	public static function isDB(string $pool_name): bool {
		return (
			!isset(self::$pools[$pool_name]) &&
			self::$pools[$pool_name] != null
		);
	}



	public function getRealTables(): array {
		$this->loadRealTables();
		if ($this->existing_tables == null)
			throw new \RuntimeException('Failed to load existing tables from database');
		return $this->existing_tables;
	}
	public function hasRealTable(string $table_name): bool {
		$this->loadRealTables();
		if ($this->existing_tables == null)
			throw new \RuntimeException('Failed to load existing tables from database');
		return isset($this->existing_tables[$table_name]);
	}



}
