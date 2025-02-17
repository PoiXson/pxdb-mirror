<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2025
 * @license AGPLv3+ADD-PXN-V1
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

use \pxn\phpUtils\utils\SanUtils;


final class dbTools {
	private function __construct() {}



	public static function GetTables(dbPool $pool): array {
		$db = dbPool::GetDB($pool);
		$tables = [];
		try {
			$driver = $db->getDriver();
			$sql = match ($driver) {
				dbDriver::SQLite => $sql = 'SELECT `name` FROM `sqlite_master` WHERE `type`=\'table\'',
				dbDriver::MySQL  => $sql = 'SHOW TABLES',
				default => null,
			};
			if ($driver == null)
				throw new \RuntimeException('Unknown database driver type: '.$driver->toString());
			$db->exec($sql);
			while ($db->hasNext()) {
				$name = $db->getString('name');
				if (\str_starts_with(haystack: $name, needle: '_'))
					continue;
				$tables[] = $name;
			}
		} finally {
			$db->release();
		}
		return $tables;
	}



	public static function CreateTable(dbPool $pool, dbTable $table): void {
		$db = dbPool::GetDB($pool);
		try {
			$driver = $pool->getDriver();
			$table_name = self::ValidateTableName($table->getTableName());
			$field = $table->getFirstField();
			$sql_field = $field->buildFieldSQL($driver);
			$db->exec("CREATE TABLE `__TABLE__$table_name` ( $sql_field )");
		} finally {
			$db->release();
		}
	}

	public static function UpdateTableFields(dbPool $pool, dbTable $table): int {
		$db = dbPool::GetDB($pool);
		$driver = $pool->getDriver();
		$count = 0;
		try {
			// find existing fields
			$table_name = self::ValidateTableName($table->getTableName());
			$sql = match ($driver) {
				dbDriver::SQLite => "PRAGMA TABLE_INFO (`__TABLE__$table_name`)",
				dbDriver::MySQL  => "SHOW COLUMNS IN `__TABLE__$table_name`",
				default => null
			};
			$db->exec($sql);
			// cid, name, type, notnull, dflt_value, pk
			$existing = [];
			while ($db->hasNext()) {
				$name = $db->getString('name');
				if (\str_starts_with(haystack: $name, needle: '_'))
					continue;
				$existing[$name] = $db->getRow();
			}
			$fields = $table->getFields();
			foreach ($fields as $field) {
				$name = self::ValidateFieldName($field->getFieldName());
				// add field
				if (!isset($existing[$name])) {
					$db->clean(true);
					$sql = "ALTER TABLE `__TABLE__$table_name` ADD ";
					$sql .= $field->buildFieldSQL($driver);
					$db->exec($sql);
					$count++;
				// modify field
				} else {
//TODO: modify existing fields
				}
			}
		} finally {
			$db->release();
		}
		return $count;
	}



	public static function ValidatePoolName(string $name): string {
		if (!SanUtils::is_alpha_num_simple($name))
			throw new \RuntimeException('Pool name is invalid: '.$name);
		$name = SanUtils::alpha_num_simple($name);
		if (empty($name)) throw new \RuntimeException('Invalid pool name empty');
		if (\str_starts_with(haystack: $name, needle: '_'))
			throw new \Exception('Pool name cannot start with _ underscore: '.$name);
		return $name;
	}
	public static function ValidateTableName(string $name): string {
		if (!SanUtils::is_alpha_num_simple($name))
			throw new \RuntimeException('Table name is invalid: '.$name);
		$name = SanUtils::alpha_num_simple($name);
		if (empty($name)) throw new \RuntimeException('Invalid table name empty');
		if (\str_starts_with(haystack: $name, needle: '_'))
			throw new \Exception('Table name cannot start with _ underscore: '.$name);
		return $name;
	}
	public static function ValidateFieldName(string $name): string {
		if (!SanUtils::is_alpha_num_simple($name))
			throw new \RuntimeException('Field name is invalid: '.$name);
		$name = SanUtils::alpha_num_simple($name);
		if (empty($name)) throw new \RuntimeException('Invalid field name empty');
		if (\str_starts_with(haystack: $name, needle: '_'))
			throw new \Exception('Field name cannot start with _ underscore: '.$name);
		return $name;
	}



}
