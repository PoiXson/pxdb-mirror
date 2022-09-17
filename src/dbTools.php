<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2022
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;

use pxn\phpUtils\Strings;
use pxn\phpUtils\San;
use pxn\phpUtils\Numbers;
use pxn\phpUtils\ShellTools;


final class dbTools {
	private function __construct() {}



	public static function LoadSchema(string|dbConn|dbPool $db, string $file, string $clss): bool {
		if (!\str_starts_with($file, '/'))
			$file = __DIR__.'schemas/'.$file;
		require($file);
		$sch = new $clss();
		$schema = $sch->getSchema();
		$pool = dbPool::getPool();
		$did_changes = false;
		for ($i=0; $i<99; $i++) {
			if (!self::CreateUpdateSchema($db, $schema))
				break;
			$pool->clearTableCache();
			$did_changes = true;
		}
		return $did_changes;
	}

	public static function CreateUpdateSchema(string|dbConn|dbPool $db, array $schema): bool {
		if (\is_string($db) || $db instanceof dbPool) {
			$db = dbPool::GetDB($db);
			$result = CreateUpdateSchema($db, $schema);
			$db->release();
			return $result;
		}
		$did_something = false;
		$pool = $db->getPool();
		// create new tables
		foreach ($schema as $table_name => $fields) {
			$tab = $pool->getRealTableSchema($table_name);
			// create new table
			if ($tab == null) {
//TODO: san
				$first_name  = \array_key_first($fields);
				$first_field = $fields[$first_name];
				$first_type  = \mb_strtoupper($first_field['type']);
				$sql = "CREATE TABLE `$table_name` (`$first_name` $first_type";
				if ($first_field['primary'] === true)
					$sql .= ' PRIMARY KEY';
				if (!isset($first_field['null'])
				||  $first_field['null'] !== true)
					$sql .= ' NOT NULL';
				$sql .= ');';
				$db->prepare($sql);
				$db->exec();
				$pool->existing_tables[$table_name] = [
					$first_name => [
						'type' => $first_type,
					],
				];
				if ($first_field['primary'] === true)
					$pool->existing_tables[$table_name][$first_name]['primary'] = true;
				$tab = $pool->getRealTableSchema($table_name);
				$did_something = true;
			} // end create new table
			// check table fields
			foreach ($fields as $key => $field) {
				// add field
				if (!isset($tab[$key])) {
					$type = $field['type'];
					$sql = "ALTER TABLE `$table_name` ADD COLUMN `$key` $type;";
					$db->prepare($sql);
					$db->exec();
					$did_something = true;
				}

//TODO: modify existing fields

			} // end fields loop
		} // end tables loop
		return $did_something;
	}



}
