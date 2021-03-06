<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 28/10/16
 * Time: 3:27 PM
 */


function handle_new_project_created_event($event){
    global $CFG,$DB;
    require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
    require_once($CFG->dirroot."/mod/project/classes/event/project_tools_created.php");
    $log=new moodle\local\morph\Logger(array("prefix"=>'project_'));
    $eventdata=$event->get_data();
    $other=$eventdata["other"];
    $cm=get_coursemodule_from_instance($other["modulename"],$other["instanceid"]);
    //$section=$DB->get_record("course_sections",array('id'=>$cm->section));


    $coursechanged = true;

    $courseid=$eventdata['courseid'];
    $projectname=$other['name'];
    $projectid=$other['instanceid'];
    $hiddensections=$DB->get_records("course_sections",array('course'=>$courseid,'visible'=>0));
    $log->debug("hidden sections found:".json_encode($hiddensections));
    if(sizeof($hiddensections)==0){
        $sections=$DB->get_records("course_sections",array('course'=>$courseid));
        $latestsectionnum=0;
        foreach($sections as $sect){
            if($latestsectionnum<$sect->section){
                $latestsectionnum=$sect->section;
            }
        }
        $section = new stdClass();
        $section->course   = $courseid;
        $section->name= "Project activities";
        $section->section  = $latestsectionnum+1;
        $section->visible=0;
        $section->summary  = '';
        $section->summaryformat = FORMAT_HTML;
        $section->sequence = '';
        $section->id = $DB->insert_record("course_sections", $section);
        $log->debug("created new invisible section:".json_encode($section));
        $coursechanged = true;
        $courseformat=$DB->get_record("course_format_options",array("courseid"=>$courseid,"name"=>"numsections"));
        $courseformat->value=$courseformat->value+1;
        $log->debug("course format:".json_encode($courseformat));
        $DB->update_record("course_format_options",$courseformat);
        if ($coursechanged) {
            rebuild_course_cache($courseid, true);
        }
    }else{
        $section=array_pop($hiddensections);
        $log->debug("already had hidden sections:".json_encode($section));
    }



    $log->debug("CREATING PROJECT:".$projectname." in section:".$section->section);
    $forum_mod=create_project_forum($courseid,$projectname,$section->section);
    $chat_mod=create_project_chat($courseid,  $projectname, $section->section);
    $log->debug("FORUM MOD:".json_encode($forum_mod));
    $forum=$forum_mod[0];
    $chat=$chat_mod[0];
    $project_tools=new stdClass();
    $project_tools->project_id=$projectid;
    $project_tools->forum_id=$forum->id;
    $project_tools->forum_mod=$forum_mod[1]->coursemodule;
    $project_tools->chat_id=$chat->id;
    $project_tools->chat_mod=$chat_mod[1]->coursemodule;
   $project_tools->id= $DB->insert_record("project_tools", $project_tools);
    $context = context_course::instance($courseid);
    $params = array(
        'context' => $context,
        'objectid' => $project_tools->id,
        'courseid' =>$courseid,
        'other'=> array(

           )
    );
    $event = \local_morph\event\project_tools_created::create($params);
    $event->trigger();


}
function handle_project_deleted_event($event){
    global $CFG,$DB;
    require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
    require_once($CFG->dirroot."/mod/project/classes/event/project_tools_created.php");
    $log=new moodle\local\morph\Logger(array("prefix"=>'project_'));
    $log->debug("HANDLE PROJECT DELETED EVENT");
    $eventdata=$event->get_data();
    $projectid=$eventdata['other']['instanceid'];
    $courseid=$event->courseid;
    $context = context_course::instance($courseid);
    $params = array(
        'context' => $context,
        'objectid' => $projectid,
        'courseid' =>$courseid,
        'other'=> array(
            'delete'=>true
        )
    );
    $event = \local_morph\event\project_tools_created::create($params);
    $event->add_morph_other_data('deleted','true');
    $event->add_morph_other_data('projectid',$projectid);
    $event->trigger();
    $DB->delete_records("project_tools",array("project_id"=>$projectid));
    $DB->delete_records("project_task",array("project_id"=>$projectid));
}
function create_project_forum($courseid,  $projectname, $sectionid) {
// How to set up special 1-per-course forums
    global $CFG, $DB, $OUTPUT, $USER;
    require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
    $log=new moodle\local\morph\Logger(array("prefix"=>'project_'));

    // Doesn't exist, so create one now.
    $forum = new stdClass();
    $forum->course = $courseid;
    $forum->type = "social";
    if (!empty($USER->htmleditor)) {
        $forum->introformat = $USER->htmleditor;
    }
    $forum->name  = "ACS:Forum";
    $forum->intro = "General discussion on project: ".$projectname;
    $forum->assessed = 0;
    $forum->forcesubscribe = 0;

    $forum->timemodified = time();
    $forum->id = $DB->insert_record("forum", $forum);
    $log->debug("Created forum for project:".json_encode($forum));
    if (! $module = $DB->get_record("modules", array("name" => "forum"))) {
        echo $OUTPUT->notification("Could not find forum module!!");
        return false;
    }
    $mod = new stdClass();
    $mod->course = $courseid;
    $mod->module = $module->id;
    $mod->visible=1;
    $mod->indent=1;
    $mod->groupmode=1;
    $mod->instance = $forum->id;
    $mod->section = $sectionid;
    $log->debug("Created forum course module for project:".json_encode($mod));
    include_once("$CFG->dirroot/course/lib.php");
    if (! $mod->coursemodule = add_course_module($mod) ) {
        echo $OUTPUT->notification("Could not add a new course module to the course '" . $courseid . "'");
        return false;
    }
     course_add_cm_to_section($courseid, $mod->coursemodule, $sectionid);
    $event = \core\event\course_module_created::create(array(
        'courseid' => $courseid,
        'context'  => context_module::instance($mod->coursemodule),
        'objectid' => $mod->coursemodule,
        'other'    => array(
            'modulename' => 'forum',
            'name'       => $forum->name,
            'instanceid' => $forum->id
        )
    ));
    $event->trigger();
    //return $DB->get_record("forum", array("id" => "$forum->id"));
    return array($forum,$mod);
}

function create_project_chat($courseid,  $projectname, $sectionid) {
// How to set up special 1-per-course forums
    global $CFG, $DB, $OUTPUT, $USER;
    require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
    $log=new moodle\local\morph\Logger(array("prefix"=>'project_'));

    // Doesn't exist, so create one now.
    $chat = new stdClass();
    $chat->course = $courseid;
   // $chat->type = "social";
    if (!empty($USER->htmleditor)) {
        $chat->introformat = $USER->htmleditor;
    }
    $chat->name  = "ACS:Chat";
    $chat->intro = "General chat on project: ".$projectname;
    $chat->keepdays = 0;

    $chat->chattime = time();
    $chat->schedule = 0;
    $chat->timemodified = time();
    $chat->id = $DB->insert_record("chat", $chat);
    $log->debug("Created chat for project:".json_encode($chat));
    if (! $module = $DB->get_record("modules", array("name" => "chat"))) {
        echo $OUTPUT->notification("Could not find chat module!!");
        return false;
    }
    $mod = new stdClass();
    $mod->course = $courseid;
    $mod->module = $module->id;
    $mod->visible=1;
    $mod->indent=1;
    $mod->groupmode=1;
    $mod->instance = $chat->id;
    $mod->section = $sectionid;
    $log->debug("Created chat course module for project:".json_encode($mod));
    include_once("$CFG->dirroot/course/lib.php");
    if (! $mod->coursemodule = add_course_module($mod) ) {
        echo $OUTPUT->notification("Could not add a new course module to the course '" . $courseid . "'");
        return false;
    }
    course_add_cm_to_section($courseid, $mod->coursemodule, $sectionid);
    $event = \core\event\course_module_created::create(array(
        'courseid' => $courseid,
        'context'  => context_module::instance($mod->coursemodule),
        'objectid' => $mod->coursemodule,
        'other'    => array(
            'modulename' => 'chat',
            'name'       => $chat->name,
            'instanceid' => $chat->id
        )
    ));
    $event->trigger();
   // return $DB->get_record("chat", array("id" => "$chat->id"));
    return array($chat,$mod);
}