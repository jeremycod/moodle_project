<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 25/09/17
 * Time: 10:32 AM
 */
namespace local_morph\event;
defined ('MOODLE_INTERNAL') || die();
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/morph/classes/event/morphbase.php');

/**
 * mod_chat message sent event class.
 *
 * @package    mod_chat
 * @since      Moodle 2.6
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum_post_sent extends \local_morph\event\morphbase {

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user $this->relateduserid has sent a post.";
    }

    /**
     * Return legacy log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $post=$this->get_record_snapshot('forum_posts',$this->objectid);
        return array($this->courseid, 'chat', 'talk', 'view.php?id=' . $this->context->instanceid,
            $post->discussion, $this->context->instanceid, $post->userid);
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_forum_post_sent', 'mod_project');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/forum/view.php', array('id' => $this->context->instanceid));
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['level'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'chat_messages';
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The property relateduserid must be set.');
        }
    }

}
