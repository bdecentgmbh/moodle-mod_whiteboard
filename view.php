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

use mod_whiteboard\service;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT); // Course module ID.

[$course, $cm] = get_course_and_cm_from_cmid($id, 'whiteboard');
$whiteboard = $DB->get_record('whiteboard', ['id' => $cm->instance], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/whiteboard:view', $context);

$PAGE->set_url('/mod/whiteboard/view.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname . ': ' . format_string($whiteboard->name, true, ['context' => $context]));
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($whiteboard);

whiteboard_view($whiteboard, $course, $cm, $context);

echo $OUTPUT->header();

// Only links of an enabled service are shown, so disabling a service hides its boards.
$service = service::find((string)$whiteboard->boardurl);
if ($service) {
    $plain = ['context' => $context, 'escape' => false];
    echo $OUTPUT->render_from_template('mod_whiteboard/whiteboard', [
        'embed' => $service->display == service::DISPLAY_EMBED,
        'url' => service::board_url($service, $whiteboard->boardurl),
        'title' => format_string($whiteboard->name, true, $plain),
        'newtabinfo' => get_string('newtabinfo', 'mod_whiteboard', format_string($service->name, true, $plain)),
        'allow' => service::IFRAME_ALLOW,
    ]);
} else {
    $canedit = has_capability('moodle/course:manageactivities', $context);
    echo $OUTPUT->notification(get_string($canedit ? 'unavailableedit' : 'unavailable', 'mod_whiteboard'), 'warning', false);
}

echo $OUTPUT->footer();
