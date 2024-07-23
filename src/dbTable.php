<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

use \pxn\phpUtils\utils\StringUtils;
use \pxn\phpUtils\utils\SanUtils;


abstract class dbTable {

	protected bool $inited = false;

	protected ?dbPool $pool      = null;
	protected ?string $tableName = null;
	protected ?array  $fields    = null;



	public function __construct(dbPool $pool, string $tableName) {
		$this->pool      = self::ValidatePool($pool);
		$this->tableName = self::ValidateTableName($tableName);
	}
	public function doInitFields(): bool {
		if ($this->inited)
			return false;
		$this->initFields();
		// set key names
		$fields = [];
		foreach ($this->fields as $field) {
			$name = $field->getName();
			// validate and lock field
			$field->ValidateKeys();
			$field->lock();
			$fields[$name] = $field;
		}
		$this->fields = $fields;
		return true;
	}
	public function initFields(): void {
		throw new \Exception('Must override initFields() function in a class extending dbTable!');
	}



	public function getFields(): array {
		$this->doInitFields();
		return $this->fields;
	}
	public function hasField(string $fieldName): bool {
		$fieldName = self::ValidateFieldName($fieldName);
		$this->doInitFields();
		return \array_key_exists($fieldName, $this->fields);
	}
	public function getField(string $fieldName): ?dbField {
		$fieldName = self::ValidateFieldName($fieldName);
		$this->doInitFields();
		if (!\array_key_exists($fieldName, $this->fields))
			return null;
		return $this->fields[$fieldName];
	}



	public function getName(): string {
		return $this->tableName;
	}



	public static function ValidatePool(dbPool $pool): dbPool {
		if ($pool == null) throw new \Exception('Invalid or unknown pool!');
		$pool = dbPool::getPool($pool);
		if ($pool == null) throw new \Exception('Invalid or unknown pool!');
		return $pool;
	}
	public static function ValidateTableName(string $tableName): string {
		if (empty($tableName)) throw new \Exception('Invalid or unknown table name!');
		$tableName = SanUtils::AlphaNumUnderscore($tableName);
		if (empty($tableName)) throw new \Exception('Invalid or unknown table name!');
		if (StringUtils::StartsWith($tableName, '_'))
			throw new \Exception('Table name cannot start with _ underscore: '.$tableName);
		return $tableName;
	}
	public static function ValidateFieldName(string $fieldName): string {
		if (empty($fieldName)) throw new \Exception('Invalid or unknown field name!');
		$fieldName = SanUtils::AlphaNumUnderscore($fieldName);
		if (empty($fieldName)) throw new \Exception('Invalid or unknown field name!');
		if (StringUtils::StartsWith($fieldName, '_'))
			throw new \Exception('Field name cannot start with _ underscore: '.$fieldName);
		return $fieldName;
	}



	// $schema argument can be path string to class or a class instance object
	// returns the same string or schema object passed to it
	public static function ValidateSchemaClass(string|dbSchema $schema): string|dbSchema|null {
		if (empty($schema))
			return null;
		if (\is_string($schema)) {
			$classPath = (string) $schema;
			$classPath = StringUtils::ForceStartsWith($classPath, '\\');
			if (!\class_exists($classPath))
				throw new \Exception('Schema class not found: '.$classPath);
			return $classPath;
		}
		// invalid class type
		if (! ($schema instanceof \pxn\pxdb\dbSchema) )
			throw new \Exception('Invalid db schema class for table: '.$classPath);
		return $schema;
	}
	// $schema argument can be path string to class or a class instance object
	// returns a dbTableSchema object
	public static function GetSchemaClass(string|dbSchema $schema, ?dbPool $pool=null, ?string $tableName=null): string|dbSchema|null {
		// path to class
		if (\is_string($schema)) {
			$classPath = StringUtils::ForceStartsWith( (string)$schema, '\\' );
			if (!\class_exists($classPath))
				throw new \Exception('Schema class not found: '.$classPath);
			$pool      = self::ValidatePool($pool);
			$tableName = self::ValidateTableName($tableName);
			// new instance of schema class
			$clss = new $classPath($pool, $tableName);
			if (!($clss instanceof \pxn\pxdb\dbTableSchema))
				throw new \Exception('Invalid schema class: '.$classPath);
			return $clss;
		}
		if (!($schema instanceof \pxn\pxdb\dbTableSchema))
			throw new \Exception('Invalid schema class: '.\get_class($schema));
		return $schema;
	}



}
