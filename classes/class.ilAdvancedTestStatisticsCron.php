<?php

require_once './Services/Cron/classes/class.ilCronJob.php';
require_once './Customizing/global/plugins/Services/Cron/CronHook/AdvancedTestStatisticsCron/classes/class.ilAdvancedTestStatisticsCronPlugin.php';
require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/AdvancedTestStatistics/classes/class.ilAdvancedTestStatisticsPlugin.php';
require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/AdvancedQuestionPoolStatistics/classes/class.ilAdvancedQuestionPoolStatisticsPlugin.php';

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


    /**
     * ilAdvancedTestStatisticsCron constructor.
     */
    public function __construct() {
		global $tree;
		$this->tree = $tree;
		$this->pl = ilAdvancedTestStatisticsCronPlugin::getInstance();
	}


    /**
     * @return string
     */
    public function getTitle() {
		return "Cronjob for triggerfunctions of the statistics plugin";
	}


    /**
     * @return string
     */
    public function getDescription() {
		return "Checks if triggerfunctions are statisfied";
	}


    /**
     * @return string
     */
    public function getId() {
		return self::CRON_ID;
	}


    /**
     * @return bool
     */
    public function hasAutoActivation() {
		return true;
	}


    /**
     * @return bool
     */
    public function hasFlexibleSchedule() {
		return false;
	}


    /**
     * @return int
     */
    public function getDefaultScheduleType() {
		return self::SCHEDULE_TYPE_DAILY;
	}


    /**
     * @return array|int
     */
    public function getDefaultScheduleValue() {
		return 1;
	}


    /**
     * @return ilCronJobResult
     */
    public function run() {
        $triggers = array_merge(xatsTriggers::get(), xaqsTriggers::get());

        foreach ($triggers as $trigger) {
            $this->checkDate($trigger)
            && $this->checkInterval($trigger)
            && $this->checkPrecondition($trigger)
            && $this->checkTrigger($trigger);
        }

        $this->result = new ilCronJobResult();
        $this->result->setStatus(ilCronJobResult::STATUS_OK);

        return $this->result;
    }


    /**
     * @param $trigger
     * @return bool
     */
    public function checkDate($trigger) {
		if ($trigger->getDatesender() > date('U')) {
			return false;
		}

		return true;
	}


    /**
     * @param xatsTriggers|xaqsTriggers $trigger
     * @return bool
     * @throws Exception
     */
    public function checkPrecondition($trigger) {
		if ($trigger instanceof xatsTriggers) { // question pool triggers are checked later, since every question has to be checked
		    if (!ilObject::_exists($trigger->getRefId(), true)) {
		        $trigger->delete();
		        return false;
            }
		    try {
                $class = new ilAdvancedTestStatisticsAggResults($trigger->getRefId());
            } catch (Exception $e) {
		        return false;
            }
            $finishedtests = $class->getTotalFinishedTests($trigger->getRefId());
            // Check if enough people finished the test
            if ($finishedtests < $trigger->getUserThreshold()) {
                return false;
            }
        }

		return true;
	}


    /**
     * @param $trigger
     * @return bool
     */
    public function checkInterval($trigger) {
		$interval = $trigger->getIntervalls();
		$lastrun = $trigger->getLastRun();

		switch ($interval) {
			case 0:
			    if ($lastrun + 86400 <= date('U')) {
			        return true;
                }
				return false;
			case 1:
				if ($lastrun + 604800 <= date('U')) {
					return true;
				}
				return false;
			case 2:
				if ($lastrun + 2629743 <= date('U')) {
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
		$triggername = $trigger->getTriggerName();
		$trigger_value = $trigger->getValue();
        $values_reached = $trigger instanceof xatsTriggers ? ilAdvancedTestStatisticsConstantTranslator::getValues($trigger) : ilAdvancedQuestionPoolStatisticsConstantTranslator::getValues($trigger);
        $operator = $trigger->getOperatorFormatted();
        $trigger_values = '';

        switch ($triggername) {
            case 'qst_percentage':
                $trigger_values .= "\n";
                foreach ($values_reached as $qst_id => $value_reached) {
                    if (!eval('return ' . $value_reached . ' ' . $operator . ' ' . $trigger_value . ';')) {
                        unset($values_reached[$qst_id]);
                    } else {
                        $trigger_values .= '"' . assQuestion::_instanciateQuestion($qst_id)->getTitle() . '"' . ': ';
                        $trigger_values .= $value_reached . "\n";
                    }
                }

                if (empty($values_reached)) {
                    return false;
                }
                break;
            default:
                if (!eval('return ' . $values_reached . ' ' . $operator . ' ' . $trigger_value . ';')) {
                    return false;
                }
                $trigger_values = $values_reached;
                break;
        }

        try {
            $this->ref_id_course = $this->pl->getParentCourseId($trigger->getRefId());
        } catch (Exception $e) {
            $this->ref_id_course = 0;
        }
		$this->usr_ids = ilCourseMembers::getData($this->ref_id_course);

        if ($trigger->getUserId()) {
            if ($trigger instanceof xatsTriggers) {
                $sender =  new ilAdvancedTestStatisticsSender();
                $sender->createNotification($this->ref_id_course, $trigger, $trigger_values);
            } else {
                $sender = new ilAdvancedQuestionPoolStatisticsSender();
                $sender->createNotification($this->ref_id_course, 0, $trigger->getRefId(), $trigger, $trigger_values);
            }
        }
        $trigger->setLastRun(date('U'));
        $trigger->save();
	}
}
