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
use pxn\pxdb\dbField;
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
		// missing table
		if (!$tableExists) {
			$msg = "<MISSING> {$poolName}:{$tableName}";
			echo "$msg\n";
			return TRUE;
		}
		// found table
		$existTable = $pool->getExistingTable($tableName);
		$schemFields = $pool
			->getSchemaTable($tableName)
				->getFields();
		$fieldCount = \count($schemFields);
		$msg = "<found> {$poolName}:{$tableName}  Fields: {$fieldCount}\n";
		$msg .= "\n";
		// list/check the expected fields
		if ($fieldCount > 0) {
			if ($this->flagShowFields || $this->flagCheckFields) {
				$strings = [
					[ '   Type ', ' Name ', ' Changes Needed ' ],
					[ '  ======', '======', '================' ]
				];
				foreach ($schemFields as $fieldName => $field) {
					// prepare schema field
					$schemField = $field->duplicate();
					$schemField->ValidateKeys();
					$schemField->FillKeysSchema();
					// check for needed changes
					$needsChangesStr = '';
					if ($this->flagCheckFields) {
						// field exists
						$exists = $existTable->hasField($fieldName);
						if ($exists) {
							$existField = $existTable->getField($fieldName);
							$needsChanges = dbField::CheckFieldNeedsChanges($existField, $schemField);
							if (\is_array($needsChanges)) {
								$needsChangesStr = \implode($needsChanges, ', ');
							}
						// missing field
						} else {
							$needsChangesStr = 'MISSING';
						}
					}
					// build display string
					$fieldType = $schemField->getType();
					$fieldSize = $schemField->getSize();
					$fieldTypeStr = (
						!empty($fieldSize)
						? "{$fieldType}({$fieldSize})"
						: $fieldType
					);
					$strings[$fieldName] = [
						"  $fieldTypeStr",
						$fieldName,
						$needsChangesStr
					];
				}
				$msg .= \implode(Strings::PadColumns($strings, 8, 8), "\n");
				unset ($strings);
				echo "$msg\n";
			}
		}
		return TRUE;
	}



}
