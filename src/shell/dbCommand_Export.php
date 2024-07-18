<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 * /
namespace pxn\pxdb\shell;

use pxn\phpUtils\utils\ShellUtils;

use pxn\pxdb\dbPool;


class dbCommand_Export extends dbCommand {



	public function __construct(bool $dry=true) {
		parent::__construct($dry);
	}



	// returns true if successful
	public function execute(dbPool $pool, string $tableName): void {
		$dryStr   = ($this->isDry() ? '{color=orange}[DRY]{reset} ' : '');
		$pool     = dbPool::getPool($pool);
		$poolName = $pool->getName();
		$exists   = $pool->hasExistingTable($tableName);
		if (!$exists) throw new \Exception('Cannot export, table not found: '.$tableName);
		$existTable = $pool->getExistingTable($tableName);
		if ($existTable === null) throw new \Exception('Unknown table: '.$poolName.':'.$tableName);
//TODO: fix file name here
$path = '/run/media/lop/usb16/wwww/gc-website/';
$filename = 'testfile.txt';
$filepath = "{$path}{$filename}";
		echo ShellUtils::FormatString(
			"{$dryStr}Exporting Table: {color=green}{$poolName}:{$tableName}{reset}\n"
		);
		// prepare file for writing
		if (\file_exists($filepath)) throw new \Exception('File already exists: '.$filepath);
		if (\is_writable($filepath)) throw new \Exception('Cannot write to file: '.$filepath);
		$sql = "SELECT * FROM `__TABLE__{$tableName}`";
		$db = $pool->get();
		$db->setDry($this->dry);
		$result = $db->Execute($sql, "Export({$tableName})");
		if ($result->hasError()) throw new \Exception('Failed to export table: '.tableName);
		unset($sql);
		$recordCount = $result->getRowCount();
		if ($recordCount < 1) {
			echo ShellUtils::FormatString(
				"{$dryStr}{color=red}No records found to export!{reset}"
			);
			return;
		}
		// open file for writing
		if ($this->notDry()) {
			$handle = \fopen($filepath, 'w');
			if (!$handle) throw new \Exception('Failed to open file for writing: '.$filepath);
		}
		// export data
		echo ShellUtils::FormatString(
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
				echo ShellUtils::FormatString(
					"{$dryStr}Writing to file: {$filepath}-{$fileCount} .."
				);
				if ($this->notDry()) {
					\fclose($handle);
					$handle = \fopen("{$filepath}-{$fileCount}", 'w');
					if (!$handle) throw new \Exception('Failed to open file for writing: '.$filepath);
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
		echo ShellUtils::FormatString(
			"Successfully exported {color=green}{$totalCount}{reset} record{$recordPlural} to {color=green}{$fileCount}{reset} file{$filePlural}!\n"
		);
		echo "\n";
	}



}
*/
