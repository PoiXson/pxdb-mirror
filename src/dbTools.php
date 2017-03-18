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


final class dbTools {
	private function __construct() {}



	public static function CreateTable($pool, $table, $dry=FALSE) {
		$dryStr = ($dry === FALSE ? '' : '[DRY] ');
		// validate pool
		$pool = dbPool::getPool($pool);
		if ($pool == NULL) {
			fail('Invalid or missing pool!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$poolName = $pool->getName();
		// validate table
		$table = $pool->getSchemaTable($table);
		if ($table == NULL) {
			fail('Table is invalid or unknown!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$tableName = San::AlphaNumUnderscore(
			$table->getName()
		);
		if (empty($tableName)) {
			fail('Invalid table name!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		if (Strings::StartsWith($tableName, '_')) {
			fail("Cannot create tables starting with _ underscore: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		if ($pool->hasExistingTable($tableName)) {
			fail("Cannot create table, already exists: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		// get first field
		$fields = $table->getFields();
		$firstField = \reset($fields);
		// generate sql
		$dbEngine = 'InnoDB';
		$fieldSQL = $firstField->getSQL();
		$sql = "CREATE TABLE `__TABLE__{$tableName}` ( $fieldSQL ) ENGINE={$dbEngine} DEFAULT CHARSET=latin1";
		echo "{$dryStr}Creating table: {$poolName}:{$tableName} ..\n";
		// create new table
		$db = $pool->getDB();
		$db->setDry($dry);
		$result = $db->Execute(
			$sql,
			'CreateTable()'
		);
		if ($result->hasError()) {
			fail("Failed to create table: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		unset($sql);
		// set auto-increment
		if ($firstField->isAutoIncrement()) {
			$firstFieldName = San::AlphaNumUnderscore(
				$firstField->getName()
			);
			if (empty($firstFieldName)) {
				fail('Invalid or missing table name!',
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
			$sql = "ALTER TABLE `__TABLE__{$tableName}` ADD PRIMARY KEY ( `{$firstFieldName}` )";
			$result = $db->Execute(
				$sql,
				'InitAutoIncrementField(primary-key)'
			);
			if (!$result) {
				fail("Failed to set primary key on field: $firstFieldName",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
			$sql = "ALTER TABLE `__TABLE__{$tableName}` MODIFY `{$firstFieldName}` int(11) NOT NULL AUTO_INCREMENT";
			$result = $db->Execute(
				$sql,
				'InitAutoIncrementField(auto-increment)'
			);
			if (!$result) {
				fail("Failed to set auto-increment on field: $firstFieldName",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
			unset($sql);
		}
		$db->release();
		// add more fields
		foreach ($fields as $fieldName => $field) {
			// skip first field
			if ($fieldName === $firstFieldName) {
				continue;
			}
			// add field to table
			self::AddTableField(
				$pool,
				$table,
				$field,
				$dry
			);
		}
		// force cache reload
		$pool->ReloadExistingTableCache();
		return TRUE;
	}



	public static function AddTableField($pool, $table, dbField $field, $dry=FALSE) {
		$dryStr = ($dry === FALSE ? '' : '[DRY] ');
		// validate pool
		$pool = dbPool::getPool($pool);
		if ($pool == NULL) {
			fail('Invalid or missing pool!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$poolName = $pool->getName();
		// validate table
		$table = $pool->getSchemaTable($table);
		if ($table == NULL) {
			fail('Table is invalid or unknown!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$tableName = San::AlphaNumUnderscore(
			$table->getName()
		);
		if (empty($tableName)) {
			fail('Invalid table name!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		if (Strings::StartsWith($tableName, '_')) {
			fail("Cannot create tables starting with _ underscore: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		// generate sql
		$fieldSQL = $field->getSQL();
		$sql = "ALTER TABLE `{$tableName}` ADD $fieldSQL";
		$desc = $field->getDesc();
		echo "{$dryStr} Adding field: {$desc}\n";
		// alter table
		$db = $pool->getDB();
		$db->setDry($dry);
		$result = $db->Execute(
			$sql,
			'AddTableField()'
		);
		if ($result->hasError()) {
			fail("Failed to add table field: {$tableName}:: $desc",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		unset($sql);
		$db->release();
		return TRUE;
	}
	public static function UpdateTableField($pool, $table, dbField $field, $dry=FALSE) {
		$dryStr = ($dry === FALSE ? '' : '[DRY] ');
		// validate pool
		$pool = dbPool::getPool($pool);
		if ($pool == NULL) {
			fail('Invalid or missing pool!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$poolName = $pool->getName();
		// validate table
		$table = $pool->getSchemaTable($table);
		if ($table == NULL) {
			fail('Table is invalid or unknown!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$tableName = San::AlphaNumUnderscore(
			$table->getName()
		);
		if (empty($tableName)) {
			fail('Invalid table name!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		if (Strings::StartsWith($tableName, '_')) {
			fail("Cannot create tables starting with _ underscore: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		// generate sql
		$fieldName = $field->getName();
		$fieldSQL = $field->getSQL();
		$sql = "ALTER TABLE `__TABLE__{$tableName}` CHANGE `{$fieldName}` $fieldSQL";
		$desc = $field->getDesc();
		echo "{$dryStr} Changing field: {$desc}\n";
		// alter table
		$db = $pool->getDB();
		$db->setDry($dry);
		$result = $db->Execute(
			$sql,
			'UpdateTableField()'
		);
		if ($result->hasError()) {
			fail("Failed to change table field: {$tableName}:: $desc",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		unset($sql);
		$db->release();
		return TRUE;
	}



}
