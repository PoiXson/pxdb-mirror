<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2025
 * @license AGPLv3+ADD-PXN-V1
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb;


enum dbFieldType {
	case TYPE_BOOL;
	case TYPE_INT;
	case TYPE_STR;
	case TYPE_TEXT;

	public static function FromString(string|dbFieldType $type): ?dbFieldType {
		if (\is_string($type)) {
			$drv = \mb_strtolower(\trim($type));
			return match ($drv) {
				'type_bool', 'bool', 'boolean' => dbFieldType::TYPE_BOOL,
				'type_int',  'int',  'integer' => dbFieldType::TYPE_INT,
				'type_str',  'str',  'string'  => dbFieldType::TYPE_STR,
				'type_text',                   => dbFieldType::TYPE_TEXT,
				default => null
			};
		} else {
			return $type;
		}
		return null;
	}

	public function toString(): string {
		return match ($this) {
			dbFieldType::TYPE_BOOL => 'bool',
			dbFieldType::TYPE_INT  => 'int',
			dbFieldType::TYPE_STR  => 'str',
			dbFieldType::TYPE_TEXT => 'text',
			default => null
		};
	}

}
