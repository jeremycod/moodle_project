<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 04/11/16
 * Time: 3:32 PM
 */

require_once (dirname ( __FILE__ ) . '/../../config.php');
global $CFG, $DB;
require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");


$context =context_system::instance();
require_login();
$PAGE->set_context($context);
$log=new moodle\local\morph\Logger(array("prefix"=>"project_"));

$log->debug("PROJECT GROUP AJAX PROCESSING");

session_write_close();

if(is_ajax()) {
    if (isset($_POST["action"]) && !empty($_POST["action"])) { //Checks if action value exists
        $action = $_POST["action"];
        switch ($action) { //Switch case for value of action
            case 'projectgroupactivate':
                $log->debug("project group activate action...".$_POST["courseid"]." ".$_POST["projectid"]." ".$_POST["groupid"]);
                if($projectgroup=$DB->get_record('project_group_mapping',array('course_id'=>$_POST["courseid"],'project_id'=>$_POST["projectid"],'group_id'=>$_POST["groupid"]))){
                    $projectgroup->disabled=filter_var($_POST["disabled"],FILTER_VALIDATE_BOOLEAN);
                    $log->debug("UPDATE DISABLE/ENABLE GROUP:".json_encode($projectgroup));
                    $DB->update_record('project_group_mapping',$projectgroup);
                }else{
                    $projectgroup=new stdClass();
                    $projectgroup->course_id=$_POST["courseid"];
                    $projectgroup->project_id=$_POST["projectid"];
                    $projectgroup->group_id=$_POST["groupid"];
                    $projectgroup->disabled=filter_var($_POST["disabled"],FILTER_VALIDATE_BOOLEAN);
                    $log->debug("DISABLE/ENABLE GROUP:".json_encode($projectgroup));
                    $DB->insert_record('project_group_mapping',$projectgroup);

                }
                break;
        }
    }
}
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

