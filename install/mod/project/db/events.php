<?php
/**
 * Created by PhpStorm.
 * User: zoran
 * Date: 04/04/16
 * Time: 2:22 PM
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\mod_chat\event\message_sent',
        'callback'  => 'mod_project_observer::process_chat_message_event'

    ),
    array(
        'eventname' => '\mod_forum\event\assessable_uploaded',
        'callback'  => 'mod_project_observer::process_forum_post_event'

    ),
    array(
        'eventname' => '\mod_chat\event\message_sent',
        'callback'  => 'mod_project_observer::process_forum_post_event'

    ),
    array(
        'eventname' => '\core\event\course_module_created',
        'callback'  => 'mod_project_observer::process_course_module_created'

    ),
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => 'mod_project_observer::process_course_module_deleted'

    ),

);