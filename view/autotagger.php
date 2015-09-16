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

    $tags_to_autotag = process_form_data($fromform);

    $autotag_specs = create_autotag_specs($DB);

    foreach ($tags_to_autotag as $lang => $tags) {

        $questions = $DB->get_records_sql("SELECT *
                                   FROM {question} q, {question_coderunner_options} cq
                                   WHERE q.id = cq.questionid AND cq.coderunnertype LIKE '%$lang%'");

        foreach ($questions as $question) {
            $question_metatags = array();
            foreach ($tags as $tag) {
                $field = $autotag_specs[$lang][$tag]['db_field'];
                $db_field_value = $question->$field;

                $db_field_value = apply_replacement_regexes($autotag_specs[$lang][$tag]['replacement_list'], $db_field_value);

                $question_metatags = apply_accept_regexes($autotag_specs[$lang][$tag]['accept_regex_list'], $autotag_specs[$lang][$tag]['tag_type'], $db_field_value, $tag, $question_metatags);
            }

            autotag_question($question, $question_metatags);
        }
    }

    echo "<h1>Autotagging Complete</h1>";
} else {
    //Set default data (if any)
    $mform->set_data(array());
    //displays the form
    $mform->display();
}

echo $OUTPUT->footer();


/**
 * @param $fromform
 * @return array
 */
function process_form_data($fromform)
{
    $fromform = (array)$fromform;
    unset($fromform['checkbox_controller1']);
    unset($fromform['checkbox_controller2']);
    unset($fromform['submitbutton']);

    $tags_to_autotag = array();
    foreach ($fromform as $metatag => $value) {
        $metatag = explode(',', $metatag);
        $lang = $metatag[0];
        $tag = $metatag[1];

        if (isset($tags_to_autotag[$lang])) {
            $tags_to_autotag[$lang][] = $tag;
        } else {
            $tags_to_autotag[$lang] = array($tag);
        }
    }
    return $tags_to_autotag;
}

/**
 * @param $DB
 * @return array
 */
function create_autotag_specs($DB)
{
    global $DB;

    $languages_yaml = $DB->get_records('local_autotagger');
    $autotag_specs = array();
    foreach ($languages_yaml as $language_yaml) {
        $yaml = yaml_parse($language_yaml->tag_values_yaml);
        $autotag_specs[$language_yaml->language] = $yaml;
    }
    return $autotag_specs;
}

/**
 * @param $replace_regexes
 * @param $db_field_value
 * @return mixed
 */
function apply_replacement_regexes($replace_regexes, $db_field_value)
{
    foreach ($replace_regexes as $replace_regex) {
        $replace_regex['find'] = '/' . $replace_regex['find'] . '/';
        $db_field_value = preg_replace($replace_regex['find'], $replace_regex['replace'], $db_field_value);
    }

    return $db_field_value;
}

/**
 * @param $accept_regexes
 * @param $tag_type
 * @param $db_field_value
 * @param $tag
 * @param $question_metatags
 * @return mixed
 */
function apply_accept_regexes($accept_regexes, $tag_type, $db_field_value, $tag, $question_metatags)
{
    $matches = array();

    foreach ($accept_regexes as $accept_regex) {
        $temp_matches = array();
        $accept_regex = '/' . $accept_regex . '/';
        preg_match_all($accept_regex, $db_field_value, $temp_matches);
        if (!empty($temp_matches[0])) {
            if (isset($matches[1])) {
                $matches = array_merge($matches, $temp_matches[1]);
            } else {
                $matches = array_merge($matches, $temp_matches[0]);
            }
        }
    }

    if ($tag_type == 'bool') {
        if (!empty($matches)) {
            $question_metatags[$tag] = 'T';
            return $question_metatags;
        } else {
            $question_metatags[$tag] = 'F';
            return $question_metatags;
        }
    } else if ($tag_type == 'number') {
        $num_matches = count($matches);
        $question_metatags[$tag] = $num_matches;
        return $question_metatags;
    }

    return $question_metatags;
}

/**
 * @param $question
 * @param $question_metatags
 */
function autotag_question($question, $question_metatags)
{
    global $DB;

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
            $tag_id = -1;
            if ($existing_id === false) {
                $tag_object = new stdClass();
                $tag_object->userid = 2;
                $tag_object->name = strtolower($formatted_metatag);
                $tag_object->rawname = $formatted_metatag;
                $tag_object->tagtype = 'default';
                $tag_object->timemodified = time();
                $tag_id = $DB->insert_record('tag', $tag_object);
            } else {
                $tag_id = intval($existing_id->id);
            }


            $existing_link = $DB->get_record_sql("SELECT id FROM {tag_instance} WHERE itemid = $question->id AND tagid = $tag_id");
            if ($existing_link === false) {
                $tag_instance_object = new stdClass();
                $tag_instance_object->itemtype = 'question';
                $tag_instance_object->itemid = $question->id;
                $tag_instance_object->tagid = $tag_id;
                $tag_instance_object->component = 'core_question';
                $tag_instance_object->contextid = 2;
                $tag_instance_object->timecreated = time();
                $tag_instance_object->timemodified = time();
                $tag_instance_object->ordering = $next_ordering;
                $DB->insert_record('tag_instance', $tag_instance_object);
                $next_ordering += 1;
            }
        }
    }
}