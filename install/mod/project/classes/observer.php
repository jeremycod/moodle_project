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


    }
    public static function process_course_module_created(\core\event\course_module_created $event){
        global $CFG;
        require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
        require_once($CFG->dirroot."/mod/project/projectadminlib.php");
        $log=new moodle\local\morph\Logger(array("prefix"=>'project_'));
        $log->debug("OBSERVED COURSE MODULE CREATED EVENT: data:".json_encode($event->get_data()));
        $eventdata=$event->get_data();
        if($eventdata['other']['modulename']==='project'){
            $log->debug("CREATED NEW PROJECT");
            handle_new_project_created_event($event);
        }
    }
    public static function process_course_module_deleted(\core\event\course_module_deleted $event){
        global $CFG,$DB;
        require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
        require_once($CFG->dirroot."/mod/project/projectadminlib.php");
        $log=new moodle\local\morph\Logger(array("prefix"=>'project_'));





        $log->debug("OBSERVED COURSE MODULE CREATED DELETED");
        $eventdata=$event->get_data();
        if($eventdata['other']['modulename']==='project'){
            $other=$eventdata["other"];
            $projectid=$other['instanceid'];
            if($project_tools=$DB->get_record('project_tools',array('project_id'=>$projectid))){
                $chat_cm=get_coursemodule_from_instance("chat",$project_tools->chat_id);
                $DB->delete_records('chat', array('id'=>$chat_cm->instance));
                $DB->delete_records('course_modules', array('id'=>$chat_cm->id));
                $forum_cm=get_coursemodule_from_instance("forum",$project_tools->forum_id);
                $DB->delete_records('forum', array('id'=>$forum_cm->instance));
                $DB->delete_records('course_modules', array('id'=>$forum_cm->id));
            }

            $log->debug("DELETED PROJECT");
        }
    }


}