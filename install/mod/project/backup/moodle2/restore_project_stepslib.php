<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 30/01/18
 * Time: 4:17 PM
 */

/**
 * Structure step to restore one project activity
 */
class restore_project_activity_structure_step extends restore_activity_structure_step {


    public function after_restore(){
        global $DB;
              $project_tools=$DB->get_records_sql("SELECT * from {project_tools} WHERE forum_id<0 and project_id=:projectid",array("projectid"=>$this->get_new_parentid('project')));
        foreach($project_tools as $project_tool){

            $old_forum=$project_tool->forum_id*(-1);
            $old_chat=$project_tool->chat_id*(-1);
            $project_tool->forum_id=$this->get_mappingid('forum',$old_forum);
            $project_tool->chat_id=$this->get_mappingid('chat',$old_chat);
            $DB->update_record("project_tools",$project_tool);

        }
       //$fid =$this->get_mappingid('forum',backup::VAR_ACTIVITYID);
      //  echo "FOUND...:".$fid;

    }

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

       $paths[] = new restore_path_element('project', '/activity/project');
        $paths[] = new restore_path_element('project_tool', '/activity/project/tools/tool');
        /*  $paths[] = new restore_path_element('project_option', '/activity/project/options/option');*/

         if ($userinfo) {
             $paths[] = new restore_path_element('project_group', '/activity/project/groups/project_group_mapping');
             $paths[] = new restore_path_element('project_task', '/activity/project/tasks/project_task');
             $paths[] = new restore_path_element('project_feedback', '/activity/project/tasks/project_task/feedbacks/project_feedback');
             $paths[] = new restore_path_element('project_history_imp_summary', '/activity/project/history/project_history_imp_summary');
             $paths[] = new restore_path_element('project_history_detail', '/activity/project/history/project_history_imp_summary/history_details/project_history_imp_detail');
         }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_project($data) {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();

        // insert the project record
        $newitemid = $DB->insert_record('project', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }
    protected function process_project_tool($data){
        global $DB;
        $data = (object)$data;
        $data->project_id=$this->get_new_parentid('project');
        $oldid = $data->id;
        $oldchat=$data->chat_id;
        $data->chat_id=$data->chat_id*(-1);
        $data->forum_id= $data->forum_id*(-1);
        $newitemid = $DB->insert_record('project_tools', $data);
       // $this->set_mapping('project_tool', $oldid, $newitemid);
    }
    protected function process_project_feedback($data){
        global $DB;
        $data = (object)$data;
        $data->task_id=$this->get_mappingid("project_task", $data->task_id);
        echo "OLD USER:".$data->student_id." NEW:".$this->get_mappingid("user",$data->student_id);
        $data->student_id=$this->get_mappingid("user", $data->student_id);
        echo "STUDENT:::".json_encode($data);
       $newitemid = $DB->insert_record('project_feedback', $data);
        // $this->set_mapping('project_tool', $oldid, $newitemid);
    }
    protected function process_project_group($data){
        global $DB;
        $data = (object)$data;
        $data->course_id = $this->get_courseid();
        $data->project_id =$this->get_new_parentid('project');
        $oldgroupid=$data->group_id;

        $data->group_id = $this->get_mappingid('group', $data->group_id);
        $newitemid = $DB->insert_record('project_group_mapping', $data);
        $this->set_mapping('project_group', $oldgroupid, $data->group_id);
    }
    protected function process_project_task($data){
        global $DB;
        $data = (object)$data;
        $oldid=$data->id;
        //$data->course_id = $this->get_courseid();
        $data->project_id =$this->get_new_parentid('project');
        $data->group_id = $this->get_mappingid('project_group', $data->group_id);
        $newitemid = $DB->insert_record('project_task', $data);
        $this->set_mapping('project_task', $oldid, $newitemid,true);
    }
    protected function process_project_history_imp_summary($data){
        global $DB;
        $data = (object)$data;
        $oldid=$data->id;
        //$data->course_id = $this->get_courseid();
        $data->project_id =$this->get_new_parentid('project');
        $data->group_id = $this->get_mappingid('project_group', $data->group_id);
        $newitemid = $DB->insert_record('project_history_imp_summary', $data);
        $this->set_mapping('project_history_message', $oldid, $newitemid);
    }
    protected function process_project_history_detail($data){
        global $DB;
        $data = (object)$data;
        $oldid=$data->id;
        //$data->course_id = $this->get_courseid();

        $data->message_id = $this->get_mappingid('project_history_message', $data->message_id);
        $newitemid = $DB->insert_record('project_history_imp_detail', $data);
        //$this->set_mapping('project_history_message', $oldid, $newitemid);
    }



    protected function after_execute() {
             $this->add_related_files('mod_project',  'attachment', "project_task");
    }
}