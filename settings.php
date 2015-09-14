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

$ADMIN->add('root', new admin_category('local_autotagger', get_string('autotagger', 'local_autotagger')));

$ADMIN->add('local_autotagger', new admin_externalpage('autotagquestions', get_string('autotag_questions', 'local_autotagger'),
	$CFG->wwwroot."/local/autotagger/view/autotagger.php", 'moodle/site:config'));

$ADMIN->add('local_autotagger', new admin_externalpage('autotagsettings', get_string('autotag_settings', 'local_autotagger'),
    $CFG->wwwroot . "/local/autotagger/view/autotagger_settings.php", 'moodle/site:config'));
