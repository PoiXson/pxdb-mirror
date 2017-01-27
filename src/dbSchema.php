<?php
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2017
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\pxdb;


abstract class dbSchema {

	protected $fields = NULL;



	public function __construct() {
		$this->fields = $this->initFields();
	}
	public abstract function initFields();



	public function getFields() {
		return $this->fields;
	}



}
