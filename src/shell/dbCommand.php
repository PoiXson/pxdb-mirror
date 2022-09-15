<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2022
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 * /
namespace pxn\pxdb\shell;

use pxn\phpUtils\System;


abstract class dbCommand {

	protected $dry = NULL;



	public function __construct($dry=TRUE) {
		System::RequireShell();
		$this->dry = ($dry !== FALSE);
	}



	public abstract function execute($pool, $table);



	public function isDry($defaultDry=TRUE) {
		if ($this->dry === NULL) {
			return ($defaultDry !== FALSE);
		}
		return ($this->dry === TRUE);
	}
	public function notDry($defaultDry=TRUE) {
		if ($this->dry === NULL) {
			return ($defaultDry === FALSE);
		}
		return ($this->dry === FALSE);
	}



}
*/
