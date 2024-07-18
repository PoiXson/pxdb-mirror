<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 * /
namespace pxn\pxdb\shell;

use pxn\phpUtils\utils\SanUtils;
use pxn\phpUtils\utils\ShellUtils;
use pxn\phpUtils\utils\StringUtils;
use pxn\phpUtils\utils\SystemUtils;
use pxn\phpUtils\pxnDefines as xDef;

use pxn\pxdb\dbPool;


final class dbCommands {
	private function __construct() {}



	public static function RunShellCommand(): void {
		SystemUtils::RequireShell();

		// get command argument
		$cmd = ShellUtils::getArg(1);
		$cmd = \mb_strtolower($cmd);
		if (empty($cmd)) {
			self::DisplayHelp();
			ExitNow(xDef::EXIT_CODE_GENERAL);
		}

		// -h or --help
		if (ShellUtils::isHelp()) {
			self::DisplayHelp($cmd);
			ExitNow(xDef::EXIT_CODE_GENERAL);
		}
		$helpMsg = [];

		// is dry run?
		{
			$dry = ShellUtils::getFlagBool('-D', '--dry');
			// default dry run
			if ($dry === null) {
				// commands which modify
				if ($cmd == 'update'
				|| $cmd == 'import') $dry = true;
				else                 $dry = false;
			}
			// confirmed, not dry run
			$confirm = ShellUtils::getFlagBool('--confirm');
			if ($confirm != false)
				$dry = false;
		}

		// commands: list, check, update, import, export

		// --pool/--table flags
		{
			$poolFlag  = ShellUtils::getFlag('-p', '--pool');
			$tableFlag = ShellUtils::getFlag('-t', '--table');
			if (!empty($poolFlag) || !empty($tableFlag)) {
				$poolFlag  = self::ValidatePoolTableArg($poolFlag);
				$tableFlag = self::ValidatePoolTableArg($tableFlag);
				if (empty($poolFlag))  throw new \Exception('Invalid pool name provided!');
				if (empty($tableFlag)) throw new \Exception('Invalid table name provided!');
				// perform the command
				$result = self::_doRunCommand(
					$cmd,
					$poolFlag,
					$tableFlag,
					$dry
				);
				echo "\n";
				return ($result !== false);
			}
			unset ($poolFlag, $tableFlag);
		}

		// pool:table arguments
		{
			$args = ShellUtils::getArgs();
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
					if ($result === false) {
						echo "\n";
						return false;
					}
					$count++;
				}
				if ($count > 0) {
					echo "\n";
					return true;
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
			return ($result !== false);
		}

		// command not handled
		self::DisplayHelp($cmd, $helpMsg);
		ExitNow(xDef::EXIT_CODE_INVALID_COMMAND);
	}
	private static function _doRunCommand(string $cmd, dbPool $pool, string $table, bool $dry=true): bool {
		$dry = ($dry !== false);
		if ($dry) echo ShellUtils::FormatString(" {color=orange}[Dry Mode]{reset} \n");

		// all pools and tables
		if ($pool == '*' && $table == '*') {
			echo ShellUtils::FormatString(
				" Cmd: {color=green}$cmd{reset}  Pool: {color=green}-all-{reset}  Table: {color=green}-all-{reset}\n\n"
			);
			$pools = dbPool::getPools();
			if ($pools === null || count($pools) == 0)
				throw new \Exception('No database pools configured!');
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
					if ($result === false) {
						$plural = ($count == 1 ? '' : 's');
						echo ShellUtils::FormatString(
							" {color=red}Ran $cmd on {color=green}$count{color=red} table{$plural}, then failed!{reset}\n"
						);
						if ($dry) {
							echo ShellUtils::FormatString(
								" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
							);
						}
						return false;
					}
					$count++;
				}
			}
			$plural = ($count == 1 ? '' : 's');
			echo ShellUtils::FormatString(
				" Ran {color=green}$cmd{reset} on {color=green}$count{reset} table{$plural}\n"
			);
			if ($dry) {
				echo ShellUtils::FormatString(
					" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
				);
			}
			return true;
		}

		// all pools
		if ($pool == '*') {
			echo ShellUtils::FormatString(
				" Cmd: {color=green}$cmd{reset}  Pool: {color=green}-all-{reset}  Table: {color=green}$table{reset}\n\n"
			);
			$pools = dbPool::getPools();
			if ($pools === null || count($pools) == 0)
				throw new \Exception('No database pools configured!');
			$count = 0;
			foreach ($pools as $poolEntryName => $poolEntry) {
				if (!$poolEntry->hasExistingTable($table))
					continue;
				$result = self::_doRunCommandOnce(
					$cmd,
					$poolEntry,
					$table,
					$dry
				);
				if ($result === false) {
					$plural = ($count == 1 ? '' : 's');
					echo ShellUtils::FormatString(
						" {color=red}Ran $cmd on {color=green}$count{color=red} table{$plural}, then failed!{reset}\n"
					);
					if ($dry) {
						echo ShellUtils::FormatString(
							" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
						);
					}
					return false;
				}
				$count++;
			}
			if ($count == 0) throw new \Exception('Table not found: '.$pool.':'.$table);
			$plural = ($count == 1 ? '' : 's');
			echo ShellUtils::FormatString(
				" Ran {color=green}$cmd{reset} on {color=green}$count{reset} table{$plural}\n"
			);
			if ($dry) {
				echo ShellUtils::FormatString(
					" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
				);
			}
			return true;
		}

		// all tables
		if ($table == '*') {
			$poolName = dbPool::castPoolName($pool);
			echo ShellUtils::FormatString(
				" Cmd: {color=green}$cmd{reset}  Pool: {color=green}$poolName{reset}  Table: {color=green}-all-{reset}\n\n"
			);
			$poolEntry = dbPool::getPool($pool);
			if ($poolEntry == null) throw new \Exception('Invalid pool!');
			$tables = $poolEntry->getSchemaTables();
			$count = 0;
			foreach ($tables as $tableEntryName => $tableEntry) {
				$result = self::_doRunCommandOnce(
					$cmd,
					$poolEntry,
					$tableEntryName,
					$dry
				);
				if ($result === false) {
					$plural = ($count == 1 ? '' : 's');
					echo ShellUtils::FormatString(
						" Ran {color=green}$cmd{reset} on {color=green}$count{reset} table{$plural}\n"
					);
					if ($dry) {
						echo ShellUtils::FormatString(
							"{color=orange} [ Dry Mode - No changes made ]{reset}\n"
						);
					}
					return false;
				}
				$count++;
			}
			$plural = ($count == 1 ? '' : 's');
			echo ShellUtils::FormatString(
				" Ran {color=green}$cmd{reset} on {color=green}$count{reset} table{$plural}\n"
			);
			if ($dry) {
				echo ShellUtils::FormatString(
					" {color=orange}[ Dry Mode - No changes made ]{reset}\n"
				);
			}
			return true;
		}

		// one pool/table
		$poolName = dbPool::castPoolName($pool);
		echo ShellUtils::FormatString(
			" Cmd: {color=green}$cmd{reset}  Pool: {color=green}$poolName{reset}  Table: {color=green}$table{reset}\n\n"
		);
		return self::_doRunCommandOnce(
			$cmd,
			$pool,
			$table,
			$dry
		);
	}
	private static function _doRunCommandOnce(string $cmd, dbPool $pool, string $table, bool $dry=true): bool {
		$dry = ($dry !== false);
		$poolName = dbPool::castPoolName($pool);
		$pool = dbPool::getPool($pool);
		if ($pool == null) throw new \Exception('Failed to find db pool: '.$poolName);
		if (StringUtils::StartsWith($table, '_'))
			throw new \Exception('Table cannot start with _ underscore: '.$poolName.':'.$table);
		$cmdObj = null;
		switch ($cmd) {
		// list pools/tables
		case 'list':
			$cmdObj = new dbCommand_List($dry);
			if (ShellUtils::getFlagBool('-f', '--show-fields'))
				$cmdObj->flagShowFields = true;
			else $cmdObj->flagShowFields = false;
			$cmdObj->flagCheckFields = false;
			break;
		// check for needed updates
		case 'check':
			$cmdObj = new dbCommand_Check($dry);
			if (ShellUtils::getFlagBool('-F', '--no-fields'))
				$cmdObj->flagShowFields = false;
			else $cmdObj->flagShowFields = true;
			$cmdObj->flagCheckFields = true;
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
			ExitNow(xDef::EXIT_CODE_INVALID_COMMAND);
		}
		$result = $cmdObj->execute($pool, $table);
		return $result;
	}



	private static function ValidatePoolTableArg(string $arg): string {
		if (empty($arg))
			return '';
		if ($arg == '*' || \mb_strtolower($arg) == 'all')
			return '*';
		return SanUtils::AlphaNumUnderscore($arg);
	}
	private static function SplitPoolTable(array $args): array {
		$entries = [];
		foreach ($args as $arg) {
			$poolName  = null;
			$tableName = null;
			// split pool:table
			if (\strpos($arg, ':') === false) {
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
				$tableName = SanUtils::AlphaNumUnderscore($tableName);
				if (empty($tableName)) throw new \Exception('Invalid table name provided!');
			}
			// parse pool name
			if (empty($poolName) || $poolName == '*' || \mb_strtolower($poolName) == 'all') {
				$poolName = '*';
			} else {
				$poolName = SanUtils::AlphaNumUnderscore($poolName);
				if (empty($poolName))
					throw new \Exception('Invalid pool name provided!');
			}
			// build entry
			$entries["$poolName:$tableName"] = [
				'pool'  => $poolName,
				'table' => $tableName
			];
		}
		return $entries;
	}



	public static function DisplayHelp(?string $cmd=null, array $helpMsg=[]): void {
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
