<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 04/04/16
 * Time: 2:23 PM
 */

class mod_project_observer {


    /**
     * Triggered when user sends chat message
     *
     * @param \core\event\base $event
     */

    public static function process_chat_message_event(\mod_chat\event\message_sent $event){
   // public static function process_chat_message_event(\core\event\base $event){
        global $CFG, $USER, $COURSE, $DB;
        require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
        require_once ($CFG->dirroot . "/local/morph/classes/alerts_controller.php");
        require_once($CFG->dirroot."/mod/project/lib.php");
        require_once($CFG->dirroot."/mod/project/classes/event/chat_message_sent.php");
        require_once($CFG->dirroot."/mod/chat/locallib.php");
        $log=new moodle\local\morph\Logger(array("prefix"=>'chat_'));


        $message=$event->get_record_snapshot('chat_messages',$event->objectid);
        $log->debug("MESSAGE RECORDSNAPSHOT:".json_encode($message));
        $chatuser = $DB->get_record('chat_users', array('chatid'=>$message->chatid, 'userid'=>$USER->id, 'groupid'=>$message->groupid));
        $log->debug("CHAT USER:".json_encode($chatuser));



        ///Creating customized event here
        $eventclass='\local_morph\event\chat_message_sent';
        $cm = get_coursemodule_from_instance('chat', $chatuser->chatid, $chatuser->course);
        $params = array(
            'context' => context_module::instance($cm->id),
            'objectid' => $message->id,
            // We set relateduserid, because when triggered from the chat daemon, the event userid is null.
            'relateduserid' => $chatuser->userid,
            'other'=>array(
            )
        );
        $config = get_config('project');
        $log->debug(" CONFIG:".json_encode($config));
         $projectconfig=json_decode(json_encode($config),true);
       // $projectconfig=get_object_wars($config);
        $log->debug("PROJECT CONFIG:".json_encode($projectconfig));
        $log->debug("SENDING NEW CHAT EVENT:".json_encode($params));
        $event = $eventclass::create($params);
            $event->add_morph_record_snapshot('chat_messages', $message);
            $event->add_morph_other_data('messagelength',strlen($message->message));
        $event->add_morph_other_data('config',$projectconfig);
        $event->trigger();

        ///Finished triggering new event


/*

        $msgcount = $DB->get_record_sql("SELECT count(*) as msgcnt FROM `mdl_chat_messages_current` WHERE system = 0 AND groupid = ?", array($chatuser->groupid))->msgcnt;
        $mbrcount = $DB->get_record_sql("SELECT count(*) as mbrcnt FROM `mdl_groups_members` WHERE groupid = ?", array($chatuser->groupid))->mbrcnt;
        if($msgcount > $mbrcount*3){ //check to see if no alert has happened AND theres a little bit of conversation (3 times per # of group members) before analyzing
            //Added by Jeff Kurcz Apr 20, 2015 to check the last time an alert occurred for a specific user
            $lastcheck= $DB->get_record_sql("SELECT * FROM {project_groups_check} gc WHERE gc.group_id=? AND gc.courseid=? ORDER BY gc.lastcheck DESC LIMIT 1",array($chatuser->groupid, $COURSE->id));
         //  $log->debug("LAST CHECK 1:".json_encode($lastcheck));
            $checktime=$lastcheck->lastcheck+30;
                $log->debug("LAST CHECK done:".json_encode($lastcheck)." CURRENT TIME:".time()." is it higher than:".$checktime);
            if(!$lastcheck || time()>($lastcheck->lastcheck+30)){
                checkChatAlerts($chatuser,$COURSE->id);
                if(!$lastcheck){
                    $lastcheckrecord=new stdClass();
                    $lastcheckrecord->courseid=$COURSE->id;
                    $lastcheckrecord->group_id=$chatuser->groupid;
                    $lastcheckrecord->lastcheck=time();
                    $DB->insert_record('project_groups_check',$lastcheckrecord);
                }else{
                    $lastcheck->lastcheck=time();
                    $DB->update_record('project_groups_check',$lastcheck);
                }

            }

        }//end if
*/
    }



}