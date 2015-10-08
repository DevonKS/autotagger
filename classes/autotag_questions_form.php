<?php

/**
 * Created by PhpStorm.
 * User: devon
 * Date: 11/09/15
 * Time: 11:02 AM
 */

require_once("$CFG->libdir/formslib.php");

//include_once($CFG->dirroot . '/local/autotagger/yaml_parser/Spyc.php');

class autotag_questions_form extends moodleform
{

    protected function definition()
    {
        global $DB;

        $mform = $this->_form;

        $languages_yaml = $DB->get_records('local_autotagger');
        $checkbox_group = 1;
        foreach ($languages_yaml as $language_yaml) {
            $lang = $language_yaml->language;
            $metatags = array_keys(spyc_load($language_yaml->tag_values_yaml));

            $mform->addElement('header', $lang . 'header', ucfirst($lang));
            foreach ($metatags as $metatag) {
                $mform->addElement('advcheckbox', $lang . ',' . $metatag, ucfirst($metatag), '',
                    array('group' => $checkbox_group, 'class' => $lang . 'checkbox', 'value' => $metatag));
            }
            $this->add_checkbox_controller($checkbox_group);
            $checkbox_group += 1;
        }

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(false, 'Autotag Questions');
    }
}