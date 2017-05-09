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
 * @package    mod
 * @subpackage project
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in project module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function project_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function project_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function project_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function project_get_view_actions() {
    return array('view','view all');
}

/**
 * List of update style log actions
 * @return array
 */
function project_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add project instance.
 * @param stdClass $data
 * @param mod_project_mod_form $mform
 * @return int new project instance id
 */
function project_add_instance($data, $mform = null) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;

    $data->timemodified = time();
    $displayoptions = array();
  /*  if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }*/
    $displayoptions['printintro']   = $data->printintro;
    $data->displayoptions = serialize($displayoptions);

    /*if ($mform) {
        $data->content       = $data->project['text'];
        $data->contentformat = $data->project['format'];
    }*/

    $data->id = $DB->insert_record('project', $data);

    // we need to use context now, so we need to make sure all needed info is already in db
    $id = $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
    $context = context_module::instance($cmid);
	$DB->set_field('course_modules', 'groupmode', 1, array('id'=>$cmid)); //Automatically set separate Group Mode


    /*if ($mform and !empty($data->project['itemid'])) {
        $draftitemid = $data->project['itemid'];
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_project', 'content', 0, project_get_editor_options($context), $data->content);
        $DB->update_record('project', $data);
    }*/

    return $data->id;
}

/**
 * Update project instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function project_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid        = $data->coursemodule;
  //  $draftitemid = $data->project['itemid'];

    $data->timemodified = time();
    $data->id           = $data->instance;
    $data->revision++;

  /*  $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }*/
    $displayoptions['printintro']   = $data->printintro;
    $data->displayoptions = serialize($displayoptions);

  //  $data->content       = $data->project['text'];
   // $data->contentformat = $data->project['format'];

    $DB->update_record('project', $data);
	$DB->set_field('course_modules', 'groupmode', 1, array('id'=>$cmid)); //Automatically set separate Group Mode

    $context = context_module::instance($cmid);
  /*  if ($draftitemid) {
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_project', 'content', 0, project_get_editor_options($context), $data->content);
        $DB->update_record('project', $data);
    }*/

    return true;
}

/**
 * Delete project instance.
 * @param int $id
 * @return bool true
 */
function project_delete_instance($id) {
    global $DB;

    if (!$project = $DB->get_record('project', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('project', array('id'=>$project->id));

    return true;
}

/**
 * Get List of group members userid's
 * $param int groupids
 * return object userid
 */
function getGroupMembersID($groupid){
	global $DB;
	
	return $DB->get_records('groups_members',array('groupid'=>$groupid),null,'userid');
}

/**
 * Get Group ID based on userid
 * @param int userid
 * return int groupid
 */
/*function getGroupID($userid){
	global $DB;
	echo "<br/>get group ID for:".$userid;
	return $DB->get_record('groups_members', array('userid'=>$userid), 'groupid')->groupid;
}*/
function getGroupID($userid, $courseid){
	global $DB;
	$sql="SELECT gm.groupid
	FROM {groups_members} as gm
	JOIN {groups} as g ON g.id=gm.groupid
	WHERE g.courseid=:course AND gm.userid=:userid";
	$params['course']=$courseid;
	$params['userid']=$userid;
	$response=array();
	if($records=$DB->get_records_sql($sql, $params)){
		foreach($records as $rs){
			$response[]=$rs->groupid;
		}
	}
	if(!empty($response)){
		return $response[0];
	}else return 0;

	//return $DB->get_records_sql($sql, $params);

}

/**
 * Get group members userid, username and lasttime online
 * @param  int groupid
 * return array members with userid, username, lastonline
 */
function getGroupMembers($groupid){
	global $DB, $COURSE;
	
	$members = array(); //Set a new array
	$groupids = getGroupMembersID($groupid); //Get a list of users in a group
	foreach($groupids as $studentid) { //For each of those users, iterate
		$studentname = $DB->get_record('user',array('id'=>$studentid->userid),'username'); //Get the username based on student id
		$lastaccess = $DB->get_record('user_lastaccess',array('userid'=>$studentid->userid, 'courseid'=>$COURSE->id),'timeaccess'); //Get the last time the user logged on
		if(!$lastaccess){ //If they have never been online, we need to set to zero, because otherwise it is null
			$lastaccess = new stdClass();
			$lastaccess->timeaccess = "0";
			}
		$student = array($studentid->userid, $studentname->username, $lastaccess->timeaccess); //Add user info to an array
		array_push($members, $student); //Add to the array we will be returning. 
		}
		
	return $members;
}

/**
 * Display username
 * @param string userid's seperated by comma's
 * return array students usernames
 */
function getStudentName($userid){
		global $DB;

		$students = array(); //Set new blank array
    		$users = explode(',', $userid); //Exrract the string of users based on comma delimiter
			for($i=0; $i<count($users); $i++){
                if($users[$i]>0){
                    $student = $DB->get_record('user',array('id'=>$users[$i]), 'username'); //Get the username of the studentid
                    array_push($students, $student); //Add student name to array
                }

		}
		
		return $students;
}

/**
 * List all groups in a course
 * @param int courseid
 * return array groupids,groupnames
*/
function listGroups($course){
	global $DB;
	
	return $DB->get_records('groups',array('courseid'=>$course),null, 'id,name');
}

/**
 * Get Group Name
 * @param int group id
 * return string groupname
 */
function getGroupName($groupid){
	global $DB;
	
	return $DB->get_record('groups',array('id'=>$groupid),'name')->name;
}

/**
 * Get Student IDs from a string and return an array
 * @param string userid
 * return array of student ids
 */
function getStudentID($userid){
		global $DB;
		
		$students = array();
		$users = explode(',', $userid);
		for($i=0; $i<count($users); $i++){
			//$student = $DB->get_record('user',array('id'=>$users[$i]), 'username');
			array_push($students, $users[$i]);
		}
		
		return $students;
}

/**
 * Get Students fullnames
 * @param string userid
 * return array groupname
 */
function getStudentFullName($userid){
		global $DB;
		
		$students = array(); //Set new blank array
		$users = explode(',', $userid);
		for($i=0; $i<count($users); $i++){
			$student_f = $DB->get_record('user',array('id'=>$users[$i]), 'firstname')->firstname;
			$student_l = $DB->get_record('user',array('id'=>$users[$i]), 'lastname')->lastname;
			$student_fullname = $student_f; //Set their first name
			array_push($students, $student_fullname);
		}
		
		return $students;
}

/**
 * Get a single Student Name by id
 * @param int userid
 * return string username
 */
function studentidToName($userid){
	global $DB;
	
	return $DB->get_record('user',array('id'=>$userid),'username')->username;
}

/**
 * Get Student Full Name Capitalized first letters
 * @param int userid
 * return string fullname
 */
function studentidToLMS_Name($userid){
	global $DB;
	
	return ucwords($DB->get_record('user',array('id'=>$userid),'firstname')->firstname .' '.$DB->get_record('user',array('id'=>$userid),'lastname')->lastname);
}

/**
 * Get Groups Tasks End Date
 * @param int currentgroup
 * return object of task end dates
 */
function getGroupsTasks($currentgroup,$projectid){
	global $DB;
	
	return $DB->get_records('project_task', array('group_id'=>$currentgroup, 'project_id'=>$projectid), 'end_date');
}

/**
 * Display Group members usernames
 * @param object group
 * return text of usernames separated by newlines
 */
function displayGroupMembers($group){
	foreach($group as $student){
		echo $student->username."<br />";
	}
	echo "<br />";
}

/**
 * Get Members last access timestamp of a particular course
 * @param int studentid, int courseid
 * return string timeaccess
 */
function getMembersLastAccess($studentid, $courseid){
	global $DB;
	
	return $DB->get_record('user_lastaccess',array('userid'=>$studentid, 'courseid'=>$courseid),'timeaccess');
}

/**
 * Get a list of imported chats
 * @param int groupid
 * return object of chat summaries
 */
function getGroupChatHistory($currentgroup,$projectid) {
	global $DB;
	return $DB->get_records('project_history_imp_summary', array('group_id' =>$currentgroup, 'project_id'=>$projectid));
}

/**
 * Get user comments for a task
 * @param int taskid
 * return object of comments 
 */
function getUsersComments($task_id) {
	global $DB;
	
	return $DB->get_records('project_feedback', array('task_id' => $task_id));
}

/**
 * Takes an array of students and outputs them as nice text
 * @param array userids
 * return Student names with capitalized names
 */
function displayUsersAsText($users_array){
	$result = "";
	$lastelement = end($users_array);
	$firstelement = reset($users_array);
	foreach($users_array as $user){
		if($lastelement == $user && $firstelement != $lastelement) { //if this is the last and not member to be listed
			$result .= " & " . ucfirst($user) . " ";
		}else
			if($firstelement != $lastelement)
				 				$result .= ucfirst($user)." ";
		 			else
 				$result .= ucfirst($user).", ";
	}//end for
	//$result .= implode(", ",$users_array);
	return rtrim(ucwords($result),",");
}

/**
 * Displays a group forum link, if it exists.
 * @param None
 * return html of Forum Link
 */
function displayForums(){
	global $CFG, $DB, $COURSE;

	//If Forums are setup for Groups, display links to them.
	$html = '';
    $forummoduleid=$DB->get_field('modules', 'id', array('name'=> 'forum'));
	if($forum = $DB->get_records('course_modules', array('module'=>$forummoduleid,  'course'=>$COURSE->id, 'groupmode'=>1), 'id,instance')){
		foreach($forum as $forum_link){
			$forum_name = $DB->get_record('forum', array('id'=>$forum_link->instance), 'name');
			$html .= ' <tr><td><img src="'.$CFG->wwwroot.'/mod/forum/pix/icon.png" width="16px" height="16px"> <a href="'.$CFG->wwwroot.'\mod\forum\view.php?id='.$forum_link->id.'">'.$forum_name->name.'</a></td></tr>';
		}
		//$html .= "<br />";
	}
	
	return $html;
}

/**
 * Add users of a group the user mapping table
 * @param int courseid, int groupid
 * return 
 */
function fillUsers($courseid, $currentgroup){
	global $DB;
	//First, check to see if there is any users already there
	$mapped_users = $DB->get_records('project_user_mapping', array('course_id'=>$courseid,'group_id'=>$currentgroup), null, 'user_id');
	//If no users are returned, array is empty, fill it with group members id's.
	if(empty($mapped_users)){
		$members = $DB->get_records('groups_members', array('groupid'=>$currentgroup)); //Get all the members of a group
		$fillempty = new stdClass();
		$fillempty->course_id = $courseid; //Add the courseid
		$fillempty->group_id = $currentgroup; //Add the groupid
		foreach($members as $member){ //Iterate through each group member
			$fillempty->user_id = $member->userid;
			$fillempty->id = $DB->insert_record('project_user_mapping', $fillempty); //Insert into the table
		}
	}
}//end filUsers

//**Adaptive Features Section**//

/**
 * Get the students task work allocation
 * @param int currentgroup
 * return array of rank members by work allocation
 */
function RankMembersTasksDistribution($currentgroup,$projectid){
	global $DB;
	
	$groups_members = $DB->get_records('groups_members', array('groupid'=>$currentgroup)); //Get the members of a current group
	$tasks = $DB->get_records('project_task', array('group_id'=>$currentgroup, 'project_id'=>$projectid), '', 'id,members,hours'); //Get the tasks assigned to a group, return an array of task id, members assigned, and total hours

	//Iterate all members in a group
	$total_hours = 0;
	$member_rank = array();
	foreach($groups_members as $member){
		//For every member, find their associated tasks and estimated hours.
		$hours= 0;
		foreach($tasks as $task_hours){
			$names = getStudentID($task_hours->members); //Get an array of users in the project group
			if(in_array($member->userid, $names)){ //If member is in the array of assigned members add hours
				if((count($names)>1)) //If there is more than one member assigned, split the workload by all members
					$hours += $task_hours->hours/count($names);
				else //Otherwise just assign all hours to assigned member
					$hours += $task_hours->hours;
			}
		}//end inner foreach
		$member_rank[$member->userid] = $hours;
		
		$total_hours += $hours; //Add hours of a task to the overall
	}//end outer foreach

	return $member_rank;
}//end function RankMembersTasksDistribution

/*
*  Function to take number of hours a member is assigned, and what would be equal hours (total hours/#members)
*  Returns the % of variation when it's > 20% and < -20%.
*/
/**
 * Find the distribution hours in allocated workload
 * @param float hours, float equal_hours
 * return distribution
 */
function MemberWorkloadDistribution($hours, $equal_hours){

	$distribution = (($hours - $equal_hours)/$equal_hours)*100;
	if($distribution > 20 || $distribution < -20)
		return round($distribution,0)."%";
		
}//end function MemberWorkloadDistribution

/*
* Function Takes the group# and determines members ranks.
*  Returns true when a member is found to be unbalanced.
*/
function AlertWorkloadDistribution($group,$projectid){
	global $DB;
	
	$course = $DB->get_record('groups',array('id'=>$group),'courseid')->courseid;

	$member_rank = RankMembersTasksDistribution($group,$projectid);

	//Get the total number of hours based on each student
	$total_hours = array_sum($member_rank);
	$equal_hours = $total_hours/count($member_rank);
	
	if($total_hours==0)
		return;
	foreach($member_rank as $key=>$member){
		$workload = MemberWorkloadDistribution($member, $equal_hours);
		if(!empty($workload)){
			add_to_log($course, 'project', 'alert', 'workload dist: '.$group);
			return true;
			break;
		}//end if
	}//end foreach
	
}//end function AlertWorkloadDistribution

/*
* Function takes group# and determines the progress%
* Returns the progress % and time %
*/
function getCurrentGroupProgress($group,$projectid){
	global $DB;

	$hours_complete = $total_hours = $start = $end = 0;
	$tasks = $DB->get_records('project_task', array('group_id'=>$group, 'project_id'=>$projectid), '', 'id,name,hours,progress,start_date,end_date');
	if(!$tasks)
		return "0/0";
	
	foreach($tasks as $task){
		//Find the earliest start time, not set OR new start is sooner
		if($start==0 || $task->start_date<$start){
			$start = $task->start_date;
		}
		//Find the latest end time, not set OR later end time
		if($end==0 || $task->end_date>$end){
			$end = $task->end_date;
		}
		$hours_complete += $task->hours * ($task->progress/100);
		$total_hours += $task->hours;
	}
if($total_hours>0)
	$group_progress = round($hours_complete/$total_hours*100); //Store rounded hours of the group progress.
    else $group_progress=0;
	//Find how many days between the first task and the last task
	$total_days = ($end - $start)/(60*60*24);
	//Find how many days into the project a group is
	$days_in = floor((time() - $start)/(60*60*24));

	//Find out what the percentage of time done is.
	$time_done = round(($days_in / $total_days)*100,0);
	
	return $group_progress."/".$time_done;
}

/*
 * Function Call to check other alert procedures and warn users who may be having difficulty
 * @param int userid, int currentgroup
 * return string html alerts (jquery pop ups, or banners).
 */
function checkAlerts($userid, $currentgroup){
	global $CFG, $COURSE, $DB,$USER;
	require_once ($CFG->dirroot . "/local/morph/classes/alerts_controller.php");
	$alerts_controller=new alerts_controller();
	//Get config alert thresholds
	$config = get_config('project');
	$html = ""; //start blank new html
	
	//get an object of the current user and any potential alerts that may have lapsed.
	//$alerts = $DB->get_record('project_user_mapping', array('user_id'=>$userid), 'id,user_id,group_id,meetings_attended,meetings_total,meeting_alert,cohort_alert,forum_alert,import_alert');

	//if($alerts){

	//if($alerts->cohort_alert+($config->prevcohortalertsfreq) < time() || $alerts->cohort_alert==0 )
	//if($alerts->cohort_alert+($config->prevcohortalertsfreq) < time() || $alerts->cohort_alert==0 )
 		//checkPreviousCohorts($COURSE, $currentgroup);
	//if($alerts->forum_alert+($config->lowforumalertsfreq*60*60*24) < time() || $alerts->forum_alert+($config->highforumalertsfreq*60*60*24) < time() || $alerts->forum_alert==0 )
	//if($alerts->forum_alert+($config->lowforumalertsfreq*60*5) < time() || $alerts->forum_alert+($config->highforumalertsfreq*60*60*24) < time() || $alerts->forum_alert==0 )
	//	checkForumParticpation($currentgroup, $alerts);
	//if($alerts->import_alert+($config->lowimportalertsfreq*60*60*24) < time() || $alerts->import_alert+($config->highimportalertsfreq*60*60*24) < time() || $alerts->import_alert==0 )
	//if($alerts->import_alert+($config->lowimportalertsfreq*60*5) < time() || $alerts->import_alert+($config->highimportalertsfreq*60*60*24) < time() || $alerts->import_alert==0 )
		//checkImportedParticpation($currentgroup, $alerts);
	
	//Make sure there has been at least 1 meeting, otherwise divide by zero error.
/*if($alerts->meetings_total>0){
	//If meetings attended is <= 50% and they have not been previously alerted since the last meeting, prompt the user.
	if(($alerts->meetings_attended/$alerts->meetings_total)*100 <= 50 && $alerts->meeting_alert==0){
		$header="Meeting Alert!";
		$content="It seems that you have missed over 50% of the meetings in your group. Please try to attend the next meeting as it is important for the project\'s success that all team members are attending frequently.";
	//	$alerts_controller->create_interruptive_notification_alert($USER->id,$COURSE->id,0, $header, $content);

			 //Set a flag to true that the user has been alerted to not allow for repeat alerts until the next meeting.
			$DB->set_field('project_user_mapping', 'meeting_alert', 1, array('user_id'=>$userid));
			
			add_to_log($COURSE->id, 'project', 'alert', 'meeting attendance '.$alerts->meetings_attended.'/'.$alerts->meetings_total);
	}//End check 
	}//end if meetings > 0*/
	//}//end if alerts true
	
	return $html;
}

/**
 * Check previous courses for their progress at a specific point in time
 * @param object course, int currentgroup
 * return string html 
 */
function checkPreviousCohorts($course, $currentgroup){
	global $CFG, $DB, $USER, $COURSE;
	require_once ($CFG->dirroot . "/local/morph/classes/alerts_controller.php");
	$alerts_controller=new alerts_controller();
	$config = get_config('project');

	//See if values exist in table, otherwise we don't continue the check
	if($DB->count_records('project_previous_cohorts')==0)
		return;
//Get the project ID for the future link
    $projectid = $DB->get_record_sql('SELECT id FROM {course_modules} WHERE module = (SELECT id FROM {modules} WHERE name = \'project\') AND course = :course ', array('course'=>$course->id))->id;

	$grades = array(); //Array to store all the users and grades
	$avg_grades= array(); //Array to store all the average grades with keys being group id's.
	$progress = explode('/', getCurrentGroupProgress($currentgroup,$projectid)); //Get the current group progress that is returned by "work/time", seperate the two variables
	//save the progress in the table
	$record = new stdClass();
	$record->group_id = $currentgroup;
	//$record->time_percentage = ceil($progress[1]/5) * 5; //Round to the nearest 5
	$record->time_percentage = $progress[1];
	$record->progress_percentage = $progress[0];
	//echo "time: ".$record->time_percentage;
	
	//Get a list of groups with grades for both passed and failed
	$passed_groups = $DB->get_records('project_completed_groups', array('pass'=>1), null, 'group_id');
	$failed_groups = $DB->get_records('project_completed_groups', array('pass'=>0), null, 'group_id');
	
	//If there are no failed groups and no passed groups, this will not work and through an exception, so we will return
	if(empty($failed_groups) && empty($passed_groups))
			return;

	//Get the lowest average percentage of work done by failed groups
	$num_failed = 0;
	foreach($failed_groups as $group=>$failed){
		//If there are no groups with results, we don't continue our analysis
		$count_cohorts = $DB->count_records('project_previous_cohorts', array('group_id' => $group,'time_percentage'=>$record->time_percentage));
		if(empty($count_cohorts))
			return;
		$failed_progress[$num_failed] = $DB->get_record_sql('SELECT progress_percentage FROM {project_previous_cohorts} WHERE group_id = :group_id AND time_percentage = :time ORDER BY progress_percentage DESC LIMIT 1', array('group_id' => $group,'time'=>$record->time_percentage))->progress_percentage;
		$num_failed++;
	}
		//Get the average by adding all progress and dividing by the number of groups
		$avg_failed = round(array_sum($failed_progress)/count($failed_progress));	
		$max_failed = max($failed_progress);
		//echo "<br />avg fail: ".$avg_failed;
		//echo "<br />max fail: ".$max_failed;

	//Get the highest average percentage of work done by successful groups
	$num_passed = 0;
	foreach($passed_groups as $group=>$passed){
		//If there are no groups with results, we don't continue our analysis
		$count_cohorts = $DB->count_records('project_previous_cohorts', array('group_id' => $group,'time_percentage'=>$record->time_percentage));
		if(empty($count_cohorts))
			return;
			
		//Select the highest average percentage of work done from table
		$passed_progress[$num_passed] = $DB->get_record_sql('SELECT progress_percentage FROM {project_previous_cohorts} WHERE group_id = :group_id AND time_percentage = :time ORDER BY progress_percentage ASC LIMIT 1', array('group_id' => $group,'time'=>$record->time_percentage))->progress_percentage;
		$num_passed++;
	}
		//Get the average by adding all progress and dividing by the number of groups
		$avg_passed = round(array_sum($passed_progress)/count($passed_progress));
		$min_passed = min($passed_progress);
		//echo "<br />avg pass: ".$avg_passed;
		//echo "<br />min pass: ".$min_passed;
		
	//Determine if a group is at risk of failure

	

	//If a current groups progress is greater than the maximum failure, there is no risk.  (Very High)
	if($record->progress_percentage > $max_failed){
		return;
	}
	
	//If a current groups progress is less than the minimum passing grade, they are absolutely at risk (very high)
	if($record->progress_percentage < $min_passed){			
		$cohort_alert = $DB->get_record('project_user_mapping', array('user_id'=>$USER->id), 'cohort_alert')->cohort_alert;
		$header='Very High Risk Progress Alert';
		//$content="				Your group\'s progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
				//				Your group is at very high risk of failing the project because <b>'.count($failed_progress).'</b> groups from previous cohorts had the same amount of work done at this time and failed. To improve your group\'s risk level, you need to complete at least <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project.";
		$content="You groups progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
				You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed. To improve your risk level, you need to complete <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project.";
		$alerts_controller->create_interruptive_notification_alert($USER->id,$COURSE->id,0, $header, $content);
		/*$html = '<div style="border:1px dashed black;width:80%;background:#FFFFD1;">
				<img style="float:left;" src="'.$CFG->wwwroot.'/mod/project/pix/alert_icon.png" width="12px" height="12px" />
				<span id="title" style="margin:auto;"> Very High Risk Progress Alert</span><br />
				You groups progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
				You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed. To improve your risk level, you need to complete <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project.
			</div>';*/
	
		//Check if last popup was X seconds ago from settings.php
		if($cohort_alert+($config->prevcohortalertsfreq*60) < time()){
			$header='Very High Risk Progress Alert';

			/*$content='Group Progress: '.$record->progress_percentage.'% <br/><br/>
			You groups progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br /><br />
				You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed.<br /><br />
				To improve your risk level, you need to complete <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project <br /><br />
				We Recommend visiting <a href="'.$CFG->wwwroot.'/mod/project/view.php?id='.$projectid.'">your project</a>.<br />';*/
			$content="Your group\'s progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br /><br />
Your group is at very high risk of failing the project because <b>'.count($failed_progress).'</b> groups from previous cohorts<br />
had the same amount of work done at this time and failed. <br /><br />
To improve your group\'s risk level, you need to complete at least <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project.<br /><br />
+				We recommend visiting <u><a href=\"'.$CFG->wwwroot.'/mod/project/view.php?id='.$projectid.'\">your project</a></u>.<br />";
			$alerts_controller->create_interruptive_notification_alert($USER->id,$COURSE->id,0, $header, $content);
		/*$html .=
			<div id="dialog-message" title="Very High Risk Progress Alert!">
			  <p>
				<span  style="float:left; margin:0 7px 10px 0;"><img src="'.$CFG->wwwroot.'/mod/project/pix/alert_icon.png" width="32px" height="32px" /></span>
				<div style="float:right;border: solid 1px;width: 230px;height: 12px;">
				<div style="position: relative;top:0px;background-color: red;width:'.$record->progress_percentage.'%; height:12px;">&nbsp;</div>
				<div style="position: relative;top:-12px;text-align:center;font-size:10px;font-weight:bold;">Group Progress: '.$record->progress_percentage.'%</div>
				</div><br /><br />
				You groups progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br /><br />
				You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed.<br /><br />
				To improve your risk level, you need to complete <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project <br /><br />
				We Recommend visiting <a href="'.$CFG->wwwroot.'/mod/project/view.php?id='.$projectid.'">your project</a>.<br />
			  </p>
			</div>
			<script>
			  $(function() {
				$( "#dialog-message" ).dialog({
				  modal: true,
				  buttons: {
					Ok: function() {
					  $( this ).dialog( "close" );
					}
				  }
				});
			  });
			  </script>';*/
			  
		//Set a flag with a timestamp so that the user has been alerted to not allow for repeat alerts until a later time if action has not been corrected.
		$DB->set_field('project_user_mapping', 'cohort_alert', time(), array('user_id'=>$USER->id));
		}//end cohort pop time check
			  
		add_to_log($course->id, 'project', 'alert', 'very high risk');
		//echo $html;
	}
	
	//If a current groups progress is between min passing and max failure, we need to determine the risk level of failure.
	if($record->progress_percentage >= $min_passed && $record->progress_percentage <= $max_failed){
		$cohort_alert = $DB->get_record('project_user_mapping', array('user_id'=>$USER->id), 'cohort_alert')->cohort_alert;

		//High Risk if the progress is less than the average amount of work of failed groups
		if($record->progress_percentage < $avg_failed){
			 $header="High Risk Progress Alert";
		//	$content='You groups project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
			//	You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed. To improve your risk level, you need to complete <b>'.($avg_failed-$record->progress_percentage).'%</b> more of your project.';
			$alerts_controller->create_interruptive_notification_alert($USER->id,$COURSE->id,0, $header, $content);
			$content="Your group\'s project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
				Your group is at high risk of failing the project because <b>'.count($failed_progress).'</b> groups from previous cohorts had the same amount of work done at this time and failed. To improve your group\'s risk level, you need to complete at least <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project.";
			/*
			$html = '<div style="border:1px dashed black;width:80%;background:#FFFFD1;">
				<img style="float:left;" src="'.$CFG->wwwroot.'/mod/project/pix/alert_icon.png" width="12px" height="12px" />
				<span id="title" style="margin:auto;"> High Risk Progress Alert</span><br />
				You groups project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
				You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed. To improve your risk level, you need to complete <b>'.($avg_failed-$record->progress_percentage).'%</b> more of your project.
			</div>';*/
			
			//Check if last popup was X seconds ago from settings.php
			if($cohort_alert+($config->prevcohortalertsfreq*60) < time()){
				$header="High Risk Progress Alert!";
				/*$content='Group Progress: '.$record->progress_percentage.'%<br/><br/>You groups project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br /><br />
				You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed.<br /><br />
				To improve your risk level, you need to complete <b>'.($avg_failed-$record->progress_percentage).'%</b> more of your project.<br /><br />
				We Recommend visiting <a href="'.$CFG->wwwroot.'/mod/project/view.php?id='.$projectid.'">your project</a>.<br />';*/
				$content="Your group\'s project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br /><br />
Your group is at high risk of failing the project because <b>'.count($failed_progress).'</b> groups from previous cohorts<br />
had the same amount of work done at this time and failed. <br /><br />
To improve your group\'s risk level, you need to complete at least <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project.<br /><br />
We recommend visiting <u><a href=\"'.$CFG->wwwroot.'/mod/project/view.php?id='.$projectid.'\">your project</a></u>.<br />";
				$alerts_controller->create_interruptive_notification_alert($USER->id,$COURSE->id,0, $header, $content);
			/*$html .= '<div id="dialog-message" title="High Risk Progress Alert!">
			  <p>
				<span  style="float:left; margin:0 7px 10px 0;"><img src="'.$CFG->wwwroot.'/mod/project/pix/alert_icon.png" width="32px" height="32px" /></span>
				<div style="float:right;border: solid 1px;width: 230px;height: 12px;">
				<div style="position: relative;top:0px;background-color: red;width:'.$record->progress_percentage.'%; height:12px;">&nbsp;</div>
				<div style="position: relative;top:-12px;text-align:center;font-size:10px;font-weight:bold;">Group Progress: '.$record->progress_percentage.'%</div>
				</div><br /><br />
				You groups project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br /><br />
				You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed.<br /><br />
				To improve your risk level, you need to complete <b>'.($avg_failed-$record->progress_percentage).'%</b> more of your project.<br /><br />
				We Recommend visiting <a href="'.$CFG->wwwroot.'/mod/project/view.php?id='.$projectid.'">your project</a>.<br />
			  </p>
			</div>
			<script>
			  $(function() {
				$( "#dialog-message" ).dialog({
				  modal: true,
				  buttons: {
					Ok: function() {
					  $( this ).dialog( "close" );
					}
				  }
				});
			  });
			  </script>';	*/
			  
		//Set a flag with a timestamp so that the user has been alerted to not allow for repeat alerts until a later time if action has not been corrected.
		$DB->set_field('project_user_mapping', 'cohort_alert', time(), array('user_id'=>$USER->id));

		}//end cohort pop time check
		
		add_to_log($course->id, 'project', 'alert', 'high risk');
		//echo $html;
				
		}
		
		//Low risk if the progress is greater than the average amount of work of successful groups
		if($record->progress_percentage > $avg_passed){
			$header="Low Risk Progress Alert";
			//$content='You groups project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
			//	You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed. To improve your risk level, you need to complete <b>'.($max_failed-$record->progress_percentage).'%</b> more of your project.
//';
			$content="Your group\'s project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
Your group is at low risk of failing the project because <b>'.count($failed_progress).'</b> groups from previous cohorts had the same amount of work done at this time and failed. To improve your group\'s risk level, you need to complete at least <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project.";
			/*$html = '
			<div style="border:1px dashed black;width:80%;background:#FFFFD1;">
				<img style="float:left;" src="'.$CFG->wwwroot.'/mod/project/pix/alert_icon.png" width="12px" height="12px" />
				<span id="title" style="margin:auto;"> Low Risk Progress Alert</span><br />
				You groups project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
				You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed. To improve your risk level, you need to complete <b>'.($max_failed-$record->progress_percentage).'%</b> more of your project.
			</div>';*/
		
		add_to_log($course->id, 'project', 'alert', 'low risk');
		//echo $html;
				
		}
		
		//Medium risk if the progress is between the average amount of work of failed and successful groups
		if($record->progress_percentage >= $avg_failed && $record->progress_percentage <= $avg_passed){
			$header="Medium Risk Progress Alert";
			$content="Your group\'s project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
Your group is at medium risk of failing the project because <b>'.count($failed_progress).'</b> groups from previous cohorts had the same amount of work done at this time and failed. To improve your group\'s risk level, you need to complete at least <b>'.($min_passed-$record->progress_percentage).'%</b> more of your project.";
		/*	$html = '
			<div style="border:1px dashed black;width:80%;background:#FFFFD1;">
				<img style="float:left;" src="'.$CFG->wwwroot.'/mod/project/pix/alert_icon.png" width="12px" height="12px" />
				<span id="title" style="margin:auto;"> Medium Risk Progress Alert</span><br />
				You groups project progress is currently at <b>'.$record->progress_percentage.'%</b> and the time into your project is '.$record->time_percentage.'%.<br />
				You are at-risk because <b>'.count($failed_progress).'</b> groups had the same amount of work done and failed. To improve your risk level, you need to complete <b>'.($avg_passed-$record->progress_percentage).'%</b> more of your project.
			</div>';	
		*/
		add_to_log($course->id, 'project', 'alert', 'medium risk');
		//echo $html;
		}
	}
		
}

/*Function to determine participation levels of forum users*/
function checkForumParticpation($currentgroup, $alerts){
global $DB, $USER, $COURSE, $CFG;

	require_once ($CFG->dirroot . "/local/morph/classes/alerts_controller.php");
	$alerts_controller=new alerts_controller();
$averages = $DB->get_record_sql('SELECT avg(coalesce(t1.msgchars,0)) as avgmsgsize
FROM
	(SELECT userid FROM {groups_members} WHERE groupid = '.$currentgroup.' ) t4
LEFT JOIN
	(SELECT t1.userid, sum(length(message)) as msgchars FROM {forum_posts} t1
LEFT JOIN {forum_discussions} t2
	ON t2.id = t1.discussion
	WHERE t2.groupid = '.$currentgroup.'
	GROUP BY t1.userid) t1
	ON t4.userid = t1.userid');

	$config = get_config('project');
	$message = new stdClass();
	$message->size_small =  $averages->avgmsgsize*($config->smallmsg/100);
	$message->size_medium = $averages->avgmsgsize*1.0;
	$message->size_large =  $averages->avgmsgsize*($config->largemsg/100);
	
		$charCount = new stdClass();
	$charCount = $DB->get_records_sql('SELECT t0.userid, coalesce(Schar,0) as Schar, coalesce(Mchar,0) as Mchar, coalesce(Lchar,0) as Lchar, coalesce(Tchar,0) as Tchar
FROM
(SELECT userid FROM {groups_members}
WHERE groupid = '.$currentgroup.' ) t0
LEFT JOIN
(SELECT t1.userid, sum(length(message)) as Schar
	FROM {forum_posts} t1
	LEFT JOIN {forum_discussions} t2
	ON t2.id = t1.discussion
	WHERE t2.groupid = '.$currentgroup.' AND length(message) < '.$message->size_small.' 
	GROUP BY t1.userid) t3
ON t0.userid = t3.userid
LEFT JOIN
(SELECT t4.userid, sum(length(message)) as Mchar
	FROM {forum_posts} t4
	LEFT JOIN {forum_discussions} t5
	ON t5.id = t4.discussion
	WHERE t5.groupid = '.$currentgroup.' AND length(message) BETWEEN '.$message->size_small.'  AND '.$message->size_large.' 
	GROUP BY t4.userid) t6
ON t0.userid = t6.userid
LEFT JOIN
(SELECT t7.userid, sum(length(message)) as Lchar
	FROM {forum_posts} t7
	LEFT JOIN {forum_discussions} t8
	ON t8.id = t7.discussion
	WHERE t8.groupid = '.$currentgroup.' AND length(message) > '.$message->size_large.' 
	GROUP BY t7.userid) t9
ON t0.userid = t9.userid
LEFT JOIN
(SELECT t10.userid, sum(length(message)) as Tchar
	FROM {forum_posts} t10
	LEFT JOIN {forum_discussions} t11
	ON t11.id = t10.discussion
	WHERE t11.groupid = '.$currentgroup.' AND length(message) >= '.$message->size_small.' 
	GROUP BY t10.userid) t12
ON t0.userid = t12.userid
GROUP BY t0.userid');
	
	$stats = new stdClass();
	$stats = $DB->get_record_sql('SELECT sum(coalesce(t1.msgchars,0)) as sum
FROM
	(SELECT userid FROM {groups_members} WHERE groupid = '.$currentgroup.' ) t4
LEFT JOIN
	(SELECT t1.userid, sum(length(message)) as msgchars FROM {forum_posts} t1
LEFT JOIN {forum_discussions} t2
	ON t2.id = t1.discussion
	WHERE t2.groupid = '.$currentgroup.' AND length(message) > '.$message->size_small.' 
	GROUP BY t1.userid) t1
	ON t4.userid = t1.userid');
	
	//Get number of group members
	$numMembers = $DB->count_records('groups_members', array('groupid'=>$currentgroup));
	$stats->avg = $stats->sum/$numMembers;
	$stats->low = $stats->avg * ($config->lowthreshold / 100);
	$stats->high = $stats->avg * ($config->highthreshold / 100);
	//var_dump($stats);
	
	$low_participators = array();
	foreach($charCount as $member) {
		if($member->tchar < $stats->low){
			$member->alert = "Low";
			$low_participators[$member->userid] = studentidToName($member->userid);
		} else if($member->tchar > $stats->high) {
			$member->alert = "High";
		} else
			$member->alert = null;
	}
	
	//$charCount[$USER->id]->alert = "High";
	//var_dump($charCount);
	
	if(!empty($charCount[$USER->id]->alert)){
		if($charCount[$USER->id]->alert == "High"){
			if($alerts->forum_alert+($config->highforumalertsfreq*60*60*24) > time())
				return;
			$msg = $USER->firstname.", you are contributing really a lot to the group forums! This is great! <br /><br />
However, for the group project to be successful, it would be important to get other members' ideas, concerns and thoughts as well. <br /><br />
You would be a good leader for your group! Try to improve your leadership skills by trying to incorporate others in the discussion, especially ";

$msg .= rtrim(displayUsersAsText($low_participators),'', '');
$msg .= " who are not participating as much!";
$alert_icon = "info.png";
		/*	$msg = $USER->firstname.", You are a high participator in the forums.<br /><br /> We have determined you are rather active in the group forums. <br /><br />
			As an additional challenge to gain leadership skills, try incorporating others in to the discussion, especially ";
			$msg .= displayUsersAsText($low_participators);
			$msg .= " who's not participating as much.";*/
		}
		else{
			if($alerts->forum_alert+($config->lowforumalertsfreq*60*60*24) > time())
				return;
			$msg = $USER->firstname.", it seems that you have not contributed much to the group forums so far. <br /><br />
For the group project to be successful, it is important that each team member contributes to the discussions. Try to share some ideas, concerns or thoughts with your team members by posting to the discussion forum! <br /><br />
You can access the current discussions here: ";
$msg .= "<br/><br/><u>".displayForums()."</u>";
$alert_icon = "alert_icon.png";

			//$msg = $USER->firstname.", You are a low participator in the group forums.<br /><br /> Try posting more in-depth, detailed ideas, concepts or thoughts to expand on your posts and contribute as equally as others.<br /><br />Be sure to check out the group forums below:";
			//$msg .= "<br /><br />".displayForums();
			}
		$header='Forum Participation Alert!';

	/*	$html = '<div id="dialog-message" title="'.$charCount[$USER->id]->alert.' Forum Participation Alert!">
			  <p>
				<span  style="float:left; margin:0 7px 10px 0;"><img src="'.$CFG->wwwroot.'/mod/project/pix/alert_icon.png" width="32px" height="32px" /></span>
				'.$msg.'
			  </p>
			</div>
			<script>
			  $(function() {
				$( "#dialog-message" ).dialog({
				  modal: true,
				  buttons: {
					Ok: function() {
					  $( this ).dialog( "close" );
					}
				  }
				});
			  });
			  </script>';	*/
	
	add_to_log($COURSE->id, 'project', 'alert-forum', $charCount[$USER->id]->alert);
	//echo $html;
		$alerts_controller->create_interruptive_notification_alert($USER->id,$COURSE->id,0, $header, $msg);
	$DB->set_field('project_user_mapping', 'forum_alert', time(), array('user_id'=>$USER->id));

	}//end if alert check
	
}//end function forum participator

/*Third party import chat participation check and alert function*/
function checkImportedParticpation($currentgroup, $alerts){
	global $DB, $CFG, $COURSE, $USER;
	require_once ($CFG->dirroot . "/local/morph/classes/alerts_controller.php");
	$averages = $DB->get_record_sql('SELECT avg(coalesce(t3.msgchars,0)) as avgmsgsize
	FROM
	(SELECT user_id, coalesce(length(message),0) as msgchars FROM
	(SELECT userid FROM {groups_members} WHERE groupid = '.$currentgroup.') t0
	 LEFT JOIN {project_user_mapping}  t1
	ON t0.userid = t1.user_id
	LEFT JOIN {project_history_imp_detail} t2
	 ON t2.user = t1.skype
	 WHERE group_id = '.$currentgroup.'
	 ) t3');
		
	$config = get_config('project');
	$message = new stdClass();
	$message->size_small =  $averages->avgmsgsize * ($config->smallmsg / 100);
	$message->size_medium = $averages->avgmsgsize*1.0;
	$message->size_large =  $averages->avgmsgsize * ($config->largemsg / 100);
		
	$charCount = new stdClass();
	$charCount = $DB->get_records_sql('SELECT userid, coalesce(Schar,0) as Schar, coalesce(Mchar,0) as Mchar, coalesce(Lchar,0) as Lchar, coalesce(Tchar,0) as Tchar FROM
	(SELECT userid FROM {groups_members} WHERE groupid = '.$currentgroup.' ) t0
	LEFT JOIN
	(SELECT user_id, sum(length(message)) as Schar FROM
	{project_history_imp_detail}  t1
	LEFT JOIN {project_user_mapping} t2
	 ON t1.user = t2.skype
	 WHERE group_id = '.$currentgroup.' AND length(message) < '.$message->size_small.' 
	 GROUP BY user_id) t3
	 ON t0.userid = t3.user_id
	 LEFT JOIN
	(SELECT user_id, sum(length(message)) as Mchar FROM
	{project_history_imp_detail}  t4
	LEFT JOIN {project_user_mapping} t5
	 ON t4.user = t5.skype
	 WHERE group_id = '.$currentgroup.' AND length(message) BETWEEN '.$message->size_small.'  AND '.$message->size_large.' 
	 GROUP BY user_id) t6
	 ON t0.userid = t6.user_id
	  LEFT JOIN
	(SELECT user_id, sum(length(message)) as Lchar FROM
	{project_history_imp_detail}  t7
	LEFT JOIN {project_user_mapping} t8
	 ON t7.user = t8.skype
	 WHERE group_id = '.$currentgroup.' AND length(message) > '.$message->size_large.' 
	 GROUP BY user_id) t9
	 ON t0.userid = t9.user_id
	  LEFT JOIN
	(SELECT user_id, sum(length(message)) as Tchar FROM
	{project_history_imp_detail}  t10
	LEFT JOIN {project_user_mapping} t11
	 ON t10.user = t11.skype
	 WHERE group_id = '.$currentgroup.' AND length(message) >= '.$message->size_small.' 
	 GROUP BY user_id) t12
	 ON t0.userid = t12.user_id');
	 
	$stats = new stdClass();
	$stats = $DB->get_record_sql('SELECT sum(coalesce(t3.msgchars,0)) as sum
		FROM
		(SELECT user_id, coalesce(length(message),0) as msgchars FROM
		(SELECT userid FROM {groups_members} WHERE groupid = '.$currentgroup.') t0
		 LEFT JOIN {project_user_mapping}  t1
		ON t0.userid = t1.user_id
		LEFT JOIN {project_history_imp_detail} t2
		 ON t2.user = t1.skype
		 WHERE group_id = '.$currentgroup.'
		 ) t3');
		
		//Get number of group members
		$numMembers = $DB->count_records('groups_members', array('groupid'=>$currentgroup));
		
		$stats->avg = $stats->sum/$numMembers;
	$stats->low = $stats->avg * ($config->lowthreshold / 100);
	$stats->high = $stats->avg * ($config->highthreshold / 100);
		
		$low_participators = array();
		foreach($charCount as $member) {
			if($member->tchar < $stats->low) {
				$member->alert = "Low";
				$low_participators[$member->userid] = studentidToName($member->userid);	
			} else if($member->tchar > $stats->high)
				$member->alert = "High";
			else
				$member->alert = null;
		}
			
	if(!empty($charCount[$USER->id]->alert)){
		$msg = "";
		if($charCount[$USER->id]->alert == "High"){
			if($alerts->import_alert+($config->highimportalertsfreq*60*60*24) > time())
				return;

			$msg .= $USER->firstname.", you are contributing really a lot to the Skype conversations! This is great!<br /><br />
			However, for the group project to be successful, it would be important to get other members' ideas, concerns and thoughts as well. <br /><br />
You would be a good leader for your group! Try to improve your leadership skills by trying to incorporate others in the discussion, especially ";
$msg .= rtrim(displayUsersAsText($low_participators),", ");
$msg .= " who is not participating as much!";
$alert_icon = "info.png";

			/*$msg .= $USER->firstname.", You are a high participator in the Skype conversations.<br /><br />We have determined you are rather active in the imported Skype chats.<br /><br />
			As an additional challenge, try incorporating others in to the discussion, especially ";
			$msg .= displayUsersAsText($low_participators);
			$msg .= " who's not participating as much during the Skype chats.";*/
		}
		else{
			if($alerts->import_alert+($config->lowimportalertsfreq*60*60*24) > time())
				return;
			//$msg = $USER->firstname.", You are a low participator in the Skype conversations.<br /><br />During your next Skype meeting, try posting more in-depth, detailed ideas, concepts or thoughts to expand on your posts and contribute as equally as others.";
			$msg = $USER->firstname.", it seems that you have not contributed much to the Skype conversations so far. <br /><br />
			For the group project to be successful, it is important that each team member contributes to the discussions.<br /><br />
			Try to share some ideas, concerns or thoughts with your team members in the next Skype meeting!";
			$alert_icon = "alert_icon.png";

			}
		$alerts_controller=new alerts_controller();
		$header=$charCount[$USER->id]->alert.' Skype Participation Alert!';
		$content="You are attempting to go back in the quiz too many times. This is not allowed";
		$alerts_controller->create_interruptive_notification_alert($USER->id, $COURSE->id,0, $header, $msg);

		/*$html = '<div id="dialog-message" title="'.$charCount[$USER->id]->alert.' Skype Participation Alert!">
			  <p>
				<span  style="float:left; margin:0 7px 10px 0;"><img src="'.$CFG->wwwroot.'/mod/project/pix/alert_icon.png" width="32px" height="32px" /></span>
				'.$msg.'
			  </p>
			</div>
			<script>
			  $(function() {
				$( "#dialog-message" ).dialog({
				  modal: true,
				  width: 350,
				  buttons: {
					Ok: function() {
					  $( this ).dialog( "close" );
					}
				  }
				});
			  });
			  </script>';	*/
	
	add_to_log($COURSE->id, 'project', 'alert-skype', $charCount[$USER->id]->alert);
	//echo $html;
	
	$DB->set_field('project_user_mapping', 'import_alert', time(), array('user_id'=>$USER->id));
	
	}//end if alert check
}


/*Go through grades table and find new groups that have finished a course and add them to our new table.*/
function populate_completed_groups_cron(){
	global $DB,$CFG, $COURSE;;
	require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
	$log=new moodle\local\morph\Logger(array('prefix'=>"cron_"));
	$log->debug("populate_completed_groups_cron");
	
	//Run for all courses that have project 
	$all_projects = $DB->get_records('project');
	$failed = 0;
	foreach($all_projects as $project){
	//Get a list of groups already completed and added to the table.
	$completed_groups = $DB->get_records('project_completed_groups', null, null, 'group_id');

	//Get the course id for grade records
	$grade_id = $DB->get_record('grade_items', array('courseid'=>$project->course,'itemtype'=>'course'), 'id')->id;
	//Get the users who have a final grade
	$users_grade = $DB->get_records_sql('SELECT userid,finalgrade FROM {grade_grades} WHERE itemid = :itemid', array('itemid' => $grade_id));
		if(empty($users_grade)) { //If no grades exist,
			trace('No grades...exiting...');
			break;
		}
		$grades=array();
	//get the group for each user with a final grade
	foreach($users_grade as $uid=>$user){
		$group_id = $DB->get_records('groups_members', array('userid'=>$uid), null, 'groupid');
		//Iterate all the groups and build an array with group ids and student ids as keys, storing grades as values.
		foreach($group_id as $gid=>$group){
			$grades[$gid][$uid] = $user->finalgrade;
		}

	}
		$groups_avg=array();
	//get the average grade per group
	foreach($grades as $key=>$group){
	$total_grade = $average_grade = 0;
		//echo "Group: ".$key."<br />";
		foreach($group as $key2=>$gr){
			//echo $key2." ".$gr."<br />";
			$total_grade += $gr;
		}
		$groups_avg[$key] = $total_grade/count($group);
	}
	
	$new_groups = array_diff_key($groups_avg, $completed_groups);
	
	if(!empty($new_groups)) {
	
		foreach($new_groups as $group=>$grade){
			if($grade<51){
				$DB->execute('INSERT INTO {project_completed_groups} (course_id, group_id, pass, lastmodified) VALUES ('.$project->course.', '.$group.', 0, '.time().')');
				}
			else {
				$DB->execute('INSERT INTO {project_completed_groups} (course_id, group_id, pass, lastmodified) VALUES ('.$project->course.', '.$group.', 1, '.time().')');
			}
		}//end for each
	
	} //End if not empty new_groups array
		add_to_log($project->course, 'project', 'cron run', '');
	}//end for each of all courses that have a project

	//add_to_log($course->id, 'project', 'cron run', '');


	
}
function checkChatAlerts($chatuser, $courseid){
	global $DB, $CFG, $USER,$PAGE;
	require_once($CFG->dirroot."/local/morph/classes/logger/Logger.php");
	$log=new moodle\local\morph\Logger(array("prefix"=>'chat_'));
	$config = get_config('project');
	$currentgroup = $chatuser->groupid;
	sleep(1);
	/* Chat participation level analysis */
	/*OLD AVG
	$averages = $DB->get_record_sql('SELECT avg(message_char) as avgMsgSize
FROM (
    SELECT distinct A.userid, coalesce(sum(LENGTH(message)),0) as message_char FROM `mdl_groups_members` A LEFT JOIN `mdl_chat_messages` B ON A.userid = B.userid  WHERE A.groupid = '.$currentgroup.' AND system = 0
GROUP BY userid) C');*/
	$averages = $DB->get_record_sql('SELECT avg(LENGTH(message)) as avgmessage_char FROM {chat_messages_current} WHERE groupid =  '.$currentgroup.' AND system = 0')->avgmessage_char;

	$message_size = new stdClass();
	$message_size->small =  $averages*$config->smallmsg;
	$message_size->medium = $averages*1.0;
	$message_size->large =  $averages*$config->largemsg;

	$log->debug("CONFIG:".json_encode($config));

	$charCount = new stdClass();
	$query='SELECT t4.userid, coalesce(t5.Schars,0) as Schars, coalesce(t1.Mchars,0) as Mchars, coalesce(t2.Lchars,0) as Lchars, coalesce(t3.Tchars,0) as Tchars
FROM
	(SELECT userid FROM {groups_members}
     WHERE groupid = '.$currentgroup.' ) t4
LEFT JOIN
	(SELECT userid, SUM(LENGTH(message)) as Mchars
	FROM {chat_messages_current}
	WHERE groupid = '.$currentgroup.'  AND system = 0 AND LENGTH(message) BETWEEN '.$message_size->small.' AND '.$message_size->large.'
	GROUP BY userid) t1
ON t4.userid = t1.userid
LEFT JOIN
	(SELECT userid, SUM(LENGTH(message)) as Lchars
	FROM {chat_messages_current}
	WHERE groupid = '.$currentgroup.'  AND system = 0 AND LENGTH(message) >  '.$message_size->large.'
	GROUP BY userid) t2
ON t1.userid = t2.userid
LEFT JOIN
	(SELECT userid, SUM(LENGTH(message)) as Tchars
	FROM {chat_messages_current}
	WHERE groupid = '.$currentgroup.'  AND system = 0 AND LENGTH(message) > '.$message_size->small.'
	GROUP BY userid )t3
ON t1.userid = t3.userid
LEFT JOIN
	(SELECT userid, SUM(LENGTH(message)) as Schars
	FROM {chat_messages_current}
	WHERE groupid = '.$currentgroup.'  AND system = 0 AND LENGTH(message) < '.$message_size->small.'
	GROUP BY userid )t5
ON t4.userid = t5.userid';

	$charCount = $DB->get_records_sql($query);

	$stats = new stdClass();
	$stats = $DB->get_record_sql('SELECT SUM(LENGTH(message)) as Sum FROM {chat_messages_current} WHERE groupid = '.$currentgroup.'  AND system = 0 AND LENGTH(message) >  '.$message_size->small);
	$stats->avg = $stats->sum/count($charCount);
	$stats->low = $stats->avg * $config->lowthreshold;
	$stats->high = $stats->avg * $config->highthreshold;

	//create alert object
	/*$statsmsg = new stdclass();
	$statsmsg->chatid = 0;
	$statsmsg->userid = 0;
	$statsmsg->groupid = $currentgroup;
	$statsmsg->message =  'stats: avg: '.$averages.' avg by members:'.$stats->avg.' lo:'.$stats->low.' hi:'.$stats->high.' ms:'.$message_size->small.' mm:'.$message_size->medium.' ml: '.$message_size->large;
	$statsmsg->system = 1;
	$statsmsg->timestamp = time();
*/
	//insert alert message to the database
	$messageid = $DB->insert_record('chat_messages', $statsmsg);
	//$DB->insert_record('chat_messages_current', $statsmsg);
	//$log->debug("inserted into chat_messages_current:".json_encode($statsmsg));
	//Iterate through all members of a chat and find any who has a word count lower or higher than the thresholds
	$alerts_controller=new alerts_controller();
	$theme = 'bubble';
	 foreach($charCount as $member) {
		if($member->tchars < $stats->low){
			$currentuser=$DB->get_record('user',array('id'=>$member->userid));
			$member->alert = "Low";

			$header="SYSTEM ALERT!";
			$tz = get_user_timezone($currentuser->timezone);
			//$strtime = userdate(time(), get_string('strftimemessage', 'chat'), $tz);
			$content = get_string('alert-low', 'mod_project', $currentuser->firstname);
    		 $alerts_controller->create_top_panel_notification_alert($currentuser->id, $courseid,0,"error", $header, $content);



		}
		else if($member->tchars > $stats->high){
		 	$member->alert = "High";
			$currentuser=$DB->get_record('user',array('id'=>$member->userid));
			$header="SYSTEM ALERT!";
			$content = get_string('alert-high', 'mod_project', $currentuser->firstname);
   	        $alerts_controller->create_top_panel_notification_alert($currentuser->id, $courseid,0,"success", $header, $content);



		}
		else{
			$member->alert = null;
		}

	}

	return;
}



