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

	public $locked = FALSE;



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



	public function lock() {
		$this->locked = TRUE;
	}
	public function isLocked() {
		return ($this->locked != FALSE);
	}
	public function ValidUnlocked() {
		if ($this->isLocked()) {
			fail("dbField object is locked: {$this->name}",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		return TRUE;
	}



	// (size|nullable|default)
	public function getDesc() {
		$msg = $this->getType().
			'('.$this->getSize();
		$nullable = $this->getNullable();
		$defValue = $this->getDefault();
		if ($nullable === TRUE) {
			$msg .= '|NUL|'.($defValue === NULL ? 'NULL' : "'{$defValue}'");
		} else {
			// no default value and not nullable
			if ($defValue === NULL) {
				$msg .= '|NOTNUL|NONE';
			} else {
				$msg .= "|NOTNUL|'{$defValue}'";
			}
		}
		$msg .= ')';
		return $msg;
	}



	public function getSQL() {
		$sql = [];
		$fieldName = $this->getName();
		$fieldType = \mb_strtolower(
			San::AlphaNumUnderscore(
				$this->getType()
			)
		);
		$fieldSize = $this->getSize();
		// name
		$sql[] = "`{$fieldName}`";
		// type/size
		if ($fieldType == 'increment') {
			$sql[] = 'INT(11)';
		} else
		if (empty($fieldSize)) {
			$sql[] = \mb_strtoupper($fieldType);
		} else {
			switch ($fieldType) {
			case 'varchar': case 'char':
			case 'text':    case 'longtext':
			case 'enum':    case 'set':
				$fieldSize = San::AlphaNumUnderscore($fieldSize);
			default:
				break;
			}
			$fieldTypeUpper = \mb_strtoupper($fieldType);
			$sql[] = "{$fieldTypeUpper}({$fieldSize})";
		}
		// charset
		switch ($fieldType) {
		case 'varchar': case 'char':
		case 'text':    case 'longtext':
		case 'enum':    case 'set':
			$sql[] = "CHARACTER SET latin1 COLLATE latin1_swedish_ci";
		default:
			break;
		}
		// null / not null
		$nullable = ($this->getNullable() !== FALSE);
		$sql[] = (
			$nullable
			? 'NULL'
			: 'NOT NULL'
		);
		// default
		$defValue = $this->getDefault();
		if ($defValue === NULL) {
			if ($nullable) {
				$sql[] = 'DEFAULT NULL';
			}
		} else {
			switch ($fieldType) {
			case 'int': case 'tinyint': case 'smallint':
			case 'mediumint': case 'bigint':
				$defValue = (int) $defValue;
				$sql[] = "DEFAULT '{$defValue}'";
				break;
			case 'decimal': case 'double':
				$defValue = (double) $defValue;
				$sql[] = "DEFAULT '{$defValue}'";
				break;
			case 'float':
				$defValue = (float) $defValue;
				$sql[] = "DEFAULT '{$defValue}'";
				break;
			case 'bit': case 'boolean':
				$defValue = ($defValue == 0 ? 0 : 1);
				$sql[] = "DEFAULT '{$defValue}'";
				break;
			default:
				$sql[] = "DEFAULT '{$defValue}'";
				break;
			}
		}
		// auto-increment
		if ($this->isAutoIncrement()) {
			$sql[] = 'AUTO_INCREMENT';
		}
		// done
		return \implode(' ', $sql);
	}



	// field name
	public function getName() {
		return $this->name;
	}



	// field type
	public function setType($type) {
		$this->ValidUnlocked();
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
		$this->ValidUnlocked();
		$this->size = $size;
		return $this;
	}



	// nullable field
	public function getNullable() {
		return $this->nullable;
	}
	public function setNullable($nullable=TRUE) {
		$this->ValidUnlocked();
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
		$this->ValidUnlocked();
		$this->defValue = $defValue;
		return $this;
	}



	// auto-increment
	public function isAutoIncrement() {
		return ($this->increment === TRUE);
	}
	public function setAutoIncrement($increment=TRUE) {
		$this->ValidUnlocked();
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
		$this->ValidUnlocked();
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
		$this->ValidUnlocked();
		if ($unique === NULL) {
			$this->unique = NULL;
		} else {
			$this->unique = ($unique === TRUE);
		}
		return $this;
	}



	public function ValidateKeys() {
		$this->ValidUnlocked();
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
		$this->ValidUnlocked();
		if ($this->nullable === NULL) {
			$this->nullable = FALSE;
		}
	}
	public function FillKeysSchema() {
		$this->ValidUnlocked();

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
		if ($this->nullable === NULL) {
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
			// default value
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



}
