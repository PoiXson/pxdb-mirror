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


class dbTableExisting extends dbTable {



	public function initFields() {
		$this->inited = TRUE;
		$tableName = $this->tableName;
		if (Strings::StartsWith($this->tableName, '_')) {
			$poolName  = $this->pool->getPoolName();
			$tableName = $this->tableName;
			fail("Table name cannot start with _ underscore: {$poolName}:{$tableName}",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		// load fields in table
		$db = $this->pool->getDB();
		$db->Execute(
			"DESCRIBE `__TABLE__{$tableName}`;",
			'LoadTableFields()'
		);
		$this->fields = [];
		while ($db->hasNext()) {
			$row = $db->getRow();
			// field name
			$fieldName = $db->getString('Field');
			if (Strings::StartsWith($fieldName, '_')) {
				continue;
			}
			// type
			$fieldType = $db->getString('Type');
			// size
			$fieldSize = NULL;
			$pos = \mb_strpos($fieldType, '(');
			if ($pos !== FALSE) {
				$fieldSize = Strings::Trim(
					\mb_substr($fieldType, $pos),
					'(', ')'
				);
				$fieldType = \mb_substr($fieldType, 0, $pos);
			}
			// new field object
			$field = new dbField(
				$fieldName,
				$fieldType,
				$fieldSize
			);
			// null / not null
			$nullable = (\mb_strtoupper( $db->getString('Null') ) == 'YES');
			if ($nullable) {
				$field->setNullable(TRUE);
			}
			// default value
			if (\array_key_exists('default', $row)) {
				$default = (
					$row['default'] === NULL
					? NULL
					: $db->getString('Default')
				);
				$field->setDefault($default);
			}
			// primary key
			if (\mb_strtoupper( $db->getString('Key') ) == 'PRI') {
				$field->setPrimary(TRUE);
			}
			// auto increment
			$extra = $db->getString('Extra');
			if (\mb_strpos(\mb_strtolower($extra), 'auto_increment') !== FALSE) {
				$field->setIncrement(TRUE);
			}
			$this->fields[$fieldName] = $field;
		}
		$db->release();
		return \count($this->fields);
	}



/*
	// returns a list of table keys (field values may not yet be cached)
	public static function getTables($pool) {
		$pool = self::ValidatePool($pool);
		$poolName = $pool->getName();
		self::LoadPoolTables($pool);
		if (\array_key_exists($poolName, self::$cache)) {
			return self::$cache[$poolName];
		}
		return NULL;
	}
	public static function getFields($pool, $tableName) {
		$pool      = self::ValidatePool($pool);
		$tableName = self::ValidateTableName($tableName);
		$poolName = $pool->getName();
		if (Strings::StartsWith($tableName, '_')) {
			fail("Table name cannot start with _ underscore: {$poolName}:{$tableName}",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		self::LoadPoolTables($pool);
		if (!\array_key_exists($poolName, self::$cache)) {
			fail("Unknown pool: $poolName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		// load fields in table
		self::LoadTableFields($pool, $tableName);
		if (!\array_key_exists($tableName, self::$cache[$poolName])) {
			fail("Unknown table: {$poolName}:{$tableName}",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		return self::$cache[$poolName][$tableName];
	}
*/



}
