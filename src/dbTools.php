<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;


final class dbTools {
	private function __construct() {}



	public static function LoadSchema(string|dbConn|dbPool $db, string $file, string $clss): bool {
		if (!\str_starts_with($file, '/'))
			$file = __DIR__.'schemas/'.$file;
		require($file);
		$sch    = new $clss();
		$schema = $sch->getSchema();
		$pool   = dbPool::getPool();
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
				$sql = "CREATE TABLE `$table_name` (`$first_name`";
				$attribs = self::BuildFieldType($sql, $first_field);
				$sql .= ');';
				$db->prepare($sql);
				$db->exec();
				// update cache
				$pool->existing_tables[$table_name] = [
					$first_name => [ $attribs ],
				];
				$tab = $pool->getRealTableSchema($table_name);
				$did_something = true;
			} // end create new table
			// check table fields
			foreach ($fields as $key => $field) {
				// add field
				if (!isset($tab[$key])) {
					$sql = "ALTER TABLE `$table_name` ADD COLUMN `$key`";
					$attribs = self::BuildFieldType($sql, $field);
					$sql .= ';';
					$db->prepare($sql);
					$db->exec();
					$did_something = true;
				}

//TODO: modify existing fields

			} // end fields loop
		} // end tables loop
		return $did_something;
	}

	protected static function BuildFieldType(string &$sql, array $field): array {
		$attribs = [
			'type' => \mb_strtoupper($field['type']),
			'primary'  => false,
			'nullable' => false,
			'autoinc'  => false,
		];
		# type
		$sql .= ' '.$attribs['type'];
		# primary key
		if (isset($field['primary'])
		&& $field['primary'] === true) {
			$sql .= ' PRIMARY KEY';
			$attribs['primary'] = true;
		}
		# auto increment
		if (isset($field['autoinc'])
		&& $field['autoinc'] === true) {
			$sql .= ' AUTOINCREMENT';
			$attribs['autoinc'] = true;
		}
		// null/not-null
		if (isset($field['null'])
		&& $field['null'] === true)
			$attribs['nullable'] = true;
		if ($attribs['nullable'] === false)
			$sql .= ' NOT NULL';
		// default value
		if (isset($field['default'])) {
			if ($field['default'] == 'null'
			||  $field['default'] == 'NULL') {
				$sql .= ' DEFAULT NULL';
			} else {
				$sql .= " DEFAULT '".$field['default']."'";
			}
		} else
		if ($attribs['nullable'] === true) {
			$sql .= ' DEFAULT NULL';
		} else {
			switch (\mb_strtoupper($field['type'])) {
				case 'INTEGER':
					if ($attribs['autoinc'] === false)
						$sql .= ' DEFAULT 0';
					break;
				case 'TEXT': $sql .= " DEFAULT ''"; break;
				default: throw new \RuntimeException('Unknown field type: '.$field['type']);
			}
		}
		return $attribs;
	}



}
