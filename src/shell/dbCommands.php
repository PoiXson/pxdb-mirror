<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 * /
namespace pxn\pxdb\shell;

use pxn\phpUtils\San;
use pxn\phpUtils\ShellTools;
use pxn\phpUtils\Strings;
use pxn\phpUtils\System;
use pxn\phpUtils\Defines;

use pxn\pxdb\dbPool;


final class dbCommands {
	private function __construct() {}



	public static function RunShellCommand() {
		System::RequireShell();

		// get command argument
		$cmd = ShellTools::getArg(1);
		$cmd = \mb_strtolower($cmd);
		if (empty($cmd)) {
			self::DisplayHelp();
			ExitNow(Defines::EXIT_CODE_GENERAL);
		}

		// -h or --help
		if (ShellTools::isHelp()) {
			self::DisplayHelp($cmd);
			ExitNow(Defines::EXIT_CODE_GENERAL);
		}
		$helpMsg = [];

		// is dry run?
		{
			$dry = ShellTools::getFlagBool('-D', '--dry');
			// default dry run
			if ($dry === NULL) {
				// commands which modify
				if ($cmd == 'update' || $cmd == 'import') {
					$dry = TRUE;
				} else {
					$dry = FALSE;
				}
			}
			// confirmed, not dry run
			$confirm = ShellTools::getFlagBool('--confirm');
			if ($confirm != FALSE) {
				$dry = FALSE;
			}
		}

		// commands: list, check, update, import, export

		// --pool/--table flags
		{
			$poolFlag  = ShellTools::getFlag('-p', '--pool');
			$tableFlag = ShellTools::getFlag('-t', '--table');
			if (!empty($poolFlag) || !empty($tableFlag)) {
				$poolFlag  = self::ValidatePoolTableArg($poolFlag);
				$tableFlag = self::ValidatePoolTableArg($tableFlag);
				if (empty($poolFlag)) {
					fail('Invalid pool name provided!',
						Defines::EXIT_CODE_INVALID_ARGUMENT);
				}
				if (empty($tableFlag)) {
					fail('Invalid table name provided!',
						Defines::EXIT_CODE_INVALID_ARGUMENT);
				}
				// perform the command
				$result = self::_doRunCommand(
					$cmd,
					$poolFlag,
					$tableFlag,
					$dry
				);
				echo "\n";
				return ($result !== FALSE);
			}
			unset ($poolFlag, $tableFlag);
		}

		// pool:table arguments
		{
			$args = ShellTools::getArgs();
			\array_shift($args);
			\array_shift($args);
			if (\count($args) == 0) {
				$helpMsg[] = 'No pools:tables in request!';
			} else {
				// split pool:table arguments
				$entries = self::SplitPoolTable($args);
				// perform the command
				$count = 0;
				foreach ($entries as $entry) {
					$result = self::_doRunCommand(
						$cmd,
						$entry['pool'],
						$entry['table'],
						$dry
					);
					if ($result === FALSE) {
						echo "\n";
						return FALSE;
					}
					$count++;
				}
				if ($count > 0) {
					echo "\n";
					return TRUE;
				}
			}
		}

		// no argument (default to all pools/tables)
		if ($cmd == 'list' || $cmd == 'check' || $cmd == 'update') {
			$result = self::_doRunCommand(
				$cmd,
				'*',
				'*',
				$dry
			);
			echo "\n";
			return ($result !== FALSE);
		}

		// command not handled
		self::DisplayHelp($cmd, $helpMsg);
		ExitNow(Defines::EXIT_CODE_INVALID_COMMAND);
	}
	private static function _doRunCommand($cmd, $pool, $table, $dry=TRUE) {
		$dry = ($dry !== FALSE);
		if ($dry) {
			echo ShellTools::FormatString(
				" {color=orange}[Dry Mode]{reset} \n"
			);
		}

		// all pools and tables
		if ($pool == '*' && $table == '*') {
			echo ShellTools::FormatString(
				" Cmd: {color=green}$cmd{reset}  Pool: {color=green}-all-{reset}  Table: {color=green}-all-{reset}\n\n"
			);
			$pools = dbPool::getPools();
			if ($pools === NULL || count($pools) == 0) {
				fail('No database pools configured!',
					Defines::EXIT_CODE_CONFIG_ERROR);
			}
			$count = 0;
			foreach ($pools as $poolEntryName => $poolEntry) {
				$tables = $poolEntry->getSchemaTables();
				foreach ($tables as $tableEntryName => $tableEntry) {
					$result = self::_doRunCommandOnce(
						$cmd,
						$poolEntry,
						$tableEntryName,
						$dry
					);
					if ($result === FALSE) {
						$plural = ($count == 1 ? '' : 's');
						echo ShellTools::FormatString(
							" {color=red}Ran $cmd on {color=green}$count{color=red} table{$plural}, then failed!{reset}\n"
						);
						if ($dry) {
							echo ShellTools::FormatString(
								" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
							);
						}
						return FALSE;
					}
					$count++;
				}
			}
			$plural = ($count == 1 ? '' : 's');
			echo ShellTools::FormatString(
				" Ran {color=green}$cmd{reset} on {color=green}$count{reset} table{$plural}\n"
			);
			if ($dry) {
				echo ShellTools::FormatString(
					" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
				);
			}
			return TRUE;
		}

		// all pools
		if ($pool == '*') {
			echo ShellTools::FormatString(
				" Cmd: {color=green}$cmd{reset}  Pool: {color=green}-all-{reset}  Table: {color=green}$table{reset}\n\n"
			);
			$pools = dbPool::getPools();
			if ($pools === NULL || count($pools) == 0) {
				fail('No database pools configured!',
					Defines::EXIT_CODE_CONFIG_ERROR);
			}
			$count = 0;
			foreach ($pools as $poolEntryName => $poolEntry) {
				if (!$poolEntry->hasExistingTable($table)) {
					continue;
				}
				$result = self::_doRunCommandOnce(
					$cmd,
					$poolEntry,
					$table,
					$dry
				);
				if ($result === FALSE) {
					$plural = ($count == 1 ? '' : 's');
					echo ShellTools::FormatString(
						" {color=red}Ran $cmd on {color=green}$count{color=red} table{$plural}, then failed!{reset}\n"
					);
					if ($dry) {
						echo ShellTools::FormatString(
							" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
						);
					}
					return FALSE;
				}
				$count++;
			}
			if ($count == 0) {
				fail("Table not found: {$pool}:{$table}",
					Defines::EXIT_CODE_USAGE_ERROR);
			}
			$plural = ($count == 1 ? '' : 's');
			echo ShellTools::FormatString(
				" Ran {color=green}$cmd{reset} on {color=green}$count{reset} table{$plural}\n"
			);
			if ($dry) {
				echo ShellTools::FormatString(
					" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
				);
			}
			return TRUE;
		}

		// all tables
		if ($table == '*') {
			$poolName = dbPool::castPoolName($pool);
			echo ShellTools::FormatString(
				" Cmd: {color=green}$cmd{reset}  Pool: {color=green}$poolName{reset}  Table: {color=green}-all-{reset}\n\n"
			);
			$poolEntry = dbPool::getPool($pool);
			if ($poolEntry == NULL) {
				fail('Invalid pool!',
					Defines::EXIT_CODE_INVALID_ARGUMENT);
			}
			$tables = $poolEntry->getSchemaTables();
			$count = 0;
			foreach ($tables as $tableEntryName => $tableEntry) {
				$result = self::_doRunCommandOnce(
					$cmd,
					$poolEntry,
					$tableEntryName,
					$dry
				);
				if ($result === FALSE) {
					$plural = ($count == 1 ? '' : 's');
					echo ShellTools::FormatString(
						" Ran {color=green}$cmd{reset} on {color=green}$count{reset} table{$plural}\n"
					);
					if ($dry) {
						echo ShellTools::FormatString(
							"{color=orange} [ Dry Mode - No changes made ]{reset}\n"
						);
					}
					return FALSE;
				}
				$count++;
			}
			$plural = ($count == 1 ? '' : 's');
			echo ShellTools::FormatString(
				" Ran {color=green}$cmd{reset} on {color=green}$count{reset} table{$plural}\n"
			);
			if ($dry) {
				echo ShellTools::FormatString(
					" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
				);
			}
			return TRUE;
		}

		// one pool/table
		$poolName = dbPool::castPoolName($pool);
		echo ShellTools::FormatString(
			" Cmd: {color=green}$cmd{reset}  Pool: {color=green}$poolName{reset}  Table: {color=green}$table{reset}\n\n"
		);
		$result = self::_doRunCommandOnce(
			$cmd,
			$pool,
			$table,
			$dry
		);

		return $result;
	}
	private static function _doRunCommandOnce($cmd, $pool, $table, $dry=TRUE) {
		$dry = ($dry !== FALSE);
		$poolName = dbPool::castPoolName($pool);
		$pool = dbPool::getPool($pool);
		if ($pool == NULL) {
			fail("Failed to find db pool: $poolName",
				Defines::EXIT_CODE_INVALID_ARGUMENT);
		}
		if (Strings::StartsWith($table, '_')) {
			fail("Table cannot start with _ underscore: {$poolName}:{$table}",
				Defines::EXIT_CODE_INTERNAL_ERROR);
		}
		$cmdObj = NULL;
		switch ($cmd) {
		// list pools/tables
		case 'list':
			$cmdObj = new dbCommand_List($dry);
			if (ShellTools::getFlagBool('-f', '--show-fields')) {
				$cmdObj->flagShowFields = TRUE;
			} else {
				$cmdObj->flagShowFields = FALSE;
			}
			$cmdObj->flagCheckFields = FALSE;
			break;
		// check for needed updates
		case 'check':
			$cmdObj = new dbCommand_Check($dry);
			if (ShellTools::getFlagBool('-F', '--no-fields')) {
				$cmdObj->flagShowFields = FALSE;
			} else {
				$cmdObj->flagShowFields = TRUE;
			}
			$cmdObj->flagCheckFields = TRUE;
			break;
		// update db schema
		case 'update':
			$cmdObj = new dbCommand_Update($dry);
			break;
		// import db tables
		case 'import':
			$cmdObj = new dbCommand_Import($dry);
			$cmdObj->flagImportExport = 'import';
			break;
		// export db tables
		case 'export':
			$cmdObj = new dbCommand_Export($dry);
			$cmdObj->flagImportExport = 'export';
			break;
		default:
			self::DisplayHelp();
			ExitNow(Defines::EXIT_CODE_INVALID_COMMAND);
		}
		$result = $cmdObj->execute(
			$pool,
			$table
		);
		return $result;
	}



	private static function ValidatePoolTableArg($arg) {
		if (empty($arg)) {
			return '';
		}
		if ($arg == '*' || \mb_strtolower($arg) == 'all') {
			return '*';
		}
		return San::AlphaNumUnderscore($arg);
	}
	private static function SplitPoolTable(array $args) {
		$entries = [];
		foreach ($args as $arg) {
			$poolName  = NULL;
			$tableName = NULL;
			// split pool:table
			if (\strpos($arg, ':') === FALSE) {
				$tableName = $arg;
			} else {
				$array = \explode(':', $arg, 2);
				$poolName  = $array[0];
				$tableName = $array[1];
			}
			// parse table name
			if (empty($tableName) || $tableName == '*' || \mb_strtolower($tableName) == 'all') {
				$tableName = '*';
			} else {
				$tableName = San::AlphaNumUnderscore($tableName);
				if (empty($tableName)) {
					fail('Invalid table name provided!',
						Defines::EXIT_CODE_INVALID_ARGUMENT);
				}
			}
			// parse pool name
			if (empty($poolName) || $poolName == '*' || \mb_strtolower($poolName) == 'all') {
				$poolName = '*';
			} else {
				$poolName = San::AlphaNumUnderscore($poolName);
				if (empty($poolName)) {
					fail('Invalid pool name provided!',
						Defines::EXIT_CODE_INVALID_ARGUMENT);
				}
			}
			// build entry
			$entries["$poolName:$tableName"] = [
				'pool'  => $poolName,
				'table' => $tableName
			];
		}
		return $entries;
	}



	public static function DisplayHelp($cmd=NULL, $helpMsg=[]) {
		$help = (new \pxn\phpUtils\ShellHelp())
			->setSelfName('pxntools db')
			->setCommand($cmd)
			->setMessage($helpMsg);
		switch ($cmd) {
		case 'list':
			break;
		case 'check':
		case 'update':
			$help->appendUsage('[[pool:]table]..');
			break;
		case 'import':
		case 'export':
			$help->appendUsage('[--path <path>] [--file <filename>] [[pool:]table]..');
			break;
		default:
			$help->addCommands([
				'list'   => 'List the existing database pools/tables.',
				'check'  => 'Check for needed updates to table schemas.',
				'update' => 'Update the database tables to the current schema, and create tables as needed.',
				'import' => 'Import data from a stored backup file.',
				'export' => 'Export data to a backup stored in the file-system.',
			]);
		}
		$help->addFlags([
				'Run the command without making changes (default for some commands)'
					=> [ '-D', '--dry' ],
				'Confirm the changes to be made (overrides the --dry flag)'
					=> [ '--confirm' ],
			], 'pre');
		$help->addFlags([
				'Name of the database pool to use for the command.'
					=> [ '-p', '--pool' ],
				'Name of the table to use for the command.'
					=> [ '-t', '--table' ],
			], 'mid');
		switch ($cmd) {
		case 'list':
		case 'check':
			$help->addFlags([
				'List fields in tables.'
					=> [ '-f', '--show-fields' ],
				'Don\'t list fields.'
					=> [ '-F', '--no-fields' ],
			], 'mid2');
			break;
		case 'import':
		case 'export':
			$help->addFlags([
				'Path to search/detect file.'
					=> [ '--path' ],
				'Exact path or filename.'
					=> [ '--file' ],
			], 'mid2');
			break;
		}
		$help->Display();
	}



}
*/
