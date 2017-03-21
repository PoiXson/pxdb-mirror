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
use pxn\pxdb\dbTools;


class dbCommand_Update extends dbCommand_Common {

	private static $lastTableChanged = NULL;



	public function __construct($dry=TRUE) {
		parent::__construct($dry, self::CMD_UPDATE);
	}



	// returns true if successful
	public function execute($pool, $tableName) {
		$dryStr = ($this->dry === FALSE ? '' : '[DRY] ');
		$pool = dbPool::getPool($pool);
		$poolName = $pool->getName();
		$schemTable = $pool->getSchemaTable($tableName);

		// check table exists
		if (!$pool->hasExistingTable($tableName)) {
			// create table
//			echo "{$dryStr}Creating Table: {$tableName}\n";
			dbTools::CreateTable(
				$pool,
				$schemTable,
				$this->dry
			);
			return TRUE;
		}

		// check fields
//		echo "\n{$dryStr}Updating Field: {$tableName}\n";
		$countAdded = 0;
		$countAlter = 0;
		$countUnchanged = 0;
		$schemFields = $schemTable->getFields();
		$existTable = $pool->getExistingTable($tableName);
		$lastFieldName = NULL;
//TODO: need to check for auto-increment, primary key, and unique
		foreach ($schemFields as $fieldName => $field) {
			$schemField = $field->duplicate();
			$schemField->ValidateKeys();
			$schemField->FillKeysSchema();

			// missing field
			$exists = $existTable->hasField($fieldName);
			if (!$exists) {
				// add missing field
				$desc = $schemField->getDesc();
//				echo " {$dryStr}* Adding field:  {$fieldName}\n";
//				echo " {$dryStr}    $desc\n";
				dbTools::AddChangeTableField(
					$pool,
					$schemTable,
					$schemField,
					$lastFieldName,
					$this->dry
				);
				$countAdded++;
				$lastFieldName = $fieldName;
				continue;
			}

			// check existing field
			$existField = $existTable->getField($fieldName);
			$existField = $existField->duplicate();
			$existField->ValidateKeys();
			$existField->FillKeysExisting();
			$changes = dbTools::CheckFieldNeedsChanges($existField, $schemField);
			if ($changes !== FALSE) {
				// modify existing field
				$existDesc = $existField->getDesc();
				$schemDesc = $schemField->getDesc();
//				echo " {$dryStr}* Changing field:  {$fieldName}\n";
//				echo " {$dryStr}    from: {$existDesc}\n";
//				echo " {$dryStr}      to: {$schemDesc}\n";
				dbTools::UpdateTableField(
					$pool,
					$schemTable,
					$schemField,
					$this->dry
				);
				$countAlter++;
				$lastFieldName = $fieldName;
				continue;
			}
			// no changes needed
			$countUnchanged++;
			$lastFieldName = $fieldName;

		}
		// finished
		if ($countAdded == 0 && $countAlter == 0) {
			echo "{$dryStr}No fields need changes.\n";
		} else {
			$plural1 = ($countAdded == 1 ? '' : 's');
			$plural2 = ($countAlter == 1 ? '' : 's');
			echo "{$dryStr}Added $countAdded field{$plural1}, and modified $countAlter field{$plural2}\n";
		}
		return TRUE;
	}



}
