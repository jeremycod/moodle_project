<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 29/01/18
 * Time: 4:19 PM
 */
require_once($CFG->dirroot . '/mod/project/backup/moodle2/backup_project_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/project/backup/moodle2/backup_project_settingslib.php'); // Because it exists (optional)

/**
 * choice backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_project_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        $this->log('define my settings called',backup::LOG_INFO);
        // No particular settings for this activity
        //$this->add_step(new backup_project_activity_structure_step('project_structure', 'project.xml'));
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
       // $this->log('define my steps',backup::LOG_INFO);
        $this->add_step(new backup_project_activity_structure_step('project_structure', 'project.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of projects
        $search="/(".$base."\/mod\/project\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@projectINDEX*$2@$', $content);

        // Link to project view by moduleid
        $search="/(".$base."\/mod\/project\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@projectVIEWBYID*$2@$', $content);

        return $content;
    }
}