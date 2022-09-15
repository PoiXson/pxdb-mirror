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

use pxn\phpUtils\ShellTools;
use pxn\phpUtils\Defines;


class dbCommand_Export extends dbCommand {



	public function __construct($dry=TRUE) {
		parent::__construct($dry);
	}



	// returns true if successful
	public function execute($pool, $tableName) {
		$dryStr = ($this->isDry() ? '{color=orange}[DRY]{reset} ' : '');
		$pool = dbPool::getPool($pool);
		$poolName = $pool->getName();
		$exists = $pool->hasExistingTable($tableName);
		if (!$exists) {
			fail("Cannot export, table not found: $tableName",
				Defines::EXIT_CODE_USAGE_ERROR);
		}
		$existTable = $pool->getExistingTable($tableName);
		if ($existTable === NULL) {
			fail("Unknown table: {$poolName}:{$tableName}",
				Defines::EXIT_CODE_USAGE_ERROR);
		}
//TODO: fix file name here
$path = '/run/media/lop/usb16/wwww/gc-website/';
$filename = 'testfile.txt';
$filepath = "{$path}{$filename}";
		echo ShellTools::FormatString(
			"{$dryStr}Exporting Table: {color=green}{$poolName}:{$tableName}{reset}\n"
		);
		// prepare file for writing
		if (\file_exists($filepath)) {
			fail("File already exists: $filepath",
				Defines::EXIT_CODE_USAGE_ERROR);
		}
		if (\is_writable($filepath)) {
			fail("Cannot write to file: $filepath",
				Defines::EXIT_CODE_USAGE_ERROR);
		}
		$sql = "SELECT * FROM `__TABLE__{$tableName}`";
		$db = $pool->getDB();
		$db->setDry($this->dry);
		$result = $db->Execute(
			$sql,
			"Export({$tableName})"
		);
		if ($result->hasError()) {
			fail("Failed to export table: $tableName",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		unset($sql);
		$recordCount = $result->getRowCount();
		if ($recordCount < 1) {
			echo ShellTools::FormatString(
				"{$dryStr}{color=red}No records found to export!{reset}"
			);
			return;
		}
		// open file for writing
		if ($this->notDry()) {
			$handle = \fopen($filepath, 'w');
			if (!$handle) {
				fail("Failed to open file for writing: $filepath",
					Defines::EXIT_CODE_INTERNAL_ERROR);
			}
		}
		// export data
		echo ShellTools::FormatString(
			"{$dryStr}Exporting {color=green}$recordCount{reset} records..\n".
			"{$dryStr}To file: {$filepath}\n"
		);
		if ($this->notDry()) {
			\fwrite(
				$handle,
				"Table Name: {$tableName}\n".
				"Records: {$recordCount}\n"
			);
		}
		$lineCount  = 0;
		$totalCount = 0;
		$fileCount  = 1;
		while ($result->hasNext()) {
			// split every 10k lines
			if ($lineCount == 10000) {
				$lineCount = 0;
				// open new file
				$fileCount++;
//TODO: fix file name here
				echo ShellTools::FormatString(
					"{$dryStr}Writing to file: {$filepath}-{$fileCount} .."
				);
				if ($this->notDry()) {
					\fclose($handle);
					$handle = \fopen("{$filepath}-{$fileCount}", 'w');
					if (!$handle) {
						fail("Failed to open file for writing: $filepath",
							Defines::EXIT_CODE_INTERNAL_ERROR);
					}
				}
			}
			// export line
			$lineCount++;
			$totalCount++;
			$row = $result->getRow();
			if ($this->notDry()) {
				\fwrite(
					$handle,
					($lineCount > 1 ? ",\n" : '').
					\json_encode($row, \JSON_PRETTY_PRINT)
				);
			}
		}
		if ($this->notDry()) {
			\fwrite($handle, "\n");
			\fclose($handle);
		}
		$db->release();
		// finished
		$recordPlural = ($recordCount == 1 ? '' : 's');
		$filePlural   = ($fileCount   == 1 ? '' : 's');
		echo ShellTools::FormatString(
			"Successfully exported {color=green}{$totalCount}{reset} record{$recordPlural} to {color=green}{$fileCount}{reset} file{$filePlural}!\n"
		);
		echo "\n";
	}



}
*/
