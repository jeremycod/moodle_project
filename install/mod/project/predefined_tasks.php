<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 07/02/17
 * Time: 10:53 AM
 */
require('../../config.php');
require_once($CFG->dirroot.'/mod/project/locallib.php');
require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
$log=new moodle\local\morph\Logger(array('prefix'=>"project_"));
//require_once($CFG->libdir.'/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course ID
$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
require_course_login($course, true);
$context = context_course::instance($id);
require_capability('mod/project:view', $context);
$PAGE->set_pagelayout('course');
$PAGE->set_url('/mod/project/predefined_tasks.php', array('id' => $id));
$PAGE->set_title($course->shortname.': '.$course->shortname);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string("Predefined tasks in course"), 2);
$predefined_tasks=$DB->get_records('project_predefined_tasks',array('course_id'=>$id));
echo $OUTPUT->box_start('mod_introbox', 'projectintro');
$content = 'Please edit existing or create new predefined tasks that will be used by ACS module across the course.<br /><br />';
foreach($predefined_tasks as $pt){
    $content .= "<a href='predefined_task_edit.php?course_id=".$id."&pt=".$pt->id."'>".$pt->name."<br /></a>";
}
$content .= "<br /><a href='".$CFG->wwwroot."/mod/project/predefined_task_edit.php?course_id=".$course->id."'>+ NEW PREDEFINED TASK</a><br />";
echo $OUTPUT->box($content, "generalbox center clearfix");
echo $OUTPUT->box_end();
echo $OUTPUT->footer();