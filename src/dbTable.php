<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;


abstract class dbTable {

	protected string $name;
	protected array $fields = [];



	public function __construct(string $name) {
		$this->name = dbTools::ValidateTableName($name);
		$this->initFields();
	}



	protected abstract function initFields(): void;

	protected function addField(dbField|dbFieldFactory $field): self {
		if ($field instanceof dbFieldFactory)
			return $this->addField($field->build());
		$name = $field->getFieldName();
		$this->fields[$name] = $field;
		return $this;
	}
	public function getFields(): array {
		return $this->fields;
	}
	public function getFirstField(): dbField {
		return \reset($this->fields);
	}



	public function getTableName(): string {
		return $this->name;
	}



}
