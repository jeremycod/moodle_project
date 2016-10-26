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
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define all the backup steps that will be used by the backup_project_activity_task
 */

/**
 * Define the complete project structure for backup, with file and id annotations
 */
class backup_project_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $project = new backup_nested_element('project', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'legacyfiles', 'legacyfileslast',
            'display', 'displayoptions', 'revision', 'timemodified'));
           // 'name', 'intro', 'introformat', 'content', 'contentformat',
            //'legacyfiles', 'legacyfileslast', 'display', 'displayoptions',
           // 'revision', 'timemodified'));

        // Build the tree
        // (love this)

        // Define sources
        $project->set_source_table('project', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        // (none)

        // Define file annotations
        $project->annotate_files('mod_project', 'intro', null); // This file areas haven't itemid
      //  $project->annotate_files('mod_project', 'content', null); // This file areas haven't itemid

        // Return the root element (project), wrapped into standard activity structure
        return $this->prepare_activity_structure($project);
    }
}