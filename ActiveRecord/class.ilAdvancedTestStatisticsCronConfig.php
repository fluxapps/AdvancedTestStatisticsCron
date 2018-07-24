<?php

class ilAdvancedTestStatisticsCronConfig extends ActiveRecord {

	const TABLE_NAME = 'xatc_conf';


	public static function returnDbTableName() {
		return self::TABLE_NAME;
	}
}