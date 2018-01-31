<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 29/01/18
 * Time: 4:19 PM
 */
/**
 * Define all the backup steps that will be used by the backup_project_activity_task
 */
/**
 * Define the complete project structure for backup, with file and id annotations
 */
class backup_project_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

         $this->log('define structure for project',backup::LOG_INFO);
        // To know if we are including userinfo
         $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
         $project = new backup_nested_element('project', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'legacyfiles', 'legacyfileslast',
            'display', 'displayoptions', 'revision', 'timemodified'));

        $project->set_source_table('project', array('id' => backup::VAR_ACTIVITYID));

        //Project tools
         $tools=new backup_nested_element('tools');
        $tool=new backup_nested_element('tool',array('id'),array('project_id','chat_id','chat_mod','forum_id','forum_mod'));

         $project->add_child($tools);
      $tools->add_child($tool);


       $tool->set_source_sql('
       SELECT * 
            FROM {project_tools}
            WHERE project_id=?',
            array(backup::VAR_PARENTID)
       );

      if($userinfo){
          //Project groups
        $groups=new backup_nested_element('groups');

          $group=new backup_nested_element('project_group_mapping',array('id'),array('course_id','project_id','group_id', 'parent_group_id','disabled','enabled'));
          $project->add_child($groups);
          $groups->add_child($group);

          $group->set_source_sql('SELECT *
          FROM {project_group_mapping}
          WHERE project_id=?',
          array(backup::VAR_PARENTID)
          );

          //Conversation history
          $history=new backup_nested_element('history');
          $history_summary=new backup_nested_element('project_history_imp_summary',array('id'),array('project_id','group_id','date','method'));
          $project->add_child($history);
          $history->add_child($history_summary);
          $history_summary->set_source_sql('SELECT *
          FROM {project_history_imp_summary}
          WHERE project_id=?',
              array(backup::VAR_PARENTID)
          );

          $history_details=new backup_nested_element('history_details');
          $history_detail=new backup_nested_element('project_history_imp_detail',array('id'),array('message_id','time','user_name','message'));
          $history_summary->add_child($history_details);
          $history_details->add_child($history_detail);
          $history_detail->set_source_sql('SELECT *
          FROM {project_history_imp_detail}
          WHERE message_id=?',
              array(backup::VAR_PARENTID)
          );

          //Project tasks
          $tasks = new backup_nested_element('tasks');
          $task = new backup_nested_element('project_task', array('id'), array(
              'project_id',"group_id","name","description","start_date","end_date","members","hours","progress"));
          $project->add_child($tasks);
          $tasks->add_child($task);
          $task->set_source_sql('
        SELECT * 
            FROM {project_task}
            WHERE project_id=?',
              array(backup::VAR_PARENTID)
          );
         //

          //Task feedback
        $feedbacks = new backup_nested_element('feedbacks');
          $feedback = new backup_nested_element('project_feedback', array('id'), array(
              'time',"task_id","student_id","comment"));
          $task->add_child($feedbacks);
          $feedbacks->add_child($feedback);
          // $project->set_source_table('project', array('id' => backup::VAR_ACTIVITYID));




          $feedback->set_source_sql('
        SELECT * 
            FROM {project_feedback}
            WHERE task_id=?',
              array(backup::VAR_PARENTID)
          );

          $task->annotate_files('mod_project',
              'attachment',
              'id');

        }

        // Define id annotations

        // Define file annotations

        // Return the root element (choice), wrapped into standard activity structure
return $project;
    }
}