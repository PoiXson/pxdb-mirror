<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 * /
namespace pxn\pxdb;

use pxn\phpUtils\Strings;
use pxn\phpUtils\San;
use pxn\phpUtils\Defines;


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
		if ($this->inited) {
			return FALSE;
		}
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
		return TRUE;
	}
	public function initFields(): void {
		fail('Must override initFields() function in a class extending dbTable!',
			Defines::EXIT_CODE_INTERNAL_ERROR);
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
		if (!\array_key_exists($fieldName, $this->fields)) {
			return NULL;
		}
		return $this->fields[$fieldName];
	}



	public function getName(): string {
		return $this->tableName;
	}



	public static function ValidatePool(dbPool $pool): dbPool {
		if ($pool == NULL) {
			fail('Invalid or unknown pool!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$pool = dbPool::getPool($pool);
		if ($pool == NULL) {
			fail('Invalid or unknown pool!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		return $pool;
	}
	public static function ValidateTableName(string $tableName): string {
		if (empty($tableName)) {
			fail('Invalid or unknown table name!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$tableName = San::AlphaNumUnderscore($tableName);
		if (empty($tableName)) {
			fail('Invalid or unknown table name!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		if (Strings::StartsWith($tableName, '_')) {
			fail("Table name cannot start with _ underscore: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		return $tableName;
	}
	public static function ValidateFieldName(string $fieldName): string {
		if (empty($fieldName)) {
			fail('Invalid or unknown field name!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$fieldName = San::AlphaNumUnderscore($fieldName);
		if (empty($fieldName)) {
			fail('Invalid or unknown field name!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		if (Strings::StartsWith($fieldName, '_')) {
			fail("Field name cannot start with _ underscore: $fieldName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		return $fieldName;
	}



	// $schema argument can be path string to class or a class instance object
	// returns the same string or schema object passed to it
	public static function ValidateSchemaClass(string|dbSchema $schema): string|dbSchema|null {
		if (empty($schema)) {
			return NULL;
		}
		if (\is_string($schema)) {
			$classPath = (string) $schema;
			$classPath = Strings::ForceStartsWith($classPath, '\\');
			if (!\class_exists($classPath)) {
				fail("Schema class not found: $classPath",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
			return $classPath;
		}
		// invalid class type
		if (! ($schema instanceof \pxn\pxdb\dbSchema) ) {
			fail("Invalid db schema class for table: $classPath",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		return $schema;
	}
	// $schema argument can be path string to class or a class instance object
	// returns a dbTableSchema object
	public static function GetSchemaClass(string|dbSchema $schema, ?dbPool $pool=null, ?string $tableName=null): string|dbSchema|null {
		// path to class
		if (\is_string($schema)) {
			$classPath = Strings::ForceStartsWith( (string)$schema, '\\' );
			if (!\class_exists($classPath)) {
				fail("Schema class not found: $classPath",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
			$pool      = self::ValidatePool($pool);
			$tableName = self::ValidateTableName($tableName);
			// new instance of schema class
			$clss = new $classPath($pool, $tableName);
			if (!($clss instanceof \pxn\pxdb\dbTableSchema)) {
				fail("Invalid schema class: $classPath",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
			return $clss;
		}
		if (!($schema instanceof \pxn\pxdb\dbTableSchema)) {
			$classPath = \get_class($schema);
			fail("Invalid schema class: $classPath",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		return $schema;
	}



}
*/
