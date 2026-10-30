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
 * Adds or edits the board of a group or user.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_whiteboard\boards;
use mod_whiteboard\form\board_form;

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID.
$boardid = optional_param('board', 0, PARAM_INT);
$mode = optional_param('mode', '', PARAM_ALPHA) === boards::USER ? boards::USER : boards::GROUP;

[$course, $cm] = get_course_and_cm_from_cmid($id, 'whiteboard');
$whiteboard = $DB->get_record('whiteboard', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/whiteboard:manageboards', $context);

$board = $boardid ? boards::get_managed($cm, $boardid) : null;
if ($board) {
    $mode = boards::get_type($board);
    $url = new moodle_url('/mod/whiteboard/board.php', ['id' => $cm->id, 'board' => $board->id]);
    $title = get_string('boardof', 'mod_whiteboard', boards::format_name($cm, $board));
    $customdata = ['type' => $mode, 'name' => boards::format_name($cm, $board)];
} else {
    $url = new moodle_url('/mod/whiteboard/board.php', ['id' => $cm->id, 'mode' => $mode]);
    $title = get_string($mode === boards::GROUP ? 'addgroupboard' : 'adduserboard', 'mod_whiteboard');
    $customdata = ['type' => $mode, 'choices' => boards::get_choices($cm, $mode)];
}
$returnurl = new moodle_url('/mod/whiteboard/boards.php', ['id' => $cm->id, 'mode' => $mode]);
if (!$board && !$customdata['choices']) {
    // Every group or user has a board already.
    redirect($returnurl);
}

$PAGE->set_url($url);
navigation_node::override_active_url(new moodle_url('/mod/whiteboard/boards.php', ['id' => $cm->id]));
$PAGE->set_title(strip_tags($title) . moodle_page::TITLE_SEPARATOR .
    format_string($whiteboard->name, true, ['context' => $context]));
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->set_attrs(['description' => '', 'hidecompletion' => true]);

$form = new board_form($url, $customdata);
if ($board) {
    $form->set_data(['boardurl' => $board->boardurl]);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    $ownerid = $board ? ($board->groupid ?: $board->userid) : $data->ownerid;
    boards::save($whiteboard->id, $mode, (int)$ownerid, $data->boardurl);
    if (!empty($data->againbutton)) {
        $returnurl = new moodle_url('/mod/whiteboard/board.php', ['id' => $cm->id, 'mode' => $mode]);
    }
    redirect($returnurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$form->display();
echo $OUTPUT->footer();
