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


class dbField {

	public $name      = NULL;
	public $type      = NULL;
	public $size      = NULL;
	public $nullable  = NULL;
	public $defValue  = NULL;
	public $increment = NULL;
	public $primary   = NULL;
	public $unique    = NULL;



	public function __construct($name, $type, $size=NULL) {
		$name = San::AlphaNumUnderscore($name);
		if (empty($name)) {
			fail('Invalid or missing db field name!',
				Defines::EXIT_CODE_USAGE_ERROR);
		}
		if (Strings::StartsWith($name, '_')) {
			fail("Field name cannot start with _ underscore: $name"
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$this->name = $name;
		$this->setType($type);
		$this->setSize($size);
	}
	public function clone() {
		$obj = new self(
			$this->name,
			$this->type,
			$this->size
		);
		$obj->nullable  = $this->nullable;
		$obj->defValue  = $this->defValue;
		$obj->increment = $this->increment;
		$obj->primary   = $this->primary;
		$obj->unique    = $this->unique;
		return $obj;
	}



	public function getDesc() {
		$msg = [];
		$msg[] = $this->getType();
		$msg[] = '(';
		$msg[] = $this->getSize();
		$nullable = $this->getNullable();
		$defValue = $this->getDefault();
		if ($nullable === TRUE) {
			$msg[] = '-NUL=';
			$msg[] = ($defValue === NULL ? 'NULL' : "'{$defValue}'");
		} else {
			$msg[] = '-NOT';
			// no default value and not nullable
			if ($defValue === NULL) {
				$msg[] = '=NONE';
			} else {
				$msg[] = "='{$defValue}'";
			}
		}
		$msg[] = ')';
		return \implode($msg, '');
	}



	// field name
	public function getName() {
		return $this->name;
	}



	// field type
	public function setType($type) {
		$type = San::AlphaNumUnderscore(\mb_strtolower($type));
		if (empty($type)) {
			fail('Invalid or missing db field type!',
				Defines::EXIT_CODE_USAGE_ERROR);
		}
		switch ($type) {
		case 'increment':
			$this->type = 'int';
			$this->increment = TRUE;
			break;
		case 'int':       case 'tinyint':  case 'smallint':
		case 'mediumint': case 'bigint':   case 'char':
		case 'decimal':   case 'double':   case 'float':
		case 'boolean':   case 'bool':     case 'bit':
		case 'varchar':   case 'enum':     case 'set':
		case 'text':      case 'longtext': case 'blob':
		case 'date':      case 'time':     case 'datetime':
			$this->type = $type;
			break;
		default:
			fail("Unsupported field type: [{$type}] $name",
				Defines::EXIT_CODE_USAGE_ERROR);
		}
		return $this;
	}
	public function getType() {
		return $this->type;
	}



	// field size
	public function getSize() {
		return $this->size;
	}
	public function setSize($size) {
		$this->size = $size;
		return $this;
	}



	// nullable field
	public function getNullable() {
		return $this->nullable;
	}
	public function setNullable($nullable=TRUE) {
		if ($nullable === NULL) {
			$this->nullable = NULL;
		} else {
			$this->nullable = ($nullable === TRUE);
		}
		return $this;
	}



	// default value
	public function getDefault() {
		return $this->defValue;
	}
	public function setDefault($defValue) {
		$this->defValue = $defValue;
		return $this;
	}



	// auto-increment
	public function isAutoIncrement() {
		return ($this->increment === TRUE);
	}
	public function setAutoIncrement($increment=TRUE) {
		if ($increment === NULL) {
			$this->increment = NULL;
		} else {
			$this->increment = ($increment === TRUE);
		}
		return $this;
	}



	// primary key
	public function isPrimaryKey() {
		return ($this->primary === TRUE);
	}
	public function setPrimaryKey($primary=TRUE) {
		if ($primary === NULL) {
			$this->primary = NULL;
		} else {
			$this->primary = ($primary === TRUE);
		}
		return $this;
	}



	// unique
	public function isUnique() {
		return ($this->unique === TRUE);
	}
	public function setUnique($unique=TRUE) {
		if ($unique === NULL) {
			$this->unique = NULL;
		} else {
			$this->unique = ($unique === TRUE);
		}
		return $this;
	}



}

