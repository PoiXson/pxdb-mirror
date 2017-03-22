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
		$dryStr = ($this->dry ? '[DRY] ' : '');
		$pool = dbPool::getPool($pool);
		$poolName = $pool->getName();
		$schemTable = $pool->getSchemaTable($tableName);
		echo "\n{$dryStr}Checking Table: {$poolName}:{$tableName}\n";

		// check table exists
		if (!$pool->hasExistingTable($tableName)) {
			// create table
			dbTools::CreateTable(
				$pool,
				$schemTable,
				$this->dry
			);
			if (!$this->dry) {
				$table = $pool->getExistingTable($tableName);
				$fields = $table->getFields();
				$countFields = \count($fields);
				$plural = ($countFields == 1 ? '' : 's');
				echo "{$dryStr}Created new table with $countFields field{$plural}\n";
			}
			return TRUE;
		}

		// check fields
		$countAdded = 0;
		$countAlter = 0;
		$countUnchanged = 0;
		$schemFields = $schemTable->getFields();
		$existTable = $pool->getExistingTable($tableName);
		$lastFieldName = '__FIRST__';
		foreach ($schemFields as $fieldName => $field) {
			$schemField = $field->duplicate();
			$schemField->ValidateKeys();
			$schemField->FillKeysSchema();

			// missing field
			$exists = $existTable->hasField($fieldName);
			if (!$exists) {
				// add missing field
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
				dbTools::AddChangeTableField(
					$pool,
					$schemTable,
					$schemField,
					NULL,
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
