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
use pxn\phpUtils\Numbers;


final class dbTools {
	private function __construct() {}



	public static function CreateTable($pool, $table, $dry=TRUE) {
		$dry = ($dry !== FALSE);
		$dryStr = ($dry ? '[DRY] ' : '');
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
		$tableName = $table->getName();
		if ($pool->hasExistingTable($tableName)) {
			fail("Cannot create table, already exists: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		echo "{$dryStr}Creating Table: {$poolName}:{$tableName} ..\n";
//		echo "{$dryStr}Note: (size|nullable|default)\n";
		// get first field
		$fields = $table->getFields();
		$firstField = \reset($fields);
		$firstField = $firstField->duplicate();
		$firstField->ValidateKeys();
		$firstField->FillKeysSchema();
		$firstFieldName = $firstField->getName();
		// generate sql
		$dbEngine = 'InnoDB';
		$fieldSQL = $firstField->getSQL();
		// primary key
		if ($firstField->isPrimaryKey() || $firstField->isAutoIncrement()) {
			$fieldSQL .= " , PRIMARY KEY ( `{$firstFieldName}` )";
		}
		// unique
		if ($firstField->isUnique()) {
			$fieldSQL .= " , UNIQUE ( `{$fieldName}` )";
		}
		$sql = "CREATE TABLE `__TABLE__{$tableName}` ( $fieldSQL ) ENGINE={$dbEngine} DEFAULT CHARSET=latin1";
		// create new table
		$db = $pool->getDB();
		$db->setDry($dry);
		$result = $db->Execute(
			$sql,
			"CreateTable({$tableName})"
		);
		if ($result->hasError()) {
			fail("Failed to create table: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		unset($sql);
		$db->release();
		// force cache reload
		$pool->ReloadExistingTableCache();
		// add rest of the fields
		foreach ($fields as $fieldName => $entry) {
			$field = $entry->duplicate();
			$field->ValidateKeys();
			$field->FillKeysSchema();
			// add field to table
			if ($fieldName != $firstFieldName) {
				self::AddChangeTableField(
					$pool,
					$table,
					$field,
					NULL,
					$dry
				);
			}
		}
		// force cache reload
		$pool->ReloadExistingTableCache();
		return TRUE;
	}



	// set $afterFieldName to "__FIRST__" to insert at front of table
	public static function AddChangeTableField($pool, $table, dbField $field, $afterFieldName=NULL, $dry=TRUE) {
		$dry = ($dry !== FALSE);
		$dryStr = ($dry ? '[DRY] ' : '');
		// validate pool
		$pool = dbPool::getPool($pool);
		if ($pool == NULL) {
			fail('Invalid or missing pool!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$poolName = $pool->getName();
		$fieldName = $field->getName();
		// validate table
		$table = $pool->getSchemaTable($table);
		if ($table == NULL) {
			fail('Table is invalid or unknown!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$tableName = $table->getName();
		$existTable = NULL;
		$exists     = NULL;
		if (!$dry) {
			$existTable = $pool->getExistingTable($tableName);
			$exists = $existTable->hasField($fieldName);

		}
		$desc = $field->getDesc();
		if ($exists === TRUE) {
			$existField = $existTable->getField($fieldName);
			$existDesc = $existField->getDesc();
			echo "{$dryStr}* Changing field:  {$fieldName}\n";
			echo "{$dryStr}    from: {$existDesc}\n";
			echo "{$dryStr}      to: {$desc}\n";
			unset($existField, $existDesc);
		} else {
			echo "{$dryStr}* Adding field:  {$fieldName}  $desc\n";
		}
		// generate sql
		$sql = "ALTER TABLE `__TABLE__{$tableName}` ";
		$sql .= (
			$exists
			? "CHANGE `{$fieldName}` "
			: 'ADD '
		);
		$sql .= $field->getSQL();
		// insert after field (or front of table)
		if ($exists !== TRUE && !empty($afterFieldName)) {
			if ($afterFieldName === '__FIRST__') {
				$sql .= ' FIRST';
			} else {
				$afterFieldName = San::AlphaNumUnderscore(
					(string) $afterFieldName
				);
				$sql .= " AFTER `{$afterFieldName}`";
			}
		}
		// primary key
		if ($field->isPrimaryKey() || $field->isAutoIncrement()) {
			$sql .= ", ADD PRIMARY KEY ( `{$fieldName}` )";
		}
		// unique
		if ($field->isUnique()) {
			$sql .= ", ADD UNIQUE ( `{$fieldName}` )";
		}
		// alter table
//		$desc = $field->getDesc();
//		echo "{$dryStr} Adding field: {$desc}\n";
//		echo "{$dryStr} Changing field: {$desc}\n";
		$db = $pool->getDB();
		$db->setDry($dry);
		$result = $db->Execute(
			$sql,
			"AddChangeTableField({$tableName}::{$fieldName})"
		);
		if ($result->hasError()) {
			fail("Failed to add/modify table field: {$tableName}:: $desc",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		// finished
		unset($sql);
		$db->release();
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
				$size = $exist->getSize();
				if ($size === NULL) {
					$msg .= 'NULL';
				} else {
					$msg = "size({$size}";
					if (Numbers::isNumber($size)) {
						$msg .= (int) $size;
					} else {
						$msg .= "'{$size}'";
					}
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
