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

defined('MOODLE_INTERNAL') || die;

class Python3_autotagger {

//    private $regex = array('LOC' => '/^( |\\t|\\w|\\(|\\)|:|=|\\[|\\]|\\+|\\\\|\\-|\\*)*[\\n|\\r\\n]/', 'has_lists' => '/\\[/');
    private $tag_value_finders;
    private $tags;

    /**
     * python_autotagger constructor.
     */
    public function __construct($tags)
    {
        $this->tags = $tags;

        $loc_python = new stdClass();
        $loc_python->tag_name = 'LOC';
        $loc_python->db_field = 'answer';
        $loc_python->reject_regex_list = array("/'''.*'''\\r\\n/", '/.*#.*\\r\\n/', '/(\\r\\n)[\\t| ]*\\r\\n/');
        $loc_python->accept_regex_list = array('/.*(\\r\\n)/');
        $loc_python->tag_type = 'number';
        $this->tag_value_finders = array('LOC' => $loc_python);
    }

    public function autotag($question)
    {
        $question_metatags = array();
        foreach ($this->tags as $tag) {
            $tag_value = $this->tag_value_finders[$tag];
            $db_field = $tag_value->db_field;
            $db_field_value = $question->$db_field;
            foreach ($tag_value->reject_regex_list as $reject_regex) {
                $db_field_value = preg_replace($reject_regex, "$1", $db_field_value);
            }

            $all_matches = array();
            foreach ($tag_value->accept_regex_list as $accept_regex) {
                $matches = array();
                preg_match_all($accept_regex, $db_field_value, $matches);
                $all_matches = array_merge($all_matches, $matches[1]);
            }

            if ($tag_value->tag_type == 'bool') {
                if (!empty($all_matches)) {
                    $question_metatags[$tag] = 'T';
                }
            }
            else if ($tag_value->tag_type == 'number') {
                $num_matches = count($all_matches);
                if ($num_matches != 0) {
                    $question_metatags[$tag] = $num_matches;
                }
            }
            else if ($tag_value->tag_type == 'list') {
                $question_metatags[$tag] = $all_matches;
            }
        }
        return $question_metatags;
    }
}