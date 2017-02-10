<?php
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2017
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\pxdb\shell;

use pxn\pxdb\dbPool;
use pxn\pxdb\dbExistingTables;

use pxn\phpUtils\Strings;


class dbCommand_List extends dbCommands {



	// returns true if successful
	public function execute($pool, $tableName) {
		$pool = dbPool::getPool($pool);
		$poolName = $pool->getPoolName();
		$tableExists = dbExistingTables::hasTable($pool, $tableName);
		// found table
		if ($tableExists) {
			$msg = "{$poolName}:{$tableName} <found>";
			$fields = dbExistingTables::getFields($pool, $tableName);
			$fieldCount = count($fields);
			$msg .= "\n  Fields: {$fieldCount}";
			// list the fields
			if ($fieldCount > 0) {
				$msg .= ' ';
				foreach ($fields as $fieldName => $field) {
					$fieldType = $field['type'];
					$fieldTypeStr = (
						isset($field['size']) && !empty($field['size'])
						? "{$fieldType}|".$field['size']
						: $fieldType
					);
					$msg .= "\n  [{$fieldTypeStr}]{$fieldName}";
				}
			}
			echo "$msg\n";
		// missing table
		} else {
			$msg = "<MISSING> {$poolName}:{$tableName}";
			echo "$msg\n";
		}
		return TRUE;
	}



}
