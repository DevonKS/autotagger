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

//defined('MOODLE_INTERNAL') || die;

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/../classes/autotag_settings_form.php');

global $COURSE, $PAGE, $OUTPUT, $DB;

$courseid = required_param('courseid', PARAM_INT);

require_login($courseid, false);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/autotagger/view/autotagger.php');
$PAGE->set_title('Autotag Questions');
$PAGE->set_heading('Autotag Questions');
$PAGE->navbar->add('Autotag Questions', new moodle_url('/local/autotagger/view/autotagger_settings.php', array('courseid' => $courseid)));

echo $OUTPUT->header();

$PAGE->requires->yui_module('moodle-local_autotagger-settings_js', 'M.local_autotagger.settings_js.init');
$mform = new autotag_settings_form();

$formdata = array('id' => $courseid); // Note this can be an array or an object.
$mform->set_data($formdata);

if ($fromform = $mform->get_data()) {
    $languages_yaml = $DB->get_records('local_autotagger');
    $languages = array();
    foreach ($languages_yaml as $language_yaml) {
        $languages[$language_yaml->id] = $language_yaml->language;
    }

    if (property_exists($fromform, 'new_langbutton')) {
        $lang = strtolower($fromform->lang_name);
        $lang_id = array_search($lang, $languages);

        $record = new stdClass();
        $record->language = $lang;
        $record->tag_values_yaml = '';

        if ($lang_id !== false) {
            $record->id = $lang_id;
            $DB->update_record('local_autotagger', $record);
        } else {
            $DB->insert_record('local_autotagger', $record, false);
        }


        $mform = new autotag_settings_form();
        $mform->set_data(array());
        $mform->display();
    } else if (property_exists($fromform, 'submitbutton')) {
        $fromform = (array)$fromform;
        unset($fromform['submitbutton']);
        unset($fromform['lang_name']);

        foreach ($fromform as $lang => $tag_value_yaml) {
            $record = new stdClass();
            $record->language = $lang;
            $record->tag_values_yaml = $tag_value_yaml;

            $lang_id = array_search($lang, $languages);
            if ($lang_id !== false) {
                $record->id = $lang_id;
                $DB->update_record('local_autotagger', $record);
            } else {
                $DB->insert_record('local_autotagger', $record, false);
            }
        }

        $mform = new autotag_settings_form();
        $mform->set_data(array());
        $mform->display();
    }
    //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.

    //Set default data (if any)
    $mform->set_data(array());
    //displays the form
    $mform->display();
}

echo $OUTPUT->footer();