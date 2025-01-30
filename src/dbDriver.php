<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2025
 * @license AGPLv3+ADD-PXN-V1
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;


enum dbDriver {
	case SQLite;
	case MySQL;

	public static function FromString(string|dbDriver $driver): ?dbDriver {
		if (\is_string($driver)) {
			$drv = \mb_strtolower(\trim($driver));
			return match ($drv) {
				'sqlite' => dbDriver::SQLite,
				'mysql'  => dbDriver::MySQL,
				default => null
			};
		} else {
			return $driver;
		}
		return null;
	}

	public function toString(): string {
		return match ($this) {
			dbDriver::SQLite => 'SQLite',
			dbDriver::MySQL  => 'MySQL',
			default => null
		};
	}

}
