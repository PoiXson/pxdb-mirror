<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2022
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 * /
namespace pxn\pxdb\shell;

use pxn\pxdb\dbPool;
use pxn\pxdb\dbTools;

use pxn\phpUtils\Strings;
use pxn\phpUtils\ShellTools;


abstract class dbCommand_Common extends dbCommand {

	const CMD_LIST_FIELDS = 1;
	const CMD_CHECK       = 2;

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
		// missing table
		if (!$pool->hasExistingTable($tableName)) {
			echo ShellTools::FormatString(
				"{color=red}<MISSING>{reset} {$poolName}:{$tableName}"
			);
			return 'MISSING-TABLE';
		}
		// found table
		$existTable = $pool->getExistingTable($tableName);
		$schemFields = $pool
			->getSchemaTable($tableName)
				->getFields();
		$fieldCount = \count($schemFields);
		$msg = "<found> {color=green}{$poolName}:{$tableName}{reset}  Fields: {color=green}{$fieldCount}{reset}\n";
		// list/check the expected fields
		if ($fieldCount > 0) {
			if ($this->isCMD(self::CMD_LIST_FIELDS | self::CMD_CHECK)) {
				$changesArray = [];
				foreach ($schemFields as $fieldName => $field) {
					// prepare schema field
					$schemField = $field->duplicate();
					$schemField->ValidateKeys();
					$schemField->FillKeysSchema();
					// check for needed changes
					$changesNeeded = [];
					if ($this->isCMD(self::CMD_CHECK)) {
						// field exists
						if ($existTable->hasField($fieldName)) {
							$existField = $existTable->getField($fieldName);
							$result = dbTools::CheckFieldNeedsChanges($existField, $schemField);
							if (\is_array($result)) {
								if (ShellTools::isAnsiColorEnabled()) {
									foreach ($result as $index => &$value) {
										$value = "{color=orange}$value{reset}";
									}
								}
								$changesNeeded[$fieldName] = $result;
							}
						// missing field
						} else {
							$changesNeeded[$fieldName] = '{color=red}MISSING{reset}';
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
				if ($this->isCMD(self::CMD_CHECK)) {
					$strings[0][] = ' Changes Needed ';
					$strings[1][] = '================';
				}
				$strings = \array_merge(
					$strings,
					$changesArray
				);
				$msg .= \implode(Strings::PadColumns($strings, 8, 8), "\n");
				unset ($strings);
				echo ShellTools::FormatString(
					"$msg\n\n"
				);
				return $changesArray;
			}
		}
		return TRUE;
	}



}
*/
