<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;


class dbFieldFactory {

	protected ?string      $name = null;
	protected ?dbFieldType $type = null;
	protected int|string|null $size   = null;
	protected int|string|null $defval = null;
	protected bool $nullable  = false;
	protected bool $increment = false;
	protected bool $primary   = false;
	protected bool $unique    = false;



	public function __construct() {
	}



	public function build(): dbField {
		return new dbField(
			$this->name,
			$this->type,
			$this->size,
			$this->nullable,
			$this->defval,
			$this->increment,
			$this->primary,
			$this->unique
		);
	}



	public function name(string $name): self {
		$this->name = $name;
		return $this;
	}
	public function type(dbFieldType $type): self {
		$this->type = $type;
		return $this;
	}
	public function size(int|string|null $size=null): self {
		$this->size = $size;
		return $this;
	}
	public function nullable(bool $nullable=true): self {
		$this->nullable = $nullable;
		return $this;
	}
	public function defval(int|string|null $defval=null): self {
		$this->defval = $defval;
		return $this;
	}
	public function increment(bool $increment=true): self {
		$this->increment = $increment;
		return $this;
	}
	public function primary(bool $primary=true): self {
		$this->primary = $primary;
		return $this;
	}
	public function unique(bool $unique=true): self {
		$this->unique = $unique;
		return $this;
	}



}
