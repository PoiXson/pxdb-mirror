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
use pxn\phpUtils\Defines;


abstract class dbTables {
	private function __construct() {}



	public static function ValidatePool($pool) {
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
	public static function ValidateTableName($tableName) {
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
	public static function ValidateFieldName($fieldName) {
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



}
