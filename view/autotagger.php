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
require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__ . '/../classes/autotag_questions_form.php');

admin_externalpage_setup('autotagquestions');

echo $OUTPUT->header();

$mform = new autotag_questions_form();

if ($fromform = $mform->get_data()) {
    global $DB;

    $fromform = (array)$fromform;
    unset($fromform['checkbox_controller1']);
    unset($fromform['checkbox_controller2']);
    unset($fromform['submitbutton']);


    $languages_yaml = $DB->get_records('local_autotagger');
    $autotag_specs = array();
    foreach ($languages_yaml as $language_yaml) {
        $yaml = spyc_load($language_yaml->tag_values_yaml);
        $autotag_specs[$language_yaml->language] = $yaml;
    }

    foreach ($fromform as $metatag => $value) {
        $metatag = explode(',', $metatag);
        $lang = $metatag[0];
        $tag = $metatag[1];

        $questions = $DB->get_records_sql("SELECT *
                                   FROM {question} q, {question_coderunner_options} cq
                                   WHERE q.id = cq.questionid AND cq.coderunnertype LIKE '%$lang%'");

        foreach ($questions as $question) {
            $question_metatags = array();
            $field = $autotag_specs[$lang][$tag]['db_field'];
            $db_field_value = $question->$field;
            echo '<pre>';
            var_dump(ctype_print($autotag_specs[$lang][$tag]['replacement_list'][2]['find']));
            echo '</pre><br>';
            echo '<pre>';
            var_dump($autotag_specs[$lang][$tag]['replacement_list'][2]);
            echo '</pre><br>';
            echo '<pre>';
            var_dump($db_field_value);
            echo '</pre><br>';

            $replace_regexes = $autotag_specs[$lang][$tag]['replacement_list'];
            foreach ($replace_regexes as $replace_regex) {
                $replace_regex['find'] = '/' . $replace_regex['find'] . '/';
                $db_field_value = preg_replace($replace_regex['find'], $replace_regex['replace'], $db_field_value);
            }

            echo '<pre>';
            var_dump($db_field_value);
            echo '</pre><br><br>';

            $accept_regexes = $autotag_specs[$lang][$tag]['accept_regex_list'];

            $matches = array();
            foreach ($accept_regexes as $accept_regex) {
                $temp_matches = array();
                $accept_regex = '/' . $accept_regex . '/';
                preg_match_all($accept_regex, $db_field_value, $temp_matches);
                if (!empty($matches[0])) {
                    if (isset($matches[1])) {
                        $matches = array_merge($matches, $temp_matches[1]);
                    } else {
                        $matches = array_merge($matches, $temp_matches[0]);
                    }
                }
            }

            if ($autotag_specs[$lang][$tag]['tag_type'] == 'bool') {
                if (!empty($matches)) {
                    $question_metatags[$tag] = 'T';
                } else {
                    $question_metatags[$tag] = 'F';
                }
            } else if ($autotag_specs[$lang][$tag]['tag_type'] == 'number') {
                $num_matches = count($matches);
                $question_metatags[$tag] = $num_matches;
            }

            $ordering = $DB->get_record_sql("SELECT MAX(ordering) as max from {tag_instance} WHERE itemid = $question->id");
            $next_ordering = $ordering->max;
            if ($next_ordering == null) {
                $next_ordering = 0;
            } else {
                $next_ordering += 1;
            }

            foreach ($question_metatags as $metatag => $value) {
                if ($value != 0) {
                    $base_64_tag = base64_encode("$metatag: $value");
                    $formatted_metatag = "meta;Base64;$base_64_tag";
                    $existing_id = $DB->get_record_sql("SELECT id from  {tag} where name = '$formatted_metatag' AND rawname = '$formatted_metatag'");
                    if ($existing_id === false) {
                        $tag = new stdClass();
                        $tag->userid = 2;
                        $tag->name = strtolower($formatted_metatag);
                        $tag->rawname = $formatted_metatag;
                        $tag->tagtype = 'default';
                        $tag->timemodified = time();
//                        $existing_id = $DB->insert_record('tag', $tag);
                        echo '<pre>';
                        var_dump($tag);
                        echo '</pre><br><br>';
                    }

                    $tag_id = intval($existing_id->id);
                    $existing_link = $DB->get_record_sql("SELECT id FROM {tag_instance} WHERE itemid = $question->id AND tagid = $tag_id");
                    if ($existing_link === false) {
                        $tag_instance = new stdClass();
                        $tag_instance->itemtype = 'question';
                        $tag_instance->itemid = $question->id;
                        $tag_instance->tagid = $tag_id;
                        $tag_instance->component = 'core_question';
                        $tag_instance->contextid = 2;
                        $tag_instance->timecreated = time();
                        $tag_instance->timemodified = time();
                        $tag_instance->ordering = $next_ordering;
//                        $DB->insert_record('tag_instance', $tag_instance);
                        echo '<pre>';
                        var_dump($tag_instance);
                        echo '</pre><br><br>';
                        $next_ordering += 1;
                    }
                }
            }
        }
    }
} else {
    //Set default data (if any)
    $mform->set_data(array());
    //displays the form
    $mform->display();
}

echo $OUTPUT->footer();

