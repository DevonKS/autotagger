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

admin_externalpage_setup('autotagquestions');

echo $OUTPUT->header();

echo '<form action="../logic/autotagger.php" method="POST">';
echo '<h4>Choose Languages</h4>';
echo '<input type="checkbox" name="lang[]" value="Python3" id="python3"/>';
echo '<label for="python3">Python3</label><br>';

echo '<h4>Choose Tags</h4>';
echo '<input type="checkbox" name="tag[]" value="LOC" id="loc"/>';
echo '<label for="loc">LOC</label><br>';
echo '<input type="checkbox" name="tag[]" value="Num For Loops,Num While Loops" id="loops"/>';
echo '<label for="loops">Loops</label><br>';
echo '<input type="submit" value="Autotag"/>';
echo '</form>';

echo $OUTPUT->footer();

