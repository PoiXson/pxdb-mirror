<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

use pxn\phpUtils\utils\StringUtils;
use pxn\phpUtils\utils\SanUtils;


class dbField {

	public ?string $name      = null;
	public ?string $type      = null;
	public ?int    $size      = null;
	public ?bool   $nullable  = null;
	public ?string $defValue  = null;
	public ?bool   $increment = null;
	public ?bool   $primary   = null;
//TODO:
//	public ?bool   $index     = null;
//	public ?bool   $fulltext  = null;
//TODO: support multiple field unique
	public ?bool   $unique    = null;

	public bool $locked = false;



	public function __construct(string $name, string $type, ?int $size=null) {
		$name = SanUtils::AlphaNumUnderscore($name);
		if (empty($name))                        throw new \Exception('Invalid or missing db field name!');
		if (StringUtils::StartsWith($name, '_')) throw new \Exception('Field name cannot start with _ underscore: '.$name);
		$this->name = $name;
		$this->setType($type);
		$this->setSize($size);
	}
	public function clone(): self {
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



	public function lock(): void {
		$this->locked = true;
	}
	public function isLocked(): bool {
		return ($this->locked != false);
	}
	public function ValidUnlocked(): bool {
		if ($this->isLocked()) throw new \Exception('dbField object is locked: '.$this->name);
		return true;
	}



	// (size|nullable|default)
	public function getDesc(): string {
		$msg = $this->getType().
			'('.$this->getSize();
		$nullable = $this->getNullable();
		$defValue = $this->getDefault();
		if ($nullable === true) {
			$msg .= '|NUL|'.($defValue === null ? 'null' : "'{$defValue}'");
		} else {
			// no default value and not nullable
			if ($defValue === null) $msg .= '|NOTNUL|NONE';
			else                    $msg .= "|NOTNUL|'{$defValue}'";
		}
		$msg .= ')';
		return $msg;
	}



	public function getSQL(): string {
		$sql = [];
		$fieldName = $this->getName();
		$fieldType = \mb_strtolower(SanUtils::AlphaNumUnderscore($this->getType()));
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
				$fieldSize = SanUtils::AlphaNumUnderscore($fieldSize);
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
		$nullable = ($this->getNullable() !== false);
		$sql[] = ($nullable ? 'null' : 'NOT null');
		// default
		$defValue = $this->getDefault();
		if ($defValue === null) {
			if ($nullable)
				$sql[] = 'DEFAULT null';
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
		if ($this->isAutoIncrement())
			$sql[] = 'AUTO_INCREMENT';
		// done
		return \implode(' ', $sql);
	}



	// field name
	public function getName(): string {
		return $this->name;
	}



	// field type
	public function setType($type): self {
		$this->ValidUnlocked();
		$type = SanUtils::AlphaNumUnderscore(\mb_strtolower($type));
		if (empty($type)) throw new \Exception('Invalid or missing db field type!');
		switch ($type) {
		case 'increment':
			$this->type      = 'int';
			$this->increment = true;
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
			throw new \Exception("Unsupported field type: [{$type}] $name");
		}
		return $this;
	}
	public function getType(): string {
		return $this->type;
	}



	// field size
	public function getSize(): int {
		return $this->size;
	}
	public function setSize($size): self {
		$this->ValidUnlocked();
		$this->size = $size;
		return $this;
	}



	// nullable field
	public function getNullable(): bool {
		return $this->nullable;
	}
	public function setNullable($nullable=true): self {
		$this->ValidUnlocked();
		if ($nullable === null) $this->nullable = null;
		else                    $this->nullable = ($nullable === true);
		return $this;
	}



	// default value
	public function getDefault(): string {
		return $this->defValue;
	}
	public function setDefault($defValue): self {
		$this->ValidUnlocked();
		$this->defValue = $defValue;
		return $this;
	}



	// auto-increment
	public function isAutoIncrement(): bool {
		return ($this->increment === true);
	}
	public function setAutoIncrement($increment=true): self {
		$this->ValidUnlocked();
		if ($increment === null) $this->increment = null;
		else                     $this->increment = ($increment === true);
		return $this;
	}



	// primary key
	public function isPrimaryKey(): bool {
		return ($this->primary === true);
	}
	public function setPrimaryKey($primary=true): self {
		$this->ValidUnlocked();
		if ($primary === null) $this->primary = null;
		else                   $this->primary = ($primary === true);
		return $this;
	}



	// unique
	public function isUnique(): bool {
		return ($this->unique === true);
	}
	public function setUnique($unique=true): self {
		$this->ValidUnlocked();
		if ($unique === null) $this->unique = null;
		else                  $this->unique = ($unique === true);
		return $this;
	}



	public function ValidateKeys(): void {
		$this->ValidUnlocked();
		// field name
		if (!SanUtils::isAlphaNumUnderscore($this->name))
			throw new \Exception('Invalid field name: '.$this->name);
		$this->name = SanUtils::AlphaNumUnderscore( (string)$this->name );
		if (empty($this->name))
			throw new \Exception('Invalid or missing field name!');
		if (StringUtils::StartsWith($this->name, '_'))
			throw new \Exception('Field name cannot start with _ underscore: '.$this->name);
		// field type
		if (empty($this->type))
			throw new \Exception('Missing field type for field: '.$this->name);
		$this->type = SanUtils::AlphaNumUnderscore(\mb_strtolower( (string) $this->type ));
		if (empty($this->type))
			throw new \Exception('Invalid field type for field: '.$this->name);
		// size
		if ($this->size !== null) {
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
				throw new \Exception('Unable to guess size for field: ['.$this->type.'] '.$this->name);
			}
		}
	}
	public function FillKeysExisting(): void {
		$this->ValidUnlocked();
		if ($this->nullable === null)
			$this->nullable = false;
	}
	public function FillKeysSchema(): void {
		$this->ValidUnlocked();

		// auto-increment
		if ($this->type == 'increment')
			$this->setAutoIncrement(true);
		if ($this->isAutoIncrement()) {
			$this->setPrimaryKey(true);
			$this->setType('int');
			$this->setSize(11);
			$this->setNullable(false);
			$this->setDefault(null);
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
				throw new \Exception('Unable to guess size for field: ['.$this->getType().'] '.$this->getName());
			}
		}

		// null not allowed
		if ($this->nullable === null) {
			$this->nullable = false;
			// guess based on type
			switch ($this->type) {
			case 'decimal': case 'float': case 'double':
				if ($this->defValue === null)
					$this->defValue = 0.0;
				break;
			case 'int':       case 'tinyint':
			case 'smallint':  case 'mediumint': case 'bigint':
			case 'bit':       case 'boolean':   case 'bool':
				if ($this->defValue === null)
					$this->defValue = 0;
				break;
			// default value
				case 'date':
					if ($this->defValue === null || \mb_strlen($this->defValue) != 10)
						$this->defValue = '0000-00-00';
					break;
				case 'time':
					if ($this->defValue === null || \mb_strlen($this->defValue) != 8)
						$this->defValue = '00:00:00';
					break;
				case 'datetime':
					if ($this->defValue === null || \mb_strlen($this->defValue) != 19)
						$this->defValue = '0000-00-00 00:00:00';
					break;
			case 'varchar': case 'char': case 'blob':
			case 'text': case 'longtext':
				if ($this->defValue === null)
					$this->defValue = '';
				break;
			case 'enum':    case 'set':
				if ($this->defValue === null)
					$this->nullable = true;
				break;
			default:
				throw new \Exception('Unsupported field type: ['.$this->getType().'] '.$this->getName());
			}
		}

	}



}
