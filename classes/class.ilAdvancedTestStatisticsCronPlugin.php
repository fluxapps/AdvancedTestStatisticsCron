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
		if(!isset(self::$instance)){
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

    /**
     * @return ilObjCourse
     * @throws Exception
     */
    public function getParentCourse($ref_id = 0) {
        $ref_id = $ref_id ?: $_GET['ref_id'];
        require_once 'Services/Object/classes/class.ilObjectFactory.php';

        return ilObjectFactory::getInstanceByRefId($this->getParentCourseId($ref_id));
    }


    /**
     * @param $ref_id
     *
     * @return int
     * @throws Exception
     */
    public function getParentCourseId($ref_id) {
        global $tree;
        require_once 'Services/Object/classes/class.ilObject2.php';
        while (!in_array(ilObject2::_lookupType($ref_id, true), array( 'crs', 'grp' ))) {
            if ($ref_id == 1 || !$ref_id) {
                throw new Exception("Parent of ref id {$ref_id} is neither course nor group.");
            }
            $ref_id = $tree->getParentId($ref_id);
        }

        return $ref_id;
    }
}
