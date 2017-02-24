<?php
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2017
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\pxdb;

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
		$this->name = $name;
		$type = San::AlphaNumUnderscore(\mb_strtolower($type));
		if (empty($name)) {
			fail('Invalid or missing db field name!',
				Defines::EXIT_CODE_USAGE_ERROR);
		}
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
		$this->size = $size;
	}



	public function setSize($size) {
		$this->size = $size;
		return $this;
	}
	public function setNullable($nullable=TRUE) {
		if ($nullable === NULL) {
			$this->nullable = NULL;
		} else {
			$this->nullable = ($nullable === TRUE);
		}
		return $this;
	}
	public function setDefault($defValue) {
		$this->defValue = $defValue;
		return $this;
	}
	public function setAutoIncrement($increment=TRUE) {
		if ($increment === NULL) {
			$this->increment = NULL;
		} else {
			$increment = ($increment === TRUE);
		}
		return $this;
	}
	public function setPrimaryKey($primary=TRUE) {
		if ($primary === NULL) {
			$this->primary = NULL;
		} else {
			$primary = ($primary === TRUE);
		}
		return $this;
	}
	public function setUnique($unique=TRUE) {
		if ($unique === NULL) {
			$this->unique = NULL;
		} else {
			$unique = ($unique === TRUE);
		}
		return $this;
	}



}

