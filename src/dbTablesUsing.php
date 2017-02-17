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

	protected static $usingTables = [];



	public static function addTable($pool, $tableName, $schema) {
		$pool = self::ValidatePool($pool);
		$poolName = $pool->getPoolName();
		if (!\array_key_exists($poolName, self::$usingTables)) {
			self::$usingTables[$poolName] = [];
		}
		// ensure safe table name
		$tableName = self::ValidateTableName($tableName);
		if (\is_string($schema)) {
			self::$usingTables[$poolName][$tableName] =
				self::ValidateSchemaClass($schema);
		} else {
			self::$usingTables[$poolName][$tableName] =
				self::GetSchemaClass($schema);
		}
		return TRUE;
	}
	// format: [ 'table_name' => 'path\\to\\schema\\class', .. ]
	public static function addTables($pool, array $tables) {
		if (count($tables) == 0) {
			return FALSE;
		}
		$pool = self::ValidatePool($pool);
		$count = 0;
		foreach ($tables as $entryName => $entry) {
			self::addTable($pool, $entryName, $entry);
			$count++;
		}
		return $count;
	}
	public static function getTable($pool, $tableName) {
		$pool = self::ValidatePool($pool);
		$poolName = $pool->getPoolName();
		// unknown pool
		if (!\array_key_exists($poolName, self::$usingTables)) {
			return NULL;
		}
		$tableName = self::ValidateTableName($tableName);
		if (empty($tableName)) {
			fail('Unknown or invalid table name!',
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		// unknown table
		if (!\array_key_exists($tableName, self::$usingTables[$poolName])) {
			fail("Unknown table schema: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		return self::$usingTables[$poolName][$tableName];
	}
	public static function getTables($pool) {
		$pool = self::ValidatePool($pool);
		$poolName = $pool->getPoolName();
		if (!\array_key_exists($poolName, self::$usingTables)) {
			return NULL;
		}
		return self::$usingTables[$poolName];
	}



}
