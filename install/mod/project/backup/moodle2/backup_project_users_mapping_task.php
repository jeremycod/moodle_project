<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 02/02/18
 * Time: 4:15 PM
 */
require_once($CFG->dirroot . '/mod/project/backup/moodle2/backup_project_users_mapping_steps.php'); // Because it exists (optional)
class backup_project_users_mapping_task extends backup_task{
    public function build(){
        echo "BACKUP PROJECT COURSE TASK";
        $this->log("BACKUP PROJECT COURSE TASK",backup::LOG_INFO);

        $this->add_step(new backup_project_users_mapping_structure_step('project_users_mapping', 'project_users_mapping.xml'));
    }
}