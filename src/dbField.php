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
use pxn\phpUtils\Numbers;
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
			fail("Field name cannot start with _ underscore: $name",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$this->name = $name;
		$this->setType($type);
		$this->setSize($size);
	}
	public function duplicate() {
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
		$msg = $this->getType().
			'('.$this->getSize();
		$nullable = $this->getNullable();
		$defValue = $this->getDefault();
		if ($nullable === TRUE) {
			$msg .= '-NUL='.($defValue === NULL ? 'NULL' : "'{$defValue}'");
		} else {
			// no default value and not nullable
			if ($defValue === NULL) {
				$msg .= '-NOT=NONE';
			} else {
				$msg .= "-NOT='{$defValue}'";
			}
		}
		$msg .= ')';
		return $msg;
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



	public function ValidateKeys() {
		// field name
		if (!San::isAlphaNumUnderscore($this->name)) {
			fail("Invalid field name: {$this->name}",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$this->name = San::AlphaNumUnderscore( (string)$this->name );
		if (empty($this->name)) {
			fail('Invalid or missing field name!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		if (Strings::StartsWith($this->name, '_')) {
			$fieldName = $this->name;
			fail("Field name cannot start with _ underscore: $fieldName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		// field type
		if (empty($this->type)) {
			$fieldName = $this->name;
			fail("Missing field type for field: $fieldName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$this->type =
			San::AlphaNumUnderscore(
				\mb_strtolower(
					(string) $this->type
				)
			);
		if (empty($this->type)) {
			$fieldName = $this->name;
			fail("Invalid field type for field: $fieldName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		// size
		if ($this->size !== NULL) {
			switch ($this->type) {
			case 'increment':
			case 'int':       case 'tinyint': case 'smallint':
			case 'mediumint': case 'bigint':
			case 'bit':       case 'char':
			case 'boolean':   case 'bool':
			case 'varchar':
				$this->size = (int) $this->size;
			case 'decimal': case 'double':   case 'float':
			case 'enum':    case 'set':
			case 'text':    case 'longtext': case 'blob':
			case 'date':    case 'time':     case 'datetime':
				$this->size = (string) $this->size;
				break;
			default:
				$fieldName = $this->name;
				$fieldType = $this->type;
				fail("Unable to guess size for field: [{$fieldType}] $fieldName",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
		}
	}
	public function FillKeysExisting() {
		if ($this->nullable === NULL) {
			$this->nullable = FALSE;
		}
	}
	public function FillKeysSchema() {

		// auto-increment
		if ($this->type == 'increment') {
			$this->setAutoIncrement(TRUE);
		}
		if ($this->isAutoIncrement()) {
			$this->setPrimaryKey(TRUE);
			$this->setType('int');
			$this->setSize(11);
			$this->setNullable(FALSE);
			$this->setDefault(NULL);
			return;
		}

		// size
		if (empty($this->size)) {
			// guess default size
			switch ($this->type) {
			case 'int':
				$this->size = 11;
				break;
			case 'tinyint':
				$this->size = 4;
				break;
			case 'smallint':
				$this->size = 6;
				break;
			case 'mediumint':
				$this->size = 8;
				break;
			case 'bigint':
				$this->size = 20;
				break;
			case 'decimal': case 'double':
				$this->size = '16,4';
				break;
			case 'float':
				$this->size = '10,2';
				break;
			case 'bit':     case 'char':
			case 'boolean': case 'bool':
				$this->size = 1;
				break;
			case 'varchar':
				$this->size = 255;
			case 'enum': case 'set':
				$this->size = "''";
				break;
			case 'text': case 'longtext': case 'blob':
			case 'date': case 'time':     case 'datetime':
				break;
			default:
				$fieldName = $this->getName();
				$fieldType = $this->getType();
				fail("Unable to guess size for field: [{$fieldType}] $fieldName",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
		}

		// null not allowed
		if ($this->nullable !== TRUE) {
			$this->nullable = FALSE;
			// guess based on type
			switch ($this->type) {
			case 'decimal': case 'float': case 'double':
				if ($this->defValue === NULL) {
					$this->defValue = 0.0;
				}
				break;
			case 'int':       case 'tinyint':
			case 'smallint':  case 'mediumint': case 'bigint':
			case 'bit':       case 'boolean':   case 'bool':
				if ($this->defValue === NULL) {
					$this->defValue = 0;
				}
				break;
			case 'date': case 'time': case 'datetime':
				// default value
				switch ($this->type) {
				case 'date':
					if ($this->defValue === NULL || \mb_strlen($this->defValue) != 10) {
						$this->defValue = '0000-00-00';
					}
					break;
				case 'time':
					if ($this->defValue === NULL || \mb_strlen($this->defValue) != 8) {
						$this->defValue = '00:00:00';
					}
					break;
				case 'datetime':
					if ($this->defValue === NULL || \mb_strlen($this->defValue) != 19) {
						$this->defValue = '0000-00-00 00:00:00';
					}
					break;
				default:
					fail('Unexpected error!', Defines::EXIT_CODE_INTERNAL_ERROR);
				}
				break;
			case 'varchar': case 'char': case 'blob':
			case 'text': case 'longtext':
				if ($this->defValue === NULL) {
					$this->defValue = '';
				}
				break;
			case 'enum':    case 'set':
				if ($this->defValue === NULL) {
					$this->nullable = TRUE;
				}
				break;
			default:
				$fieldName = $this->getName();
				$fieldType = $this->getType();
				fail("Unsupported field type: [{$fieldType}] $fieldName",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
		}

	}



	public static function CheckFieldNeedsChanges(dbField $existingField, dbField $schemaField) {
		// prepare copies of field objects
		$exist = $existingField->duplicate();
		$schem = $schemaField->duplicate();
		$exist->ValidateKeys();
		$schem->ValidateKeys();
		$exist->FillKeysExisting();
		$schem->FillKeysSchema();
		// check for needed changes
		$changes = [];

		// auto-increment
		if ($exist->isAutoIncrement() !== $schem->isAutoIncrement()) {
			$changes[] = 'increment';
		}
		// primary key
		if ($exist->isPrimaryKey() !== $schem->isPrimaryKey()) {
			$changes[] = 'primary';
		}

		// check field type
		if ($exist->getType() !== $schem->getType()) {
			$existDesc = $exist->getDesc();
			$schemDesc = $schem->getDesc();
			$changes[] = "type: {$existDesc} -> {$schemDesc}";
			return $changes;
		}

		// check properties based on field type
		switch ($schem->getType()) {
		// length size
		case 'int':       case 'tinyint': case 'smallint':
		case 'mediumint': case 'bigint':
		case 'decimal':   case 'double':  case 'float':
		case 'bit':       case 'char':
		case 'boolean':   case 'bool':
		case 'varchar':
		// string values
		case 'enum': case 'set':
			$existSize = $exist->getSize();
			$schemSize = $schem->getSize();
			if ($existSize != $schemSize) {
				$msg = 'size('.$exist->getSize();
				if ($size === NULL) {
					$msg .= 'NULL';
				} else
				if (Numbers::isNumber($size)) {
					$msg .= (int) $size;
				} else {
					$msg .= "'{$size}'";
				}
				$msg .= '->';
				$size = $schem->getSize();
				if ($size === NULL) {
					$msg .= 'NULL';
				} else
				if (Numbers::isNumber($size)) {
					$msg .= (int) $size;
				} else {
					$msg .= "'{$size}'";
				}
				$msg .= ')';
				$changes[] = $msg;
				unset($msg);
			}
			break;
		// no size
		case 'text': case 'longtext': case 'blob':
		case 'date': case 'time':     case 'datetime':
//			$existDefault = $existField['default'];
//			$schemDefault = $schemField['default'];
//			if ($existDefault != $schemDefault) {
//				$changes[] = "default({$existDefault}>{$schemDefault})";
//			}
//			if ($existField['nullable'] !== $schemField['nullable']) {
//				$n1 = ($existField['nullable'] ? 'YES' : 'NOT');
//				$n2 = ($schemField['nullable'] ? 'YES' : 'NOT');
//				$changes[] = "nullable({$n1}>{$n2})";
//			}
			break;
		default:
			$fieldName = $schem->getName();
			$fieldType = $schem->getType();
			fail("Unsupported field type: [{$fieldType}] $fieldName",
				Defines::EXIT_CODE_USAGE_ERROR);
		}

		// check nullable
		$existNullable = $exist->getNullable();
		$schemNullable = $schem->getNullable();
		if ($schemNullable !== NULL) {
			if ($existNullable === NULL) {
				if ($schemNullable === TRUE) {
					$changes[] = 'nullable(NOT -> NUL)';
				}
			} else
			if ($existNullable !== $schemNullable) {
				$msg = 'nullable(';
				$msg .= ($existNullable === TRUE ? 'NULL' : 'NOT').' -> ';
				$msg .= ($schemNullable === TRUE ? 'NULL' : 'NOT').')';
				$changes[] = $msg;
				unset($msg);
			}
		}

		// check default value
		$existDefault = (string) $exist->getDefault();
		$schemDefault = (string) $schem->getDefault();
		if ($existDefault !== $schemDefault) {
			$msg = 'default(';
			$msg .= ($existDefault === NULL ? 'NULL' : "'{$existDefault}'").' -> ';
			$msg .= ($schemDefault === NULL ? 'NULL' : "'{$schemDefault}'").')';
			$changes[] = $msg;
			unset($msg);
		}

		if (\count($changes) == 0) {
			return FALSE;
		}
		return $changes;
	}



}
