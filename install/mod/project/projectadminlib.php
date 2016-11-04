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
    $log=new moodle\local\morph\Logger(array("prefix"=>'project_'));
    $eventdata=$event->get_data();
    $other=$eventdata["other"];
    $cm=get_coursemodule_from_instance($other["modulename"],$other["instanceid"]);
    $section=$DB->get_record("course_sections",array('id'=>$cm->section));
    $courseid=$eventdata['courseid'];
    $projectname=$other['name'];
    $projectid=$other['instanceid'];
    $forumname=$projectname." (forum)";
    $log->debug("CREATING PROJECT:".$projectname." in section:".$section->section);
    $forum=create_project_forum($courseid,$projectname,$section->section);
    $chat=create_project_chat($courseid,  $projectname, $section->section);

    $project_tools=new stdClass();
    $project_tools->project_id=$projectid;
    $project_tools->forum_id=$forum->id;
    $project_tools->chat_id=$chat->id;
    $DB->insert_record("project_tools", $project_tools);

}
function handle_project_deleted_event(){
    global $CFG;
    require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
    $log=new moodle\local\morph\Logger(array("prefix"=>'project_'));

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
    $forum->name  = "Discussion forum";
    $forum->intro = "General discussion on project: ".$projectname;
    $forum->assessed = 0;
    $forum->forcesubscribe = 0;

    $forum->timemodified = time();
    $forum->id = $DB->insert_record("forum", $forum);

    if (! $module = $DB->get_record("modules", array("name" => "forum"))) {
        echo $OUTPUT->notification("Could not find forum module!!");
        return false;
    }
    $mod = new stdClass();
    $mod->course = $courseid;
    $mod->module = $module->id;
    $mod->visible=0;
    $mod->indent=1;
    $mod->groupmode=1;
    $mod->instance = $forum->id;
    $mod->section = $sectionid;
    include_once("$CFG->dirroot/course/lib.php");
    if (! $mod->coursemodule = add_course_module($mod) ) {
        echo $OUTPUT->notification("Could not add a new course module to the course '" . $courseid . "'");
        return false;
    }
     course_add_cm_to_section($courseid, $mod->coursemodule, $sectionid);
    return $DB->get_record("forum", array("id" => "$forum->id"));
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
    $chat->name  = "Chat";
    $chat->intro = "General chat on project: ".$projectname;
    $chat->keepdays = 0;

    $chat->chattime = time();
    $chat->schedule = 0;
    $chat->timemodified = time();
    $chat->id = $DB->insert_record("chat", $chat);

    if (! $module = $DB->get_record("modules", array("name" => "chat"))) {
        echo $OUTPUT->notification("Could not find chat module!!");
        return false;
    }
    $mod = new stdClass();
    $mod->course = $courseid;
    $mod->module = $module->id;
    $mod->visible=0;
    $mod->indent=1;
    $mod->groupmode=1;
    $mod->instance = $chat->id;
    $mod->section = $sectionid;
    include_once("$CFG->dirroot/course/lib.php");
    if (! $mod->coursemodule = add_course_module($mod) ) {
        echo $OUTPUT->notification("Could not add a new course module to the course '" . $courseid . "'");
        return false;
    }
    course_add_cm_to_section($courseid, $mod->coursemodule, $sectionid);
    return $DB->get_record("chat", array("id" => "$chat->id"));
}