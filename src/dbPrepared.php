<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

use \pxn\phpUtils\Debug;
use \pxn\phpUtils\utils\GeneralUtils;


abstract class dbPrepared {

	protected ?dbPool $pool = null;

	protected ?\PDOStatement $st = null;
	protected ?string $sql  = null;
	protected ?string $desc = null;

	protected bool $dry = false;

	protected $args = [];
	protected $row = null;

	protected int $insert_id = -1;
	protected int $row_count = -1;



	public function __construct(dbPool $pool) {
		$this->pool = $pool;
		$this->clean(true);
	}



	public abstract function clone_conn(): self;

	protected abstract function doConnect(): bool;
	public abstract function getRealConnection(): \PDO;

	public abstract function getPoolName(): string;
	public abstract function getDriver(): dbDriver;

	public abstract function getDatabaseName(): string;
	public abstract function getTablePrefix(): string;

	public abstract function lock(): self;
	public abstract function release(): self;



	public function clean(bool $all=false): void {
		$this->st   = null;
		$this->sql  = null;
		$this->desc = null;
		$this->row  = null;
		$this->insert_id = -1;
		$this->row_count = -1;
		if ($all)
			$this->args = [];
	}



	public function dry(?bool $dry=null): bool {
		if ($dry !== null) {
			$previous = $this->dry;
			$this->dry = ($dry !== false);
			return $previous;
		}
		return $this->dry;
	}
	public function setDry(bool $dry): self {
		$this->dry = ($dry !== false);
	}



	public function prepare(string $sql, bool $clean_all=true): self {
		$this->clean($clean_all);
		$sql = \str_replace('__TABLE__', $this->getTablePrefix(), $sql);
		if (empty($sql)) throw new \RuntimeException('sql argument is required');
		$this->sql = $sql;
		// prepared statement
		$connection = $this->getRealConnection();
		try {
			$this->st = $connection->prepare($this->sql);
		} catch (\PDOException $e) {
			$sql = $this->sql;
			\error_log("[SQL-Error: $sql]");
			throw $e;
		}
		return $this;
	}


	public function exec(?string $sql=null): self {
		if (!empty($sql)) { $this->prepare($sql, false); unset($sql); }
		if (empty($this->sql)) throw new \RuntimeException('No sql query provided');
		if ($this->st == null) throw new \RuntimeException('Statement not prepared');
		$this->sql = \trim($this->sql);
		$pos = \mb_strpos($this->sql, ' ');
		$cmd = \mb_strtoupper($pos > 0 ? \mb_substr($this->sql, 0, $pos) : $this->sql);
		// run query
		if (Debug::debug()) {
			$sql_clean = $this->sql;
			while (\mb_strpos($sql_clean, '  ') !== false)
				$sql_clean = \str_replace('  ', ' ', $sql_clean);
			$params = '';
			foreach ($this->args as $index=>$value)
				$params .= "  #$index: $value";
			\error_log('[QUERY] '.$sql_clean.$params);
		}
		if (!$this->st->execute())
			throw new \RuntimeException('Query failed');
		// get insert id
		if ($cmd == 'INSERT') {
			$connection = $this->getRealConnection();
			$id = $connection->lastInsertId();
			$this->insert_id = ($id === false ? -1 : (int) $id);
		// get row count (not available in sqlite)
		} else {
			$this->row_count = $this->st->rowCount();
		}
		return $this;
	}



	public function hasNext(): bool {
		$this->row = $this->st->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
		return ($this->row !== false);
	}

	public function getInsertID(): int {
		return $this->insert_id;
	}
	public function getRowCount(): int {
		return $this->row_count;
	}



	public function getPool(): dbPool {
		return $this->pool;
	}
	public function getRow(): array {
		return $this->row;
	}



	public function getString(int|string $index): ?string {
		return (
			isset($this->row[$index])
			? GeneralUtils::CastType($this->row[$index], 's')
			: null
		);
	}
	public function getInt(int|string $index): ?int {
		return (
			isset($this->row[$index])
			? GeneralUtils::CastType($this->row[$index], 'i')
			: null
		);
	}
	public function getFloat(int|string $index): ?float {
		return (
			isset($this->row[$index])
			? GeneralUtils::CastType($this->row[$index], 'f')
			: null
		);
	}
//TODO: does this work?
	public function getLong(int|string $index): ?long {
		return (
			isset($this->row[$index])
			? GeneralUtils::CastType($this->row[$index], 'l')
			: null
		);
	}
	public function getBool(int|string $index): ?bool {
		return (
			isset($this->row[$index])
			? GeneralUtils::CastType($this->row[$index], 'b')
			: null
		);
	}
//TODO
//	public function getDate(int|string $index): string {
//	}
//TODO: old
//	public function getDate(int $index, ?string $format=null): string {
//		if ($this->hasError() || $this->row == null || !isset($this->row[$index]))
//			return false;
//		$value = General::castType($this->row[$index], 'int');
//		if ($value === false || $value === null)
//			return false;
//		if (empty($format))
//			$format = 'Y-m-d H:i:s';
//		return \date($format, $value);
//	}



	public function setString(int|string $index, string $value): self {
		$this->st->bindParam($index, $value, \PDO::PARAM_STR);
		$this->args[$index] = $value;
		return $this;
	}
	public function setInt(int|string $index, int $value): self {
		$this->st->bindParam($index, $value, \PDO::PARAM_INT);
		$this->args[$index] = $value;
		return $this;
	}
	public function setFloat(int|string $index, float $value): self {
		$this->st->bindParam($index, $value, \PDO::PARAM_FLOAT);
		$this->args[$index] = $value;
		return $this;
	}
	public function setLong(int|string $index, long $value): self {
		$this->st->bindParam($index, $value, \PDO::PARAM_LONG);
		$this->args[$index] = $value;
		return $this;
	}
	public function setBool(int|string $index, bool $value): self {
		$this->st->bindParam($index, $value, \PDO::PARAM_BOOL);
		$this->args[$index] = $value;
		return $this;
	}
//TODO
//	public function setDateTime(int|string $index, string $value): self {
//		$this->st->bindParam($index, $value);
//		return $this;
//	}
//TODO: old
//	public function setDate(int $index, $value): self {
//		if ($this->hasError() || $this->st == null) {
//			return null;
//		}
//		try {
//			$value = General::castType($value, 'str');
//			$this->st->bindParam($index, $value);
//			$this->args[] = "Date: $value";
//		} catch (\PDOException $e) {
//			$sql  = $this->sql;
//			$desc = $this->desc;
//			$this->setError("Query failed: $sql - $desc", $e);
//			return null;
//		}
//		return $this;
//	}



}
