<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 22/03/16
 * Time: 2:37 PM
 */
global $CFG;
require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
require_once($CFG->dirroot."/local/morph/cronlib.php");
require_once($CFG->dirroot .'/mod/project/lib.php');
require_once($CFG->libdir.'/moodlelib.php');
class project_course_cron_job extends system_cron_job{
    private $log;
    function __construct()
    {
       // parent::__construct();
        $this->log=new moodle\local\morph\Logger(array('prefix'=>"cron_"));
        $this->log->debug("project_course_cron_job constructor");
    }

    function run_cron(){

        $this->log->debug("Running course cron job for Project prototype in course:");

        populate_completed_groups_cron();

        $this->log->debug("Finished cron job for Project prototype in course:");
    }
}