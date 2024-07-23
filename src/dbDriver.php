<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;


enum dbDriver {
	case MySQL;
	case sqLite;

	public static function FromString(string $driver): ?self {
		$driver = \mb_strtolower(\trim($driver));
		switch ($driver) {
			case 'sqlite': return self::sqLite;
			case 'mysql':  return self::MySQL;
			default: break;
		}
		return null;
	}

	public function toString(): string {
		switch ($this) {
			case self::MySQL:  return 'MySQL';
			case self::sqLite: return 'sqLite';
			default: throw new \RuntimeException('Unknown database driver type');
		}
	}

}
