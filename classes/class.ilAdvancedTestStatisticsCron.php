<?php

require_once './Services/Cron/classes/class.ilCronJob.php';
require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/AdvancedTestStatistics/ActiveRecords/xatsTriggers.php';

class ilAdvancedTestStatisticsCron extends ilCronJob {

	const CRON_ID = 'xatc';
	/**
	 * @var ilAdvancedTestStatisticsCronPlugin
	 */
	protected $pl;
	/**
	 * @var ilCronJobResult
	 */
	protected $result;
	/**
	 * @var
	 */
	protected $config;
	/**
	 * @var int
	 */
	protected $ref_id_course;
	/**
	 * @var ilTree
	 */
	protected $tree;
	/**
	 * @var array
	 */
	protected $usr_ids;


	public function __construct() {
		global $tree;
		$this->tree = $tree;
	}


	public function getTitle() {
		return "Cronjob for triggerfunctions of the statistics plugin";
	}


	public function getDescription() {
		return "Checks if triggerfunctions are statisfied";
	}


	public function getId() {
		return self::CRON_ID;
	}


	public function hasAutoActivation() {
		return true;
	}


	public function hasFlexibleSchedule() {
		return false;
	}


	public function getDefaultScheduleType() {
		return self::SCHEDULE_TYPE_DAILY;
	}


	public function getDefaultScheduleValue() {
		return 1;
	}


	public function run() {
		$triggers = xatsTriggers::get();

		foreach ($triggers as $trigger) {
			if (!$this->checkDate($trigger)) {
				continue;
			}
			if (!$this->checkInterval($trigger)) {
				continue;
			}
			if (!$this->checkTrigger($trigger)) {
				continue;
			}
		}

		$this->result = new ilCronJobResult();
		$this->result->setStatus(ilCronJobResult::STATUS_OK);

		return $this->result;
	}


	public function checkDate($trigger) {
		if ($trigger->getDatesender() > date('U')) {
			return false;
		}

		return true;
	}


	public function checkPrecondition($trigger) {
		$class = new ilAdvancedTestStatisticsAggResults();
		$finishedtests = $class->getTotalFinishedTests($trigger->getRefId());
		$course_members = count($this->usr_ids);

		// Check if enough people finished the test
		if ((100 / $course_members) * $finishedtests < $trigger->getUserPercentage()) {
			return false;
		}
	}


	public function checkInterval($trigger) {
		$interval = $trigger->getIntervalls();
		$lastrun = $trigger->getLastRun();

		switch ($interval) {
			case 0:
				return true;
			case 1:
				if ($lastrun + 604800 >= date('U')) {
					return true;
				}
				return false;
			case 2:
				if ($lastrun + 2629743 >= date('U')) {
					return true;
				}
				return false;
		}
	}

    /**
     * @param $trigger xatsTriggers
     * @return bool
     */
	public function checkTrigger($trigger) {
		$class = new ilAdvancedTestStatisticsAggResults();
		$finishedtests = $class->getTotalFinishedTests($this->ref_id);
		$course_members = count($this->usr_ids);

		// Check if enough people finished the test
		if ((100 / $course_members) * $finishedtests < $trigger->getUserPercentage()) {
			return false;
		}

		$triggername = $trigger->getTriggerName();
		$value = $trigger->getValue();

		//if True trigger is a question
		if (is_int($triggername)) {
			$valuereached = 100;
		} else {
			$valuereached = ilAdvancedTestStatisticsConstantTranslator::getValues($triggername, $this->ref_id);
		}

		$operator = ilAdvancedTestStatisticsConstantTranslator::getOperatorforKey($trigger->getOperator());

		switch ($operator) {
			case '<':
				if ($valuereached < $value) {
					break;
				}

				return false;
			case '>':
				if ($valuereached > $value) {
					break;
				}

				return false;
			case '=':
				if ($valuereached == $value) {
					break;
				}

				return false;
			case '>=':
				if ($valuereached == $value) {
					break;
				}

				return false;
			case '<=':
				if ($valuereached <= $value) {
					break;
				}

				return false;
			case '!=':
				if ($valuereached != $value) {
					break;
				}

				return false;
			default:
				break;
		}

		$this->ref_id_course = $this->tree->getParentId($trigger->getRefId());
		$this->usr_ids = ilCourseMembers::getData($this->ref_id_course);

		$sender = new ilAdvancedTestStatisticsSender();
		try {
			$sender->createNotification($this->ref_id_course, $trigger);
            $trigger->setLastRun(date('U'));
            $trigger->save();
		} catch (Exception $exception) {
			$this->result = new ilCronJobResult();
			$this->result->setStatus(ilCronJobResult::STATUS_CRASHED);
		}
	}
}