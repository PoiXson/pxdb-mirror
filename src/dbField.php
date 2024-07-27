<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

use \pxn\phpUtils\utils\SanUtils;


class dbField {

	protected string      $name;
	protected dbFieldType $type;
	protected int|string|null $size;
	protected int|string|null $defval;
	protected bool $nullable;
	protected bool $increment;
	protected bool $primary;
	protected bool $unique;



	public function __construct(string $name, dbFieldType $type, int|string|null $size=null,
	bool $nullable=false, int|string|null $defval=null, bool $increment=false, bool $primary=false, bool $unique=false) {
		$this->name      = dbTools::ValidateFieldName($name);
		$this->type      = $type;
		$this->size      = $size;
		$this->nullable  = $nullable;
		$this->defval    = $defval;
		$this->increment = $increment;
		$this->primary   = $primary;
		$this->unique    = $unique;
	}



	public function getFieldName(): string {
		return $this->name;
	}



	public function buildFieldSQL(): string {
		$name = dbTools::ValidateFieldName($this->name);
		$type = match ($this->type) {
			dbFieldType::TYPE_BOOL => 'BOOL',
			dbFieldType::TYPE_INT  => "INT({$this->size})",
			dbFieldType::TYPE_STR  => "VARCHAR({$this->size})",
			dbFieldType::TYPE_TEXT => 'TEXT',
			default => null
		};
		if (empty($type)) throw new \RuntimeException('Unknown field type: '.$this->type);
		$sql = "`$name` $type";
		if (!$this->nullable) $sql .= ' NOT NULL';
		if ($this->increment) $sql .= ' AUTOINCREMENT';
		if ($this->primary)   $sql .= ' PRIMARY KEY';
		if ($this->unique)    $sql .= ' UNIQUE';
		if ($this->defval === null) {
			if ($this->nullable)
				$sql .= ' DEFAULT NULL';
		} else {
			switch ($this->type) {
				case dbFieldType::TYPE_BOOL: $sql .= ' DEFAULT '.($this->defval ? 'TRUE' : 'FALSE'); break;
				case dbFieldType::TYPE_INT:  $sql .= ' DEFAULT '.((int)$this->defval);               break;
				case dbFieldType::TYPE_STR:
				case dbFieldType::TYPE_TEXT: $sql .= " DEFAULT '{$this->defval}'";                   break;
				default: throw new \RuntimeException('Unknown field type: '.$this->type);
			}
		}
		return $sql;
	}



}
