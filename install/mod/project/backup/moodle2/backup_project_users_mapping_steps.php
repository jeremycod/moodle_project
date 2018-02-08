<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 02/02/18
 * Time: 4:19 PM
 */
class backup_project_users_mapping_structure_step extends   backup_structure_step {
    protected function define_structure() {
        $users = new backup_nested_element('users');
        return $users;
    }
}