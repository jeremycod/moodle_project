<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * project module version information
 *
 * @package    mod
 * @subpackage project
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->dirroot.'/mod/project/edit_form.php');
require_once($CFG->dirroot.'/mod/project/locallib.php');
require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
//require_once($CFG->libdir.'/completionlib.php');

$cmid       = required_param('cmid', PARAM_INT);  // Project Module ID
$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$taskid        = optional_param('t', 0, PARAM_INT); //Task ID  

$cm = get_coursemodule_from_id('project', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$project = $DB->get_record('project', array('id'=>$cm->instance), '*', MUST_EXIST);
$log=new moodle\local\morph\Logger(array("prefix"=>"project_task_"));
require_login($course, false, $cm);

$currentgroup = groups_get_activity_group($cm, true);
$members = getGroupMembers($currentgroup);
$project->currentgroup = $currentgroup;

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/project:view', $context);

$PAGE->set_url('/mod/project/task_edit.php', array('cmid' => $cmid));

$PAGE->set_title($course->shortname.': '.$project->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($project);
$log->debug("TASK ID:".$taskid);
if ($taskid) {

    $task = $DB->get_record('project_task', array('id'=>$taskid), '*', MUST_EXIST);
} else {
    $task = new stdClass();
    $task->id         = null;
}
$task->cmid = $cm->id;


$options = array('noclean'=>true, 'subdirs'=>true, 'maxfiles'=>-1, 'maxbytes'=>0, 'context'=>$context);
$data = new stdClass();
$fileoptions = array('subdirs'=>0,
    'maxbytes'=>0,// Site maximum
    //'maxfiles'=>99999999,
    'maxfiles'=>-1, // unlimited
    'accepted_types'=>'*',
    'return_types'=>2);

$mform = new task_view_form(null, array('task'=>$task, 'project'=>$project, 'members'=>$members,'data'=>$data));
if (empty($entry->id)) {
    $entry = new stdClass;
    $entry->id = $taskid;
}



// If data submitted, then process and store.
if ($mform->is_cancelled()) {
    if (empty($tasks->id)) {
        redirect("view.php?id=$cm->id");
    } else {
        redirect("view.php?id=$cm->id&taskid=$task->id");
    }
} else if ($data = $mform->get_data()) {
    $log->debug("PROCESSING FORM DATA");
    if ($data->id) {
        $log->debug("DATA:".json_encode($data)." ENTRY:".json_encode($entry));
		//store the files
		//
        $entry->id=$data->task_id;
        file_save_draft_area_files($data->attachments, $context->id, 'mod_project', 'attachment',
            $entry->id, $fileoptions);


           /*  $formdata = file_postupdate_standard_filemanager($data,
                'files',
                $fileoptions,
                $context,
                "mod_project",
                'intro',
                0);*/
           // $log->debug("FORM DATA:".json_encode($formdata));
        if(!empty($data->attachments)){
		   //echo "<br/>USER FILE:".json_encode($data->userfile);

            //echo "<br/>Task:".$task->id." CMID:".$task->cmid." taskid:".$taskid." from form:".$data->task_id;

           $filename=$mform->get_new_filename('files');
            $log->debug("FILENAME:".$filename);

            /*  $dirpath="/acsmodule/".$cmid;
             $filepath=$CFG->tempdir.$dirpath."/".$filename;
             make_temp_directory($dirpath);
           //  echo "<br/>NEW FILENAME:".$filename." TEMP DIR:".$filepath;
             $mform->save_file('userfile',$filepath, false);
             $file=new stdClass();
             $file->taskid=$data->task_id;
             $file->filepath=$dirpath;
             $file->filename=$filename;
             $file->time=time();
             $file->student_id=$USER->id;

             //$file->itemid = $data->userfile;
             //$file->task_id = $data->id;
             //print_r($file);
              $DB->insert_record('project_submitted_files', $file);*/
		 }
		
		if(!empty($data->comments)){
			$comment = new stdClass();
			$comment->time = time();
			$comment->task_id = $data->id;
			$comment->student_id = $USER->id;
			$comment->comment = $data->comments;

			$DB->insert_record('project_feedback', $comment);
			add_to_log($cm->course, 'project', 'comment', 'task_edit.php?id='.$cm->id, 'project '.$project->id.' task: '.$comment->task_id);
		}
		
		
        //add_to_log($course->id, 'course', 'update mod', '../mod/project/view.php?id='.$cm->id, 'project '.$project->id);
        $params = array(
            'context' => $context,
            'objectid' => $data->id
        );

    } 
    redirect("view.php?id=$cm->id");
}
$log->debug("ENTRY:".json_encode($entry));
$draftitemid = file_get_submitted_draft_itemid('attachments');

file_prepare_draft_area($draftitemid, $context->id, 'mod_project', 'attachment', $entry->id, $fileoptions);
$entry->attachments = $draftitemid;
$mform->set_data($entry);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Task View: '.$task->name), 2);

$mform->display();

add_to_log($course->id, 'project', 'task view', 'task_view.php?id='.$cm->id, $taskid);
//$strlastmodified = get_string("lastmodified");
//echo "<div class=\"modified\">$strlastmodified: ".userdate($project->timemodified)."</div>";

echo $OUTPUT->footer();
