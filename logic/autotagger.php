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

error_reporting(E_ALL);
ini_set('display_errors', 1);

//defined('MOODLE_INTERNAL') || die;
require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
include_once($CFG->dirroot . '/local/autotagger/classes/autotagger.php');
//admin_externalpage_setup('autotagquestions');
global $DB;

$langs = array();
if (isset($_POST['lang'])) {
    $langs = $_POST['lang'];
}

$tags = array();
if (isset($_POST['tag'])){
    $tags = $_POST['tag'];
}

foreach ($langs as $lang) {
    $questions = $DB->get_records_sql("SELECT *
                                   FROM {question} q, {question_coderunner_options} cq
                                   WHERE q.id = cq.questionid AND cq.coderunnertype = '$lang'");
    $autotagger = new Autotagger($tags);
    foreach ($questions as $question) {
        $metatags = $autotagger->autotag($question);
        echo '<pre>';
        var_dump($metatags);
        echo '</pre><br>';
        $ordering = $DB->get_record_sql("SELECT MAX(ordering) as max from {tag_instance} WHERE itemid = $question->id");
        $next_ordering = $ordering->max;
        if ($next_ordering == null) {
            $next_ordering = 0;
        } else {
            $next_ordering += 1;
        }
        foreach ($metatags as $metatag => $value) {
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
                $DB->insert_record('tag', $tag);
                    $existing_id = $DB->get_record_sql("SELECT id FROM {tag} WHERE name = '$formatted_metatag' AND rawname = '$formatted_metatag'");
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
                    $DB->insert_record('tag_instance', $tag_instance);
                    $next_ordering += 1;
                }
            }
        }
    }
}

echo '<h1>DONE</h1><br>';
header('Location: '.'http://localhost/moodle/local/autotagger/view/autotagger.php');