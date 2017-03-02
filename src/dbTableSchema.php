<?php
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2017
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\pxdb;


abstract class dbTableSchema extends dbTable {



	public function __construct($pool, $tableName) {
		parent::__construct($pool, $tableName);
	}



}
