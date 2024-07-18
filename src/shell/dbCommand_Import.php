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



class dbCommand_Import extends dbCommand {



	public function __construct(bool $dry=true) {
		parent::__construct($dry);
	}



	// returns true if successful
	public function execute(dbPool $pool, string $tableName): void {
		$dryStr     = ($this->dry ? '{color=orange}[DRY]{reset} ' : '');
		$pool       = dbPool::getPool($pool);
		$poolName   = $pool->getName();
		$existTable = $pool->getExistingTable($tableName);
//TODO: fix file name here
$path = '/run/media/lop/usb16/wwww/gc-website/';
$filename = 'testfile.txt';
$filepath = "{$path}{$filename}";
		echo ShellUtils::FormatString(
			"{$dryStr}Importing Table: {color=green}{$poolName}:{$tableName}{reset}\n".
			"{$dryStr}From file: {$filepath}\n"
		);
	}



}
*/
