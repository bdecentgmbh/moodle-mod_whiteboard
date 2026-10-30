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
 * Shows a whiteboard.
 *
 * @package    mod_whiteboard
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_whiteboard\boards;
use mod_whiteboard\service;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT); // Course module ID.

[$course, $cm] = get_course_and_cm_from_cmid($id, 'whiteboard');
$whiteboard = $DB->get_record('whiteboard', ['id' => $cm->instance], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/whiteboard:view', $context);

$theatre = optional_param('full', false, PARAM_BOOL);

$PAGE->set_url('/mod/whiteboard/view.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname . ': ' . format_string($whiteboard->name, true, ['context' => $context]));
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($whiteboard);

// A board of the user's own comes first. Then, in group mode, the board of the current group; "All participants" and
// groups without a board see the board link of the whiteboard.
$userlink = boards::get_link($whiteboard->id, boards::USER, $USER->id);
$groupmode = $userlink === null ? groups_get_activity_groupmode($cm) : NOGROUPS;
$groupid = $groupmode ? (int)groups_get_activity_group($cm, true) : 0;
$grouplink = boards::get_link($whiteboard->id, boards::GROUP, $groupid);
$link = $userlink ?? $grouplink ?? (string)$whiteboard->boardurl;

// Only links of an enabled service are shown, so disabling a service hides its boards.
$service = service::find($link);
$embed = $service && $service->display == service::DISPLAY_EMBED;

// Theatre mode shows nothing but the board, its name and buttons to open it in a new tab and to exit.
$theatre = $theatre && $embed;
if ($theatre) {
    $PAGE->set_pagelayout('popup');
    if (method_exists($PAGE, 'set_show_navigation_footer')) {
        // Moodle 5.3 and later show the course navigation in a footer.
        $PAGE->set_show_navigation_footer(false);
    }
}

whiteboard_view($whiteboard, $course, $cm, $context);

echo $OUTPUT->header();

if ($groupmode && !$theatre) {
    echo groups_print_activity_menu($cm, $PAGE->url, true);
}

if ($service) {
    $plain = ['context' => $context, 'escape' => false];
    $title = format_string($whiteboard->name, true, $plain);
    if ($grouplink !== null) {
        $title = get_string('boardofgroup', 'mod_whiteboard', (object)[
            'name' => $title,
            'group' => format_string(groups_get_group_name($groupid), true, $plain),
        ]);
    }
    echo $OUTPUT->render_from_template('mod_whiteboard/whiteboard', [
        'embed' => $embed,
        'theatre' => $theatre,
        'url' => service::board_url($service, $link),
        'title' => $title,
        'theatreurl' => (new moodle_url($PAGE->url, ['full' => 1]))->out(false),
        'exiturl' => $PAGE->url->out(false),
        'newtabinfo' => get_string('newtabinfo', 'mod_whiteboard', format_string($service->name, true, $plain)),
        'allow' => service::IFRAME_ALLOW,
    ]);
} else {
    $canedit = has_capability('moodle/course:manageactivities', $context);
    echo $OUTPUT->notification(get_string($canedit ? 'unavailableedit' : 'unavailable', 'mod_whiteboard'), 'warning', false);
}

echo $OUTPUT->footer();
