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
use pxn\pxdb\dbTablesExisting;

use pxn\phpUtils\Strings;


class dbCommand_ListCheck extends dbCommands {

	// flags
	public $flagShowFields  = FALSE;
	public $flagCheckFields = FALSE;



	// returns true if successful
	public function execute($pool, $tableName) {
		$pool     = dbPool::getPool($pool);
		$poolName = $pool->getName();
		$tableExists = $pool->hasExistingTable($tableName);
		// found table
		if ($tableExists) {
			$table = $pool->getExistingTable($tableName);
			$fields = $table->getFields();
			$fieldCount = count($fields);
			$msg = "<found> {$poolName}:{$tableName}";
			if ($this->flagShowFields) {
				$msg .= "\n Fields: {$fieldCount}\n";
				// list the fields
				if ($fieldCount > 0) {
					$maxLength = 11;
					$strings = [];
					foreach ($fields as $fieldName => $field) {
						$fieldType = $field['type'];
						$fieldTypeStr = (
							isset($field['size']) && !empty($field['size'])
							? "{$fieldType}|".$field['size']
							: $fieldType
						);
						$tmp = "  [{$fieldTypeStr}] ";
						$strings[$fieldName] = $tmp;
						$len = \strlen($tmp);
						if ($len > $maxLength) {
							$maxLength = $len;
						}
					}
					foreach ($strings as $fieldName => $fieldStr) {
						$msg .= Strings::PadLeft($fieldStr, $maxLength).$fieldName."\n";
					}
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
