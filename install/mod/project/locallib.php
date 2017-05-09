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
 * Private project module utility functions
 *
 * @package    mod
 * @subpackage project
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/project/lib.php");


/**
 * File browsing support class
 */
class project_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}


function project_get_editor_options($context) {
    global $CFG;
    return array('subdirs'=>1, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'changeformat'=>1, 'context'=>$context, 'noclean'=>1, 'trusttext'=>0);
}

class project_groups_selector{

    private $courseid;
    private $projectid;
    private $project_groups;
    private $project_groups_ids=array();
    public function __construct ($courseid, $projectid) {
        global $DB;
        $this->courseid=$courseid;
        $this->projectid=$projectid;
        $this->project_groups=$DB->get_records('project_group_mapping',array('course_id'=>$this->courseid,'project_id'=>$this->projectid));
        foreach($this->project_groups as $project_group){
           array_push( $this->project_groups_ids,$project_group->group_id);
        }
    }
    public function display_potential_groups(){
        global $DB;
        $groups = listGroups($this->courseid);

        $output ="";

        $output .= "<div class='userselector' id='groupselector_" . $this->projectid . "_wrapper'>";
        $output .= "<select name='addgroups[]' id='addgroups'  multiple='multiple' size='10' >";
        $output .= "  <optgroup label='Potential groups (" . count($groups) . ")'>" . "\n";
        foreach($groups as $group){
            if(in_array($group->id, $this->project_groups_ids)){
                echo "<br/>THIS ONE IS IN PROJECT:".$group->id." name:".$group->name;
            }
            $output .="<option value=".$group->id.">".$group->name." </option>";
        }

        $output .="</select></div>";


        return $output;


    }
    private function display_group($group){
        $output="";

    }
    public function display_project_groups(){

    }
    public function add_project_groups(){
        global $DB;
        $groupids = optional_param_array('addgroups', array(), PARAM_INT);
        $project=$DB->get_record("project",array("id"=>$this->projectid));
        foreach($groupids as $groupid){
            echo "GROUP:".$groupid;
            $this->add_group_to_project($groupid, $project);
        }
    }
    private function add_group_to_project($parent_groupid,$project){
        global $DB;
        $parentgroup = $DB->get_record('groups', array('id'=>$parent_groupid));
        $projectgroup=new stdClass();
        $projectgroup->courseid=$this->courseid;
        $projectgroup->name=$parentgroup->name." (".$project->name.")";
        $projectgroup->description=$parentgroup->description;
        $projectgroup->descriptionformat=$parentgroup->descriptionformat;
        $groupid=$DB->insert_record('groups', $projectgroup);
        $group = $DB->get_record('groups', array('id'=>$groupid));

        $projectgroupmapping=new stdClass();
        $projectgroupmapping->course_id=$this->courseid;
        $projectgroupmapping->project_id=$this->projectid;
        $projectgroupmapping->parent_group_id=$parent_groupid;
        $projectgroupmapping->group_id=$group->id;
       // $projectgroupmapping->disabled=filter_var($_POST["disabled"],FILTER_VALIDATE_BOOLEAN);

        $DB->insert_record('project_group_mapping',$projectgroupmapping);

        // Invalidate the grouping cache for the course
        cache_helper::invalidate_by_definition('core', 'groupdata', array(), array($this->courseid));

        // Trigger group event.
        $params = array(
            'context' => context_course::instance($this->courseid),
            'objectid' => $group->id
        );
        $event = \core\event\group_created::create($params);
        $event->add_record_snapshot('groups', $group);
        $event->trigger();


    }
    public function remove_project_group(){

    }

}