<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 * /
namespace pxn\pxdb\shell;

use \pxn\phpUtils\utils\SystemUtils;


abstract class dbCommand {

	protected ?bool $dry = null;



	public function __construct(bool $dry=true) {
		SystemUtils::RequireShell();
		$this->dry = ($dry !== false);
	}



	public abstract function execute(dbPool $pool, string $table): string;



	public function isDry(bool $defaultDry=true): bool {
		if ($this->dry === null)
			return ($defaultDry !== false);
		return ($this->dry === true);
	}
	public function notDry(bool $defaultDry=true): bool {
		if ($this->dry === null)
			return ($defaultDry === false);
		return ($this->dry === false);
	}



}
*/
