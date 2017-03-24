<?php
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2017
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\pxdb\shell;


class dbCommand_Import extends dbCommand {



	public function __construct($dry=TRUE) {
		parent::__construct($dry);
	}



	// returns true if successful
	public function execute($pool, $tableName) {
		$dryStr = ($this->dry ? '[DRY] ' : '');
		$pool = dbPool::getPool($pool);
		$poolName = $pool->getName();
		$existTable = $pool->getExistingTable($tableName);
		echo "\n{$dryStr}Importing Table: {$poolName}:{$tableName}\n";
	}



}
