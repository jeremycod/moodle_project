<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 22/03/16
 * Time: 1:26 PM
 */
defined('MOODLE_INTERNAL') || die;

function xmldb_project_uninstall() {
    global $CFG;
    require_once($CFG->dirroot . '/local/morph/adminlib.php');
    $pluginmanager = new morph_plugin_manager('activity');
    $pluginmanager->uninstall_prototype('project');
    return true;
}