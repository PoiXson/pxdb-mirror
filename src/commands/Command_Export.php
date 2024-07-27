<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb\commands;


class Command_Export extends \pxn\phpShell\Command {



	public function run(): int {
//TODO
return 1;
	}



}
/*
	// returns true if successful
	public function execute(dbPool|dbConn $pool, string $table_name): void {
		$dry_str   = ($this->isDry() ? '{color=orange}[DRY]{reset} ' : '');
		$pool      = dbPool::getPool($pool);
		$pool_name = $pool->getPoolName();
		$exists    = $pool->hasExistingTable($table_name);
		if (!$exists) throw new \Exception('Cannot export, table not found: '.$table_name);
		$exist_table = $pool->getExistingTable($table_name);
		if ($exist_table === null) throw new \Exception('Unknown table: '.$pool_name.':'.$table_name);
//TODO: fix file name here
$path = '/run/media/lop/usb16/wwww/gc-website/';
$filename = 'testfile.txt';
$filepath = "{$path}{$filename}";
		echo ShellUtils::FormatString("{$dry_str}Exporting Table: {color=green}{$pool_name}:{$table_name}{reset}\n");
		// prepare file for writing
		if (\file_exists($filepath)) throw new \Exception('File already exists: '.$filepath);
		if (\is_writable($filepath)) throw new \Exception('Cannot write to file: '.$filepath);
		$sql = "SELECT * FROM `__TABLE__{$table_name}`";
		$db = $pool->get();
		$db->setDry($this->dry);
		$result = $db->Execute($sql, "Export({$table_name})");
		if ($result->hasError()) throw new \Exception('Failed to export table: '.table_name);
		unset($sql);
		$count_records = $result->getRowCount();
		if ($count_records < 1) {
			echo ShellUtils::FormatString("{$dry_str}{color=red}No records found to export!{reset}");
			return;
		}
		// open file for writing
		if ($this->notDry()) {
			$handle = \fopen($filepath, 'w');
			if (!$handle) throw new \Exception('Failed to open file for writing: '.$filepath);
		}
		// export data
		echo ShellUtils::FormatString(
			"{$dry_str}Exporting {color=green}$count_records{reset} records..\n".
			"{$dry_str}To file: {$filepath}\n"
		);
		if ($this->notDry()) {
			\fwrite(
				$handle,
				"Table Name: {$table_name}\n".
				"Records: {$count_records}\n"
			);
		}
		$count_lines = 0;
		$count_total = 0;
		$count_files = 1;
		while ($result->hasNext()) {
			// split every 10k lines
			if ($count_lines == 10000) {
				$count_lines = 0;
				// open new file
				$count_files++;
//TODO: fix file name here
				echo ShellUtils::FormatString("{$dry_str}Writing to file: {$filepath}-{$count_files} ..");
				if ($this->notDry()) {
					\fclose($handle);
					$handle = \fopen("{$filepath}-{$count_files}", 'w');
					if (!$handle) throw new \Exception('Failed to open file for writing: '.$filepath);
				}
			}
			// export line
			$count_lines++;
			$count_total++;
			$row = $result->getRow();
			if ($this->notDry()) {
				\fwrite(
					$handle,
					($count_lines > 1 ? ",\n" : '').
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
		$plural_record = ($count_records == 1 ? '' : 's');
		$plural_file   = ($count_files   == 1 ? '' : 's');
		echo ShellUtils::FormatString(
			"Successfully exported {color=green}{$count_total}{reset} record{$plural_record} to {color=green}{$count_files}{reset} file{$plural_file}!\n"
		);
		echo "\n";
	}
*/
