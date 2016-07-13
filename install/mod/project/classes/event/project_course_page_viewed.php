<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 24/06/16
 * Time: 12:08 PM
 */



namespace local_morph\event;
defined ('MOODLE_INTERNAL') || die();
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/morph/classes/event/morphbase.php');

/**
 * Event for when a project activity is viewed.
 *
 * @package    mod_project
 * @since      Moodle 2.6
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class project_course_page_viewed extends \local_morph\event\morphbase {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['level'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'course';
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return 'User with id ' . $this->userid . ' viewed project resource with instance id ' . $this->objectid;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_projectcoursepageviewed', 'mod_project');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/project/view.php', array('id' => $this->context->instanceid));
    }

    /**
     * Replace add_to_log() statement.
     *
     * @return array of parameters to be passed to legacy add_to_log() function.
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'project', 'view', 'view.php?id=' . $this->context->instanceid, $this->objectid,
            $this->context->instanceid);
    }
}

