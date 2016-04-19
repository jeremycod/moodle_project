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
        require_once($CFG->dirroot."/mod/chat/locallib.php");
        $log=new moodle\local\morph\Logger(array("prefix"=>'chat_'));


        $message=$event->get_record_snapshot('chat_messages',$event->objectid);
        $log->debug("MESSAGE RECORDSNAPSHOT:".json_encode($message));
        $chatuser = $DB->get_record('chat_users', array('chatid'=>$message->chatid, 'userid'=>$USER->id, 'groupid'=>$message->groupid));
        $log->debug("CHAT USER:".json_encode($chatuser));
        $alerts_controller=new alerts_controller();
      //  $currentuser=$DB->get_record('user',array('id'=>$USER->id));
      //  $content = get_string('alert-low', 'mod_project', $currentuser->firstname);
      //  $header="TEST SYSTEM ALERT!";
      //  $log->debug("SENDING TEST MESSAGE:".json_encode($content));
       // $alerts_controller->create_top_panel_notification_alert($USER->id, $COURSE->id,0,"error", $header, $content);

        $config = get_config('project');

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
            /*
            $lastalert = $DB->get_record_sql('SELECT substring(message,7,3) as alert, timestamp FROM `mdl_chat_messages_current` WHERE userid = ? AND system = 1 AND message LIKE ? ORDER BY timestamp DESC LIMIT 1', array($message->userid, 'alert%'));
            if($lastalert->alert=='low')
                $alert_type = 'lowchatalertsfreq';
            else
                $alert_type = 'highchatalertsfreq';
                     $wait_until = ($lastalert->timestamp+($config->$alert_type*60));

            if(time() > $wait_until || !isset($lastalert) ){ //If last alert happened over X minutes, we can check for new alerts.
                checkChatAlerts($chatuser,$COURSE->id);
            }*///end if
        }//end if

    }



}