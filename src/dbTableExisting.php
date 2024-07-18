<?php declare(strict_types = 1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 * /
namespace pxn\pxdb;

use pxn\phpUtils\Strings;
use pxn\phpUtils\Defines;


class dbTableExisting extends dbTable {



	public function initFields(): int {
		$this->inited = true;
		$tableName = $this->tableName;
		if (Strings::StartsWith($this->tableName, '_'))
			throw new \Exception('Table name cannot start with _ underscore: '.$this->pool->getPoolName().':'.$this->tableName);
		// load fields in table
		$db = $this->pool->get();
		$db->Execute(
			"DESCRIBE `__TABLE__{$tableName}`;",
			'LoadTableFields()'
		);
		$this->fields = [];
		while ($db->hasNext()) {
			$row = $db->getRow();
			// field name
			$fieldName = $db->getString('Field');
			if (Strings::StartsWith($fieldName, '_'))
				continue;
			// type
			$fieldType = $db->getString('Type');
			// size
			$fieldSize = null;
			$pos = \mb_strpos($fieldType, '(');
			if ($pos !== false) {
				$fieldSize = Strings::Trim(\mb_substr($fieldType, $pos), '(', ')');
				$fieldType = \mb_substr($fieldType, 0, $pos);
			}
			// new field object
			$field = new dbField($fieldName, $fieldType, $fieldSize);
			// null / not null
			$nullable = (\mb_strtoupper( $db->getString('Null') ) == 'YES');
			if ($nullable)
				$field->setNullable(true);
			// default value
			if (\array_key_exists('Default', $row)) {
				$default = ($row['Default'] === null ? null : $db->getString('Default'));
				$field->setDefault($default);
			}
			// primary key
			if (\mb_strtoupper( $db->getString('Key') ) == 'PRI')
				$field->setPrimaryKey(true);
			// auto increment
			$extra = $db->getString('Extra');
			if (\mb_strpos(\mb_strtolower($extra), 'auto_increment') !== false)
				$field->setAutoIncrement(true);
			$this->fields[$fieldName] = $field;
		}
		$db->release();
		return \count($this->fields);
	}



}
*/
