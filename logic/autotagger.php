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
include_once($CFG->dirroot . '/local/autotagger/classes/python3_autotagger.php');
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
    $autotagger_classname = $lang.'_autotagger';
    $autotagger = new $autotagger_classname($tags);
    foreach ($questions as $question) {
        $metatags = $autotagger->autotag($question);
        echo '<pre>';
        var_dump($metatags);
        echo '</pre><br>';
        foreach ($metatags as $metatag => $value) {
            if ($value != 0) {
                $tag = new stdClass();
                $tag->userid = 2;
                $tag->name = "meta;;$metatag: $value";
                $tag->rawname = $tag->name;
                $DB->insert_record('tag', $tag);

                $tag = $DB->get_record_sql("SELECT id FROM {tag} WHERE name = 'meta;;$metatag: $value' AND rawname = 'meta;;$metatag: $value'");
                $tag_id = intval($tag->id);
                $tag_instance = new stdClass();
                $tag_instance->itemtype = 'question';
                $tag_instance->itemid = $question->id;
                $tag_instance->tagid = $tag_id;
                $DB->insert_record('tag_instance', $tag_instance);
            }
        }
    }
}

echo '<h1>DONE</h1><br>';
header('Location: '.'http://localhost/moodle/local/autotagger/view/autotagger.php');