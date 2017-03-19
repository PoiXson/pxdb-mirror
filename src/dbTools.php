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
		$firstField = $firstField->duplicate();
		$firstField->ValidateKeys();
		$firstField->FillKeysSchema();
		// generate sql
		$dbEngine = 'InnoDB';
		$fieldSQL = $firstField->getSQL();
		$sql = "CREATE TABLE `__TABLE__{$tableName}` ( $fieldSQL ) ENGINE={$dbEngine} DEFAULT CHARSET=latin1";
		echo "{$dryStr}Creating table: {$poolName}:{$tableName} ..\n";
		echo "{$dryStr}Note: (size|nullable|default)\n";
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
		foreach ($fields as $fieldName => $entry) {
			// skip first field
			if ($fieldName === $firstFieldName) {
				continue;
			}
			$field = $entry->duplicate();
			$field->ValidateKeys();
			$field->FillKeysSchema();
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



	public static function AddTableField($pool, $table, dbField $field, $dry=FALSE, $afterFieldName=NULL) {
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
		if (!empty($afterFieldName)) {
			$afterFieldName = San::AlphaNumUnderscore(
				(string) $afterFieldName
			);
			$sql .= " AFTER `{$afterFieldName}`";
		}
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



	public static function CheckFieldNeedsChanges(dbField $existingField, dbField $schemaField) {
		// prepare copies of field objects
		$exist = $existingField->duplicate();
		$schem = $schemaField->duplicate();
		$exist->ValidateKeys();
		$schem->ValidateKeys();
		$exist->FillKeysExisting();
		$schem->FillKeysSchema();
		// check for needed changes
		$changes = [];

		// auto-increment
		if ($exist->isAutoIncrement() !== $schem->isAutoIncrement()) {
			$changes[] = 'increment';
		}
		// primary key
		if ($exist->isPrimaryKey() !== $schem->isPrimaryKey()) {
			$changes[] = 'primary';
		}

		// check field type
		if ($exist->getType() !== $schem->getType()) {
			$existDesc = $exist->getDesc();
			$schemDesc = $schem->getDesc();
			$changes[] = "type: {$existDesc} -> {$schemDesc}";
			return $changes;
		}

		// check properties based on field type
		switch ($schem->getType()) {
		// length size
		case 'int':       case 'tinyint': case 'smallint':
		case 'mediumint': case 'bigint':
		case 'decimal':   case 'double':  case 'float':
		case 'bit':       case 'char':
		case 'boolean':   case 'bool':
		case 'varchar':
		// string values
		case 'enum': case 'set':
			$existSize = $exist->getSize();
			$schemSize = $schem->getSize();
			if ($existSize != $schemSize) {
				$msg = 'size('.$exist->getSize();
				if ($size === NULL) {
					$msg .= 'NULL';
				} else
				if (Numbers::isNumber($size)) {
					$msg .= (int) $size;
				} else {
					$msg .= "'{$size}'";
				}
				$msg .= '->';
				$size = $schem->getSize();
				if ($size === NULL) {
					$msg .= 'NULL';
				} else
				if (Numbers::isNumber($size)) {
					$msg .= (int) $size;
				} else {
					$msg .= "'{$size}'";
				}
				$msg .= ')';
				$changes[] = $msg;
				unset($msg);
			}
			break;
		// no size
		case 'text': case 'longtext': case 'blob':
		case 'date': case 'time':     case 'datetime':
//			$existDefault = $existField['default'];
//			$schemDefault = $schemField['default'];
//			if ($existDefault != $schemDefault) {
//				$changes[] = "default({$existDefault}>{$schemDefault})";
//			}
//			if ($existField['nullable'] !== $schemField['nullable']) {
//				$n1 = ($existField['nullable'] ? 'YES' : 'NOT');
//				$n2 = ($schemField['nullable'] ? 'YES' : 'NOT');
//				$changes[] = "nullable({$n1}>{$n2})";
//			}
			break;
		default:
			$fieldName = $schem->getName();
			$fieldType = $schem->getType();
			fail("Unsupported field type: [{$fieldType}] $fieldName",
				Defines::EXIT_CODE_USAGE_ERROR);
		}

		// check nullable
		$existNullable = $exist->getNullable();
		$schemNullable = $schem->getNullable();
		if ($schemNullable !== NULL) {
			if ($existNullable === NULL) {
				if ($schemNullable === TRUE) {
					$changes[] = 'nullable(NOT -> NUL)';
				}
			} else
			if ($existNullable !== $schemNullable) {
				$msg = 'nullable(';
				$msg .= ($existNullable === TRUE ? 'NULL' : 'NOT').' -> ';
				$msg .= ($schemNullable === TRUE ? 'NULL' : 'NOT').')';
				$changes[] = $msg;
				unset($msg);
			}
		}

		// check default value
		$existDefault = (string) $exist->getDefault();
		$schemDefault = (string) $schem->getDefault();
		if ($existDefault !== $schemDefault) {
			$msg = 'default(';
			$msg .= ($existDefault === NULL ? 'NULL' : "'{$existDefault}'").' -> ';
			$msg .= ($schemDefault === NULL ? 'NULL' : "'{$schemDefault}'").')';
			$changes[] = $msg;
			unset($msg);
		}

		if (\count($changes) == 0) {
			return FALSE;
		}
		return $changes;
	}



}
