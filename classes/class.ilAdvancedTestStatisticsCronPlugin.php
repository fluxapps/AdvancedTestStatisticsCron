<?php

require_once './Services/Cron/classes/class.ilCronHookPlugin.php';
require_once './Customizing/global/plugins/Services/Cron/CronHook/AdvancedTestStatisticsCron/classes/class.ilAdvancedTestStatisticsCron.php';


/**
 * Class ilAdvancedTestStatisticsCronPlugin
 @author Silas Stulz <sst@studer-raimann.ch>
 */
class ilAdvancedTestStatisticsCronPlugin extends ilCronHookPlugin {

	protected static $instance;


	public static function getInstance(){
		if(isset(self::$instance)){
			self::$instance = new self();
		}
		return self::$instance;
	}




	public function getPluginName() {
		return 'AdvancedTestStatisticsCron';
	}


	public function getCronJobInstances() {
		$job = new ilAdvancedTestStatisticsCron();
		return array ($job);
	}


	public function getCronJobInstance($a_job_id) {
return new ilAdvancedTestStatisticsCron();
	}
}