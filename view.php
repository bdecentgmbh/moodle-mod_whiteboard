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

/**
 * Whiteboard module version information
 *
 * @package    mod_whiteboard
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot . '/mod/whiteboard/lib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID.

if (!$cm = get_coursemodule_from_id('whiteboard', $id)) {
    throw new moodle_exception('invalidcoursemodule'); // NOTE this is invalid use of print_error, must be a lang string id.
}

$PAGE->set_url('/mod/whiteboard/view.php', array('id' => $cm->id));

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    throw new moodle_exception('invalidcourse');  // NOTE As above.
}
require_course_login($course, false, $cm);
if (!$whiteboard = $DB->get_record('whiteboard', array('id' => $cm->instance))) {
    throw new moodle_exception('course module is incorrect'); // NOTE As above.
}
$context = context_module::instance($cm->id);
require_capability('mod/whiteboard:view', $context);
$PAGE->add_body_class('limitedwidth');
$PAGE->set_title($course->shortname.': '.$whiteboard->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($whiteboard);

// Completion and trigger events.
whiteboard_view($whiteboard, $course, $cm, $context);

echo $OUTPUT->header();
echo whiteboard_view_board($whiteboard);
echo $OUTPUT->footer();
