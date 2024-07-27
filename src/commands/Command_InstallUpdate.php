<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2024
 * @license AGPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb\commands;

use \pxn\pxdb\dbTools;


class Command_InstallUpdate extends \pxn\phpShell\Command {



	public function run(): int {
		if (!\is_callable([$this->app, 'getDatabaseSchema']))
			throw new \RuntimeException('function getDatabaseSchema() not found in class: '.\get_class($this->app));
		$schema = \call_user_func([$this->app, 'getDatabaseSchema']);
		$pool = $this->app->getUsersDB();
		$tables_found = dbTools::GetTables($pool);
		$count_added_tables      = 0;
		$count_add_change_fields = 0;
		foreach ($schema as $table) {
			$table_name = $table->getTableName();
			// create table
			if (!\in_array(haystack: $tables_found, needle: $table_name)) {
				dbTools::CreateTable($pool, $table);
				$count_added_tables++;
			}
			// add/update fields
			$count_add_change_fields += dbTools::UpdateTableFields($pool, $table);
		}
		if ($count_added_tables > 0)
			echo "Added [ $count_added_tables ] tables\n";
		if ($count_add_change_fields > 0)
			echo "Added/Updated [ $count_add_change_fields ] fields\n";
		echo "Finished\n";
		return 0;
	}



}
