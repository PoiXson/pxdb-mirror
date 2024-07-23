<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 * /
namespace pxn\pxdb\shell;

use \pxn\phpUtils\utils\ShellUtils;

use \pxn\pxdb\dbPool;
use \pxn\pxdb\dbTools;


class dbCommand_Update extends dbCommand {



	public function __construct(bool $dry=true) {
		parent::__construct($dry);
	}



	// returns true if successful
	public function execute(dbPool $pool, string $tableName): bool {
		$dryStr     = ($this->isDry() ? '{color=orange}[DRY]{reset} ' : '');
		$pool       = dbPool::getPool($pool);
		$poolName   = $pool->getName();
		$schemTable = $pool->getSchemaTable($tableName);
		echo ShellUtils::FormatString(
			"\n{$dryStr}Checking Table: {color=green}{$poolName}:{$tableName}{reset}\n"
		);

		// check table exists
		if (!$pool->hasExistingTable($tableName)) {
			// create table
			dbTools::CreateTable(
				$pool,
				$schemTable,
				$this->dry
			);
			if ($this->notDry()) {
				$table = $pool->getExistingTable($tableName);
				$fields = $table->getFields();
				$countFields = \count($fields);
				$plural = ($countFields == 1 ? '' : 's');
				echo ShellUtils::FormatString(
					"{$dryStr}Created new table with {color=green}$countFields{reset} field{$plural}\n"
				);
			}
			return true;
		}

		// check fields
		$countAdded     = 0;
		$countAlter     = 0;
		$countUnchanged = 0;
		$schemFields   = $schemTable->getFields();
		$existTable    = $pool->getExistingTable($tableName);
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
			if ($changes !== false) {
				// modify existing field
				dbTools::AddChangeTableField(
					$pool,
					$schemTable,
					$schemField,
					null,
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
			echo ShellUtils::FormatString(
				"{$dryStr}{color=green}No fields need changes.{reset}\n"
			);
		} else {
			$plural1 = ($countAdded == 1 ? '' : 's');
			$plural2 = ($countAlter == 1 ? '' : 's');
			echo ShellUtils::FormatString(
				"{$dryStr}Added {color=green}$countAdded{reset} field{$plural1}, and modified {color=green}$countAlter{reset} field{$plural2}\n"
			);
		}
		echo "\n";
		return true;
	}



}
*/
