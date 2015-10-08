<?php
/**
 * Created by PhpStorm.
 * User: devon
 * Date: 24/09/15
 * Time: 10:10 AM
 */


function local_autotagger_extend_settings_navigation(navigation_node $settingsnav, context $context)
{
    global $COURSE;

    if ($coursenode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $autotaggernode = $coursenode->add('Autotagger', null, navigation_node::TYPE_CONTAINER);

        $courseid = $COURSE->id;

        $autotaggernode->add('Autotag Questions',
            new moodle_url('/local/autotagger/view/autotagger.php', array('courseid' => $courseid)),
            navigation_node::TYPE_SETTING);
        $autotaggernode->add('Autotag Settings',
            new moodle_url('/local/autotagger/view/autotagger_settings.php', array('courseid' => $courseid)),
            navigation_node::TYPE_SETTING);

    }
}