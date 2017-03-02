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


abstract class dbCommand_Common extends dbCommand {

	const CMD_LIST_FIELDS = 1;
	const CMD_CHECK       = 2;
	const CMD_UPDATE      = 4;
	const CMD_IMPORT      = 8;
	const CMD_EXPORT      = 16;

	protected $cmdFlags = 0;



	public function __construct($dry=TRUE, $cmdFlags) {
		parent::__construct($dry);
		$this->cmdFlags = $cmdFlags;
	}



	public function isCMD($isFlag) {
		return ($isFlag & $this->cmdFlags);
	}



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
			if ($this->isCMD(self::CMD_LIST_FIELDS | self::CMD_CHECK | self::CMD_UPDATE)) {
				$changesArray = [];
				foreach ($schemFields as $fieldName => $field) {
					// prepare schema field
					$schemField = $field->duplicate();
					$schemField->ValidateKeys();
					$schemField->FillKeysSchema();
					// check for needed changes
					$changesNeeded = [];
					if ($this->isCMD(self::CMD_CHECK | self::CMD_UPDATE)) {
						// field exists
						$exists = $existTable->hasField($fieldName);
						if ($exists) {
							$existField = $existTable->getField($fieldName);
							$result = dbField::CheckFieldNeedsChanges($existField, $schemField);
							if (\is_array($result)) {
								$changesNeeded[$fieldName] = $result;
							}
						// missing field
						} else {
							$changesNeeded[$fieldName] = 'MISSING';
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
					$changesArray[$fieldName] = [
						'type' => "  $fieldTypeStr",
						'name' => $fieldName,
						'changes' => $changesNeeded
					];
				}
				$strings = [];
				$strings[0] = ['   Type ', ' Name '];
				$strings[1] = ['  ======', '======'];
				if ($this->isCMD(self::CMD_CHECK | self::CMD_UPDATE)) {
					$strings[0][] = ' Changes Needed ';
					$strings[1][] = '================';
				}
				$strings = \array_merge(
					$strings,
					$changesArray
				);
				$msg .= \implode(Strings::PadColumns($strings, 8, 8), "\n");
				unset ($strings);
				echo "$msg\n";
				return $changesArray;
			}
		}
		return TRUE;
	}



}
