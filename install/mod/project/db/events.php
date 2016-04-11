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

    )
);