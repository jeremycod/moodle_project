<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 07/02/17
 * Time: 11:47 AM
 */
require('../../config.php');
require_once($CFG->dirroot.'/mod/project/edit_form.php');
require_once($CFG->dirroot.'/mod/project/locallib.php');
require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
$log=new moodle\local\morph\Logger(array('prefix'=>"project_"));

$id      = optional_param('course_id', 0, PARAM_INT); // Course ID
$predefined_task_id        = optional_param('pt', 0, PARAM_INT); //Task ID
$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
require_course_login($course, true);
$context = context_course::instance($id);
require_capability('mod/project:view', $context);

$PAGE->set_pagelayout('course');
$PAGE->set_url('/mod/project/predefined_tasks.php', array('id' => $id));
$PAGE->set_title($course->shortname.': '.$course->shortname);
$PAGE->set_heading($course->fullname);

if ($predefined_task_id) {
    $predefined_task = $DB->get_record('project_predefined_tasks', array('id'=>$predefined_task_id), '*', MUST_EXIST);
    $predefined_task->description =array('text'=>$predefined_task->description,'format'=>1,'itemid'=>0);
} else {
    $predefined_task = new stdClass();
    $predefined_task->id         = null;
}


$mform = new predefined_tasks_edit_form(null, array('task'=>$predefined_task,'courseid'=>$course->id));

if ($mform->is_cancelled()) {
    $log->debug("FORM IS CANCELED");
    if (empty( $predefined_task->id)) {
        redirect("predefined_tasks.php?id=".$id);
    } else {
        redirect("predefined_tasks.php?id=".$id."&pt=".$predefined_task->id);
    }

}else if ($data = $mform->get_data()) {
    $log->debug("TASK GET DATA");
    $log->debug("TASK  DATA:".json_encode($data));
    $datarecord=(object)$data;
    $datarecord->description=$data->description['text'];
    if ($datarecord->id) {

        $DB->update_record('project_predefined_tasks', $datarecord);
    }else{
        $data->id = $DB->insert_record('project_predefined_tasks', $datarecord);
        $log->debug("TASK GET DATA. ID=".json_encode($datarecord));
    }
    redirect("predefined_tasks.php?id=".$id);
}


echo $OUTPUT->header();

//echo $OUTPUT->heading(format_string('Task Editing'), 2);
if ($predefined_task_id)
    echo $OUTPUT->heading(format_string('Predefined task Editing'), 2);
else
    echo $OUTPUT->heading(format_string('Create predefined task'), 2);
//$strlastmodified = get_string("lastmodified");
//echo "<div class=\"modified\">$strlastmodified: ".userdate($project->timemodified)."</div>";
$mform->display();
echo $OUTPUT->footer();