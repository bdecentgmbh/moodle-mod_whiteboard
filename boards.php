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
 * Lists the boards of the groups or of the users of a whiteboard, and deletes them.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_whiteboard\boards;
use mod_whiteboard\service;

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID.
$mode = optional_param('mode', '', PARAM_ALPHA);
$delete = optional_param('delete', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'whiteboard');
$whiteboard = $DB->get_record('whiteboard', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/whiteboard:manageboards', $context);

// Group boards first in group mode, user boards otherwise.
$groupmode = groups_get_activity_groupmode($cm);
if ($mode !== boards::GROUP && $mode !== boards::USER) {
    $mode = $groupmode ? boards::GROUP : boards::USER;
}

$url = new moodle_url('/mod/whiteboard/boards.php', ['id' => $cm->id, 'mode' => $mode]);
$PAGE->set_url($url);
navigation_node::override_active_url(new moodle_url('/mod/whiteboard/boards.php', ['id' => $cm->id]));
$PAGE->set_title(get_string('boards', 'mod_whiteboard') . moodle_page::TITLE_SEPARATOR .
    format_string($whiteboard->name, true, ['context' => $context]));
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->set_attrs(['description' => '', 'hidecompletion' => true]);

if ($delete) {
    $board = boards::get_managed($cm, $delete);
    $returnurl = new moodle_url($url, ['mode' => boards::get_type($board)]);
    if (optional_param('confirm', false, PARAM_BOOL) && confirm_sesskey()) {
        boards::delete($board->id);
        redirect($returnurl, get_string('boarddeleted', 'mod_whiteboard'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('deleteboardconfirm', 'mod_whiteboard', boards::format_name($cm, $board)),
        new moodle_url($returnurl, ['delete' => $board->id, 'confirm' => 1, 'sesskey' => sesskey()]),
        $returnurl
    );
    echo $OUTPUT->footer();
    die;
}

$isgroup = $mode === boards::GROUP;
$services = service::get_enabled();
$table = new html_table();
$table->head = [
    get_string($isgroup ? 'group' : 'user'),
    get_string('boardurl', 'mod_whiteboard'),
    get_string('lastmodified'),
    get_string('actions'),
];
$table->attributes['class'] = 'generaltable mod_whiteboard-boards';
$table->data = [];
$hasinactive = false;
foreach (boards::get_list($cm, $mode) as $board) {
    $name = boards::format_name($cm, $board);
    if ($service = service::find($board->boardurl, $services)) {
        $link = html_writer::link(
            service::board_url($service, $board->boardurl),
            s($board->boardurl),
            ['target' => '_blank', 'rel' => 'noopener', 'class' => 'text-break']
        );
        $servicename = format_string($service->name, true, ['context' => context_system::instance()]);
        $link .= html_writer::div($servicename, 'small text-muted');
    } else {
        $link = html_writer::span(s($board->boardurl), 'text-break') .
            html_writer::div(get_string('boardnotshown', 'mod_whiteboard'), 'small text-danger');
    }
    $actions = $OUTPUT->action_icon(
        new moodle_url('/mod/whiteboard/board.php', ['id' => $cm->id, 'board' => $board->id]),
        new pix_icon('t/edit', get_string('edit'))
    ) . ' ' . $OUTPUT->action_icon(
        new moodle_url($url, ['delete' => $board->id]),
        new pix_icon('t/delete', get_string('delete'))
    );

    $row = new html_table_row([
        $board->active ? $name : $name . ' *',
        $link,
        userdate($board->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
        $actions,
    ]);
    if (!$board->active) {
        $row->attributes['class'] = 'dimmed_text';
        $hasinactive = true;
    }
    $table->data[] = $row;
}

$select = new url_select([
    (new moodle_url($url, ['mode' => boards::GROUP]))->out(false) => get_string('groupboards', 'mod_whiteboard'),
    (new moodle_url($url, ['mode' => boards::USER]))->out(false) => get_string('userboards', 'mod_whiteboard'),
], $url->out(false), null, 'mod_whiteboard_boards_select');
$select->set_label(get_string('boards', 'mod_whiteboard'), ['class' => 'accesshide']);
$choices = boards::get_choices($cm, $mode);
$addbutton = new single_button(
    new moodle_url('/mod/whiteboard/board.php', ['id' => $cm->id, 'mode' => $mode]),
    get_string($isgroup ? 'addgroupboard' : 'adduserboard', 'mod_whiteboard'),
    'get',
    single_button::BUTTON_PRIMARY,
    $choices ? [] : ['disabled' => true]
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('boards', 'mod_whiteboard'));
echo $OUTPUT->render_from_template('mod_whiteboard/boards_actions', [
    'urlselect' => $select->export_for_template($OUTPUT),
    'addbutton' => $addbutton->export_for_template($OUTPUT),
]);

echo html_writer::tag('p', get_string($isgroup ? 'groupboards_desc' : 'userboards_desc', 'mod_whiteboard'));
if ($isgroup && !$groupmode) {
    echo $OUTPUT->notification(get_string('groupboardsnogroupmode', 'mod_whiteboard'), 'warning', false);
}
if ($isgroup && !boards::get_groups($cm)) {
    echo $OUTPUT->notification(get_string('nogroups', 'mod_whiteboard'), 'info', false);
}

if ($table->data) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string($isgroup ? 'nogroupboards' : 'nouserboards', 'mod_whiteboard'), 'info', false);
}
if ($hasinactive) {
    echo html_writer::tag(
        'p',
        get_string($isgroup ? 'inactivegroupboards' : 'inactiveuserboards', 'mod_whiteboard'),
        ['class' => 'dimmed_text']
    );
}

echo $OUTPUT->footer();
