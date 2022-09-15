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



	public function __construct() {
	}



}
/*
//	const ARG_PRE   = '[';
//	const ARG_DELIM = '|';
//	const ARG_POST  = ']';

	protected $st   = NULL;
	protected $rs   = NULL;
	protected $sql  = NULL;
	protected $desc = NULL;
	protected $dry  = NULL;

	protected $row      = NULL;
	protected $args     = [];
	protected $rowCount = -1;
	protected $insertId = -1;

	protected $hasError = FALSE;
	protected $errorMode = dbConn::ERROR_MODE_EXCEPTION;



	public function __construct() {
		$this->clean();
	}



	public abstract function getConn();
	public abstract function getTablePrefix();



	public function Prepare($sql, $desc=NULL) {
		$this->clean(TRUE);
		if (!empty($desc)) {
			$this->desc($desc);
		}
		if (empty($sql)) {
			$this->setError('sql argument is required!');
			return NULL;
		}
		try {
			$this->sql = \str_replace(
					'__TABLE__',
					$this->getTablePrefix(),
					$sql
			);
			// prepared statement
			$this->st = $this->getConn()
					->prepare($this->sql);
		} catch (\PDOException $e) {
			$sql  = $this->sql;
			$desc = $this->desc;
			$this->setError("Query failed: $sql - $desc", $e);
			return NULL;
		}
		return $this;
	}



	public function Execute($sql=NULL, $desc=NULL) {
		if ($this->hasError()) {
			return NULL;
		}
		if (!empty($sql)) {
			$this->Prepare($sql, $desc);
		}
		if (empty($this->sql)) {
			$this->setError('No sql provided!');
			return NULL;
		}
		if ($this->st == NULL) {
			$this->setError('Statement not ready!');
			return NULL;
		}
		if ($this->hasError()) {
			$this->setError();
			return NULL;
		}
		try {
			$pos = \mb_strpos($this->sql, ' ');
			$firstPart = \mb_strtoupper(
				$pos === FALSE
				? $this->sql
				: \mb_substr($this->sql, 0, $pos)
			);
			if (debug()) {
				$msg = " [SQL] $this->sql";
				if (!Strings::EndsWith($this->sql, ';')) {
					$msg .= ' ;';
				}
				if (!empty($desc)) {
					$msg .= "  / * $desc * /";
				}
				echo "$msg\n";
			}
			if ($this->notDry()) {
				// run query
				if (!$this->st->execute()) {
					$this->setError();
					return NULL;
				}
				// get insert id
				if ($firstPart == 'INSERT') {
					$this->insertId = $this->conn->lastInsertId();
				// get row count
				} else {
					$this->rowCount = $this->st->rowCount();
				}
			}
		} catch (\PDOException $e) {
			$sql  = $this->sql;
			$desc = $this->desc;
			$this->setError("Query failed: $sql - $desc", $e);
			return NULL;
		}
		return $this;
	}



	public function hasNext() {
		if ($this->hasError() || $this->st == NULL) {
			return FALSE;
		}
		try {
			$this->row = $this->st
				->fetch(
					\PDO::FETCH_ASSOC,
					\PDO::FETCH_ORI_NEXT
				);
			// finished
			if ($this->row === FALSE) {
				return FALSE;
			}
		} catch (\PDOException $e) {
			$sql  = $this->sql;
			$desc = $this->desc;
			$this->setError("Query failed: $sql - $desc", $e);
			return FALSE;
		}
		return TRUE;
	}



	public function getRowCount() {
		if ($this->hasError() || $this->st == NULL || $this->rowCount < 0) {
			return -1;
		}
		return $this->rowCount;
	}
	public function getInsertId() {
		if ($this->hasError() || $this->st == NULL || $this->insertId < 0) {
			return -1;
		}
		return $this->insertId;
	}



	public function desc($desc=NULL) {
		if ($desc != NULL) {
			$this->desc = $desc;
		}
		return $this->desc;
	}



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



	public function setError($msg=NULL, $e=NULL) {
		if (empty($msg)) {
			$msg = '';
		}
		if ($e != NULL) {
			if (!empty($msg)) {
				$msg .= ' - ';
			}
			$msg .= $e->getMessage();
		}
		if (empty($this->hasError)) {
			$this->hasError =
				empty($msg)
				? TRUE
				: $msg;
		} else
		if (!empty($msg)) {
			$this->hasError = $msg;
		}
		// exception mode
		if ($this->errorMode == dbConn::ERROR_MODE_EXCEPTION) {
			throw new \PDOException(
				$msg.' - args: '.\implode(', ', $this->args)
			);
		}
	}
	public function getError() {
		if ($this->hasError === FALSE) {
			return NULL;
		}
		return (
			($this->hasError === TRUE)
			? 'Unknown error'
			: $this->hasError
		).' - args: '.\implode(', ', $this->args);
	}
	public function hasError() {
		return ($this->hasError != FALSE);
	}



	public function setErrorMode($errorMode) {
		if ($errorMode === NULL) {
			$errorMode = dbConn::ERROR_MODE_EXCEPTION;
		}
		$this->errorMode = ($errorMode != FALSE);
	}
	public function getErrorMode() {
		return $this->errorMode;
	}



	public function clean($keepDry=FALSE) {
		$this->st       = NULL;
		$this->rs       = NULL;
		$this->sql      = NULL;
		$this->desc     = NULL;
		if ($keepDry == FALSE) {
			$this->dry  = NULL;
		}
		$this->row      = NULL;
		$this->args     = [];
		$this->rowCount = -1;
		$this->insertId = -1;
		$this->hasError = FALSE;
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



	// --------------------------------------------------
	// get results



	public function getRow() {
		if ($this->hasError() || $this->row == NULL) {
			return FALSE;
		}
		return $this->row;
	}
	public function getString($index) {
		if ($this->hasError() || $this->row == NULL || !isset($this->row[$index])) {
			return FALSE;
		}
		return General::castType($this->row[$index], 'str');
	}
	public function getInt($index) {
		if ($this->hasError() || $this->row == NULL || !isset($this->row[$index])) {
			return FALSE;
		}
		return General::castType($this->row[$index], 'int');
	}
	public function getDouble($index) {
		if ($this->hasError() || $this->row == NULL || !isset($this->row[$index])) {
			return FALSE;
		}
		return General::castType($this->row[$index], 'dbl');
	}
	public function getLong($index) {
		if ($this->hasError() || $this->row == NULL || !isset($this->row[$index])) {
			return FALSE;
		}
		return General::castType($this->row[$index], 'lng');
	}
	public function getBool($index) {
		if ($this->hasError() || $this->row == NULL || !isset($this->row[$index])) {
			return FALSE;
		}
		return General::castType($this->row[$index], 'bool');
	}
	public function getDate($index, $format=NULL) {
		if ($this->hasError() || $this->row == NULL || !isset($this->row[$index])) {
			return FALSE;
		}
		$value = General::castType($this->row[$index], 'int');
		if ($value === FALSE || $value === NULL) {
			return FALSE;
		}
		if (empty($format)) {
			$format = 'Y-m-d H:i:s';
		}
		return \date($format, $value);
		
		return General::castType($this->row[$index], 'str');
	}



}
*/
