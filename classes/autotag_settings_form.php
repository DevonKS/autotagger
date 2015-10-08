<?php

/**
 * Created by PhpStorm.
 * User: devon
 * Date: 7/09/15
 * Time: 11:53 AM
 */

require_once("$CFG->libdir/formslib.php");

class autotag_settings_form extends moodleform
{

    protected function definition()
    {
        global $DB;

        $mform = $this->_form;

        $languages_yaml = $DB->get_records('local_autotagger');
        foreach ($languages_yaml as $language_yaml) {
            $lang = $language_yaml->language;
            $lang_ucfirst = ucfirst($language_yaml->language);
            $mform->addElement('header', $lang . 'header', $lang_ucfirst);
            $mform->addElement('textarea', $lang, $lang_ucfirst, 'wrap="virtual" rows="10" cols="50"');
            $mform->setDefault($lang, $language_yaml->tag_values_yaml);
        }

        $mform->addElement('hidden', 'lang_name', '');
        $mform->setType('lang_name', PARAM_TEXT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'new_langbutton', 'New Language');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Save Changes');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}