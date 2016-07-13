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
require_once($CFG->dirroot."/mod/project/classes/event/project_course_page_viewed.php");
class project_jobs extends prototype_jobs{
    function __construct()
    {
        $this->log=new moodle\local\morph\Logger(array('prefix'=>"page_requirements_"));
    }
    function run_on_course_page_view($courseid, $page, $userid){
        global $USER;
        $this->log->debug("RUN ON COURSE PAGE VIEW FOR PROJECT PROTOTYPE:".$courseid);

        $groupid=getGroupID($USER->id, $courseid);


        ///Creating customized event here
        $eventclass='\local_morph\event\project_course_page_viewed';
       // $cm = get_coursemodule_from_instance('chat', $chatuser->chatid, $chatuser->course);
        $params = array(
            'context' => context_course::instance($courseid),
             'objectid' => $courseid,
            // We set relateduserid, because when triggered from the chat daemon, the event userid is null.

            'relateduserid' => $userid,
            'other'=>array(
            )
        );
        $config = get_config('project');
        $this->log->debug(" CONFIG:".json_encode($config));
        $projectconfig=json_decode(json_encode($config),true);
        // $projectconfig=get_object_wars($config);
        $this->log->debug("PROJECT CONFIG:".json_encode($projectconfig));
        $this->log->debug("SENDING NEW CHAT EVENT:".json_encode($params));
        $event = $eventclass::create($params);
        //$event->add_morph_record_snapshot('chat_messages', $message);
       // $event->add_morph_other_data('messagelength',strlen($message->message));
        $groupid=getGroupID($USER->id, $courseid);
        $event->add_morph_other_data('config',$projectconfig);
        $event->add_morph_other_data('groupid',$groupid);
        $event->trigger();

        ///Finished triggering new event

          checkAlerts($USER->id, getGroupID($USER->id, $courseid));
        $this->log->debug("FINISHED CHECKING ALERTS IN PROJECT:".$courseid);
    }
}