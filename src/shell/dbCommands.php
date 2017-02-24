<?php
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2017
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\pxdb\shell;

use pxn\phpUtils\San;
use pxn\phpUtils\ShellTools;
use pxn\phpUtils\Strings;
use pxn\phpUtils\System;
use pxn\phpUtils\Defines;

use pxn\pxdb\dbPool;


abstract class dbCommands {

	protected $dry = NULL;



	public function __construct($dry) {
		System::RequireShell();
		$this->dry = $dry;
	}



	public abstract function execute($pool, $table);



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
				return ($result !== FALSE);
			}
			unset ($poolFlag, $tableFlag);
		}

		// pool:table arguments
		{
			$args = ShellTools::getArgs();
			\array_shift($args);
			\array_shift($args);
			if (\count($args) > 0) {
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
						return FALSE;
					}
					$count++;
				}
				if ($count > 0) {
					return TRUE;
				}
			}
		}

		// no argument (default to all pools/tables)
		if ($cmd == 'list' || $cmd == 'check') {
			$result = self::_doRunCommand(
				$cmd,
				'*',
				'*',
				$dry
			);
			return ($result !== FALSE);
		}

		// command not handled
		self::DisplayHelp($cmd);
		ExitNow(Defines::EXIT_CODE_INVALID_COMMAND);
	}
	private static function _doRunCommand($cmd, $pool, $table, $dry) {
		if ($dry) {
			echo " [Dry Mode] \n";
		}

		// all pools and tables
		if ($pool == '*' && $table == '*') {
			echo " Cmd: $cmd  Pool: -all-  Table: -all-\n\n";
			$pools = dbPool::getPools();
			if ($pools === NULL || count($pools) == 0) {
				fail('No database pools configured!',
					Defines::EXIT_CODE_CONFIG_ERROR);
			}
			$count = 0;
			foreach ($pools as $poolEntryName => $poolEntry) {
				$tables = $poolEntry->getTableSchemas();
				foreach ($tables as $tableEntryName => $tableEntry) {
					$result = self::_doRunCommandOnce(
						$cmd,
						$poolEntry,
						$tableEntryName,
						$dry
					);
					if ($result === FALSE) {
						echo "\n Ran $cmd on $count tables, then failed!";
						return FALSE;
					}
					$count++;
				}
			}
			echo "\n Ran $cmd on $count tables";
			return TRUE;
		}

		// all pools
		if ($pool == '*') {
			echo " Cmd: $cmd  Pool: -all-  Table: $table\n\n";
			$pools = dbPool::getPools();
			if ($pools === NULL || count($pools) == 0) {
				fail('No database pools configured!',
					Defines::EXIT_CODE_CONFIG_ERROR);
			}
			$count = 0;
			foreach ($pools as $poolEntryName => $poolEntry) {
				if ($poolEntry->hasTable($table)) {
					$result = self::_doRunCommandOnce(
						$cmd,
						$poolEntry,
						$table,
						$dry
					);
					if ($result === FALSE) {
						echo "\n Ran $cmd on $count tables, then failed!";
						return FALSE;
					}
					$count++;
					continue;
				}
			}
			echo "\n Ran $cmd on $count tables";
			return TRUE;
		}

		// all tables
		if ($table == '*') {
			$poolName = dbPool::castPoolName($pool);
			echo " Cmd: $cmd  Pool: $poolName  Table: -all-\n\n";
			$poolEntry = dbPool::getPool($pool);
			if ($poolEntry == NULL) {
				fail('Invalid pool!',
					Defines::EXIT_CODE_INVALID_ARGUMENT);
			}
			$tables = $poolEntry->getUsingTables();
			$count = 0;
			foreach ($tables as $tableEntryName => $tableEntry) {
				$result = self::_doRunCommandOnce(
					$cmd,
					$poolEntry,
					$tableEntryName,
					$dry
				);
				if ($result === FALSE) {
					echo "\n Ran $cmd on $count tables";
					return FALSE;
				}
				$count++;
			}
			echo "\n Ran $cmd on $count tables";
			return TRUE;
		}

		// one pool/table
		$poolName = dbPool::castPoolName($pool);
		echo " Cmd: $cmd  Pool: $poolName  Table: $table\n\n";
		$result = self::_doRunCommandOnce(
			$cmd,
			$pool,
			$table,
			$dry
		);

		return $result;
	}
	private static function _doRunCommandOnce($cmd, $pool, $table, $dry) {
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
			$cmdObj = new dbCommand_ListCheck($dry);
			if (ShellTools::getFlagBool('-f', '--show-fields')) {
				$cmdObj->flagShowFields = TRUE;
			} else {
				$cmdObj->flagShowFields = FALSE;
			}
			$cmdObj->flagCheckFields = FALSE;
			break;
		// check for needed updates
		case 'check':
			$cmdObj = new dbCommand_ListCheck($dry);
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
			break;
		// export db tables
		case 'export':
			$cmdObj = new dbCommand_Export($dry);
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
			// default pool name
			if (empty($poolName)) {
				if ($tableName == '*') {
					$poolName = '*';
				} else {
					$poolName = dbPool::dbNameDefault;
				}
			}
			// parse pool name
			if ($poolName == '*' || \mb_strtolower($poolName) == 'all') {
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



	public static function DisplayHelp($cmd=NULL) {
		echo "\n";
		echo "Usage:\n";
		switch ($cmd) {
		case 'list':
			echo "  db list [options]\n";
			break;
		case 'check':
			echo "  db check [options] [[pool:]table] ..\n";
			break;
		case 'update':
			echo "  db update [options] [[pool:]table] ..\n";
			break;
		case 'import':
			echo "  db import [options] --file <filename> [[pool:]table] ..\n";
			break;
		case 'export':
			echo "  db export [options] --file <filename> [[pool:]table] ..\n";
			break;
		default:
			echo "  db <command> [options]\n";
			break;
		}
		echo "\n";
		echo "Commands:\n";
		echo "  list    List the existing database pools/tables\n";
		echo "  check   Check for needed updates to table schemas.\n";
		echo "  update  Update the database tables to the current schema, and create tables as needed.\n";
		echo "  import  Import data from a stored backup.\n";
		echo "  export  Export data to a backup stored in the filesystem.\n";
		echo "\n";
		echo "Options:\n";
		echo "  -D, --dry    Run the operation without making changes. (default for some operations)\n";
		if ($cmd == 'check' || $cmd == 'update' || $cmd == 'import') {
			echo "  --confirm    Confirm the changes to be made (overrides the --dry flag)\n";
		}
		if ($cmd == 'list' || $cmd == 'check') {
			echo "\n";
			echo "  -f, --show-fields  List fields for tables\n";
			echo "  -F, --no-fields    Don't list fields in tables\n";
		}
		echo "\n";
		echo "  -p, --pool   Database pool name to use for the operation.\n";
		echo "  -t, --table  Name of the table to use for the operation.\n";
		if ($cmd == 'import') {
			echo "\n";
			echo "  -f, --file   The filename or path to restore data from.\n";
		}
		if ($cmd == 'export') {
			echo "  -f, --file   The filename or path to write the exported data.\n";
		}
		echo "\n";
	}



}
