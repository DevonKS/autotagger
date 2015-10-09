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

global $DB, $PAGE, $USER, $OUTPUT, $COURSE;

$courseid = required_param('courseid', PARAM_INT);

require_login($courseid, false);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/autotagger/view/autotagger.php');
$PAGE->set_title('Autotag Questions');
$PAGE->set_heading('Autotag Questions');
$PAGE->navbar->add('Autotag Questions', new moodle_url('/local/autotagger/view/autotagger.php', array('courseid' => $courseid)));

echo $OUTPUT->header();

$mform = new autotag_questions_form();
$formdata = array('courseid' => $courseid); // Note this can be an array or an object.
$mform->set_data($formdata);

if ($fromform = $mform->get_data()) {

    $tags_to_autotag = process_form_data($fromform);

    $autotag_specs = create_autotag_specs($DB);

    $questions = get_questions();

    foreach ($questions as $question) {
        $lang = '';
        if (property_exists($question, 'options') && property_exists($question->options, 'language')) {
            $lang = strtolower($question->options->language);

            if (strpos($lang, 'python') !== false) {
                $lang = 'python';
            }
        }

        if ($lang != '' && isset($tags_to_autotag[$lang])) {
            $tags = $tags_to_autotag[$lang];
            $question_metatags = array();
            foreach ($tags as $tag) {
                $field = $autotag_specs[$lang][$tag]['db_field'];

                if (property_exists($question, $field)) {
                    $db_field_value = $question->$field;
                } else {
                    $db_field_value = $question->options->$field;
                }


                $db_field_value = apply_replacement_regexes($autotag_specs[$lang][$tag]['replacement_list'], $db_field_value);

                $question_metatags = apply_accept_regexes($autotag_specs[$lang][$tag]['accept_regex_list'], $autotag_specs[$lang][$tag]['tag_type'], $db_field_value, $tag, $question_metatags);
            }
            autotag_question($question, $question_metatags);
        }
        autotag_question_quizzes($question);
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
        if ($value != 0 && $metatag != 'courseid') {
            $metatag = explode(',', $metatag);
            $lang = $metatag[0];
            $tag = $metatag[1];

            if (isset($tags_to_autotag[$lang])) {
                $tags_to_autotag[$lang][] = $tag;
            } else {
                $tags_to_autotag[$lang] = array($tag);
            }
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

function get_questions()
{
    global $COURSE, $DB;

    $contexts = new question_edit_contexts(context_course::instance($COURSE->id));
    $editcontexts = $contexts->having_one_edit_tab_cap('questions');
    $categoriesInContext = question_category_options($editcontexts);

    $category_ids = array();
    foreach ($categoriesInContext as $context) {
        foreach ($context as $key => $category) {
            $category_ids[] = explode(',', $key)[0];
        }
    }

    list($in, $params) = $DB->get_in_or_equal($category_ids);
    $questions = $DB->get_records_sql("SELECT *
                                      FROM {question} q
                                      WHERE category $in", $params);

    foreach ($questions as $question) {
        if (question_bank::is_qtype_installed($question->qtype)) {
            try {
                get_question_options($question, true);
            } catch (coderunner_exception $e) {

            }
        }
    }

    return $questions;
}

/**
 * @param $replace_regexes
 * @param $db_field_value
 * @return mixed
 */
function apply_replacement_regexes($replace_regexes, $db_field_value)
{
    foreach ($replace_regexes as $replace_regex) {
        $replace_regex['find'] = '/' . $replace_regex['find'] . '/sm';
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
        $accept_regex = '/' . $accept_regex . '/sm';
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
 */
function autotag_question_quizzes($question)
{
    global $DB;

    $quizzes = $DB->get_records_sql("SELECT DISTINCT q.name
                                 FROM {quiz_slots} qs, {quiz} q
                                 WHERE q.id = qs.quizid AND qs.questionid = $question->id");
    if (!empty($quizzes)) {
        $index = get_next_quiz_index($question);
        foreach ($quizzes as $quiz) {
            autotag_question($question, array("quiz[$index]" => "'" . $quiz->name . "'"), false);
            $index += 1;
        }
    }
}

function get_next_quiz_index($question)
{
    $current_index = 0;
    foreach ($question->tags as $tag) {
        if (substr($tag, 0, 5) == "meta;") {
            $meta_tag_data = explode(';', $tag);
            $meta_tag = '';
            if ($meta_tag_data[1] == 'Base64') {
                $meta_tag = base64_decode($meta_tag_data[2]);
            } else if ($meta_tag_data[1] == '') {
                $meta_tag = $meta_tag_data[2];
            }

            if ($meta_tag !== '' && strpos($meta_tag, 'quiz') !== false) {
                $index = intval(preg_split("/(\\[|\\])/", $meta_tag)[1]);
                if ($index == $current_index) {
                    $current_index = $index + 1;
                }
            }
        }
    }

    return $current_index;
}

/**
 * @param $question
 * @param $question_metatags
 * @param bool $remove_existing
 * @throws dml_missing_record_exception
 * @throws dml_multiple_records_exception
 */
function autotag_question($question, $question_metatags, $remove_existing = true)
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
        if ($value !== 0) {
            if (strpos($metatag, '[') !== false) {
                $base_64_tag = base64_encode("$value");
                $raw_name = "meta;Base64;%$base_64_tag";
                $existing_id = $DB->get_record_sql("SELECT id from  {tag} where rawname LIKE '$raw_name'");
            } else {
                $base_64_tag = base64_encode("$metatag: $value");
                $raw_name = "meta;Base64;$base_64_tag";
                $existing_id = $DB->get_record_sql("SELECT id from  {tag} where rawname = '$raw_name'");
            }

            $base_64_tag = base64_encode("$metatag: $value");
            $formatted_metatag = "meta;Base64;$base_64_tag";

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

            if ($remove_existing) {
                //Remove any old tags with this key
                $base_64_key = 'meta;Base64;' . base64_encode("$metatag: ");
                $existing_keys = $DB->get_records_sql("SELECT ti.id as taginstanceid
                                 FROM {tag} t, {tag_instance} ti
                                 WHERE t.id = ti.tagid AND
                                       itemid = $question->id AND
                                       t.rawname LIKE '$base_64_key%'");
                if ($existing_keys !== false) {
                    foreach ($existing_keys as $existing_key) {
                        $DB->delete_records('tag_instance', array('id' => $existing_key->taginstanceid));
                    }
                }
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