<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 24/03/16
 * Time: 3:39 PM
 */

global $CFG;
require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
require_once($CFG->dirroot."/local/morph/prototypejobslib.php");
//require_once("lib.php");
require_once($CFG->dirroot."/mod/project/lib.php");
class project_jobs extends prototype_jobs{
    function __construct()
    {
        $this->log=new moodle\local\morph\Logger(array('prefix'=>"page_requirements_"));
    }
    function run_on_course_page_view($courseid, $page, $userid){
        global $USER;
        $this->log->debug("RUN ON COURSE PAGE VIEW FOR PROJECT PROTOTYPE:".$courseid);
        $groupid=getGroupID($USER->id, $courseid);
          checkAlerts($USER->id, getGroupID($USER->id, $courseid));
        $this->log->debug("FINISHED CHECKING ALERTS IN PROJECT:".$courseid);
    }
}