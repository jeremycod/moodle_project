<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 24/11/16
 * Time: 4:41 PM
 */

namespace local_morph\event;
defined ('MOODLE_INTERNAL') || die();

class project_tools_created extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['level'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'project_tools';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_projecttoolscreated', 'mod_project');
    }
}