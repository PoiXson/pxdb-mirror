<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2022
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

//use pxn\phpUtils\General;
//use pxn\phpUtils\Strings;


abstract class dbPrepared {

	protected ?\PDOStatement $st = null;
//TODO: type of $rs
	protected ?string $rs   = null;
	protected ?string $sql  = null;
	protected ?string $desc = null;

	protected bool $dry = false;

	protected $row = null;
	protected array $args = [];
	protected int $insert_id = -1;
	protected int $row_count = -1;



	public function __construct() {
		$this->clean();
	}



	public abstract function clone_conn(): self;

	protected abstract function doConnect(): bool;
	public abstract function getRealConnection();

	public abstract function getDriverString(): string;
	public abstract function getDriverType(): dbDriver;

	public abstract function getDatabaseName(): string;
	public abstract function getTablePrefix(): string;

	public abstract function lock();
	public abstract function release();



	public function clean() {
		$this->st   = null;
		$this->rs   = null;
		$this->sql  = null;
		$this->desc = null;
		$this->row  = null;
		$this->args = [];
		$this->insert_id = -1;
		$this->row_count = -1;
	}



	public function prepare(string $sql): self {
		$this->clean();
		$sql = \str_replace('__TABLE__', $this->getTablePrefix(), $sql);
		if (empty($sql)) throw new \RuntimeException('sql argument is required');
		$this->sql = $sql;
		// prepared statement
		$connection = $this->getRealConnection();
		try {
			$this->st = $connection->prepare($this->sql);
		} catch (\PDOException $e) {
			$sql = $this->sql;
			echo "\n[SQL-Error: $sql]\n";
			throw $e;
		}
		return $this;
	}


	public function exec(string $sql=''): self {
		if (!empty($sql)) {
			$this->prepare($sql);
			unset($sql);
		}
		if (empty($this->sql)) throw new \RuntimeException('No sql query provided');
		if ($this->st == null) throw new \RuntimeException('Statement not prepared');
		$this->sql = \trim($this->sql);
		$pos = \mb_strpos($this->sql, ' ');
		$cmd = (
			$pos > 0
			? \mb_substr($this->sql, 0, $pos)
			: $this->sql
		);
		$cmd = \mb_strtoupper($cmd);
		// run query
		if (!$this->st->execute())
			throw new \RuntimeException('Query failed');
		// get insert id
		if ($cmd == 'INSERT') {
			$connection = $this->getRealConnection();
			$this->insert_id = $connection->lastInsertId();
		// get row count
		} else {
			$this->row_count = $this->st->rowCount();
		}
		return $this;
	}



	public function hasNext(): bool {
		$this->row = $this->st->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
		return ($this->row !== false);
	}

	public function getIntertID(): int {
		return $this->insert_id;
	}
	public function getRowCount(): int {
		return $this->row_count;
	}



	public function getRow(): array {
		return $this->row;
	}



	public function getString(int|string $index): ?string {
		if (isset($this->row[$index]))
			return (string) $this->row[$index];
		return null;
	}
	public function getInt(int|string $index): ?int {
		if (isset($this->row[$index]))
			return (int) $this->row[$index];
		return null;
	}
	public function getFloat(int|string $index): ?float {
		if (isset($this->row[$index]))
			return (float) $this->row[$index];
		return null;
	}
	public function getLong(int|string $index): ?long {
		if (isset($this->row[$index]))
			return $this->row[$index];
		return null;
	}
	public function getBool(int|string $index): ?bool {
//TODO: is this right?
		if (isset($this->row[$index]))
			return ($this->row[$index] == true);
		return null;
	}
//TODO
//	public function getDate(int|string $index): string {
//	}
//TODO: old
//	public function getDate($index, $format=NULL) {
//		if ($this->hasError() || $this->row == NULL || !isset($this->row[$index])) {
//			return FALSE;
//		}
//		$value = General::castType($this->row[$index], 'int');
//		if ($value === FALSE || $value === NULL) {
//			return FALSE;
//		}
//		if (empty($format)) {
//			$format = 'Y-m-d H:i:s';
//		}
//		return \date($format, $value);
//	}



}
/*
	public function isDry() {
		// default
		if ($this->dry === NULL) {
			return FALSE;
		}
		// not dry
		if ($this->dry === FALSE) {
			return FALSE;
		}
		// is dry
		return TRUE;
	}
	public function notDry() {
		return !$this->isDry();
	}
	public function setDry($dry=TRUE) {
		$this->dry = ($dry !== FALSE);
	}



	// --------------------------------------------------
	// query parameters



	public function setString($index, $value) {
		if ($this->hasError() || $this->st == NULL) {
			return NULL;
		}
		try {
			$value = General::castType($value, 'str');
			$this->st->bindParam($index, $value);
			$this->args[] = "String: $value";
		} catch (\PDOException $e) {
			$sql  = $this->sql;
			$desc = $this->desc;
			$this->setError("Query failed: $sql - $desc", $e);
			return NULL;
		}
		return $this;
	}
	public function setInt($index, $value) {
		if ($this->hasError() || $this->st == NULL) {
			return NULL;
		}
		try {
			$value = General::castType($value, 'int');
			$this->st->bindParam($index, $value);
			$this->args[] = "Int: $value";
		} catch (\PDOException $e) {
			$sql  = $this->sql;
			$desc = $this->desc;
			$this->setError("Query failed: $sql - $desc", $e);
			return NULL;
		}
		return $this;
	}
	public function setDouble($index, $value) {
		if ($this->hasError() || $this->st == NULL) {
			return NULL;
		}
		try {
			$value = General::castType($value, 'dbl');
			$this->st->bindParam($index, $value);
			$this->args[] = "Dbl: $value";
		} catch (\PDOException $e) {
			$sql  = $this->sql;
			$desc = $this->desc;
			$this->setError("Query failed: $sql - $desc", $e);
			return NULL;
		}
		return $this;
	}
	public function setLong($index, $value) {
		if ($this->hasError() || $this->st == NULL) {
			return NULL;
		}
		try {
			$value = General::castType($value, 'lng');
			$this->st->bindParam($index, $value);
			$this->args[] = "Lng: $value";
		} catch (\PDOException $e) {
			$sql  = $this->sql;
			$desc = $this->desc;
			$this->setError("Query failed: $sql - $desc", $e);
			return NULL;
		}
		return $this;
	}
	public function setBool($index, $value) {
		if ($this->hasError() || $this->st == NULL) {
			return NULL;
		}
		try {
			$value = General::castType($value, 'bool');
			$this->st->bindParam($index, $value);
			$this->args[] = "Bool: $value";
		} catch (\PDOException $e) {
			$sql  = $this->sql;
			$desc = $this->desc;
			$this->setError("Query failed: $sql - $desc", $e);
			return NULL;
		}
		return $this;
	}
//	public function setDate($index, $value) {
//		if ($this->hasError() || $this->st == NULL) {
//			return NULL;
//		}
//		try {
//			$value = General::castType($value, 'str');
//			$this->st->bindParam($index, $value);
//			$this->args[] = "Date: $value";
//		} catch (\PDOException $e) {
//			$sql  = $this->sql;
//			$desc = $this->desc;
//			$this->setError("Query failed: $sql - $desc", $e);
//			return NULL;
//		}
//		return $this;
//	}
*/
