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
use pxn\phpUtils\Defines;


final class dbTablesUsing extends dbTables {
	private function __construct() {}

	protected $usingTables = [];



	// format: [ 'table_name' => 'path\\to\\schema\\class', .. ]
	public function addUsingTables(array $tables) {
		$this->usingTables = \array_merge(
			$this->usingTables,
			$tables
		);
	}
	public function getUsingTables() {
		$result = [];
		foreach ($this->usingTables as $tableName => $schemaClass) {
			$name = San::AlphaNumUnderscore($tableName);
			if (empty($name)) {
				fail('Invalid or missing table name!',
					Defines::EXIT_CODE_INVALID_FORMAT);
			}
			if (Strings::StartsWith($tableName, '_')) {
				fail("Invalid table name, cannot start with _ underscore: $tableName",
					Defines::EXIT_CODE_INVALID_FORMAT);
			}
			$schema = new $schemaClass();
			if (! $schema instanceof \pxn\pxdb\dbSchema) {
				fail("Invalid db schema class for table: $schemaClass",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
			$result[$name] = $schema;
		}
		return $result;
	}



}
