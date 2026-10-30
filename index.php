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
 * Lists the whiteboards of a course.
 *
 * @package    mod_whiteboard
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course, true);

$event = \mod_whiteboard\event\course_module_instance_list_viewed::create([
    'context' => context_course::instance($course->id),
]);
$event->add_record_snapshot('course', $course);
$event->trigger();

// Moodle 5 lists the activities of a course on its activities overview page.
if (class_exists(\core_courseformat\activityoverviewbase::class)) {
    \core_courseformat\activityoverviewbase::redirect_to_overview_page($course->id, 'whiteboard');
}

$strwhiteboards = get_string('modulenameplural', 'mod_whiteboard');
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/whiteboard/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname . ': ' . $strwhiteboards);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strwhiteboards);

echo $OUTPUT->header();
echo $OUTPUT->heading($strwhiteboards);

if (!$whiteboards = get_all_instances_in_course('whiteboard', $course)) {
    notice(get_string('thereareno', 'moodle', $strwhiteboards), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
if ($usesections) {
    $table->head = [get_string('sectionname', 'format_' . $course->format), get_string('name'), get_string('moduleintro')];
} else {
    $table->head = [get_string('lastmodified'), get_string('name'), get_string('moduleintro')];
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($whiteboards as $whiteboard) {
    $cm = $modinfo->cms[$whiteboard->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($whiteboard->section !== $currentsection) {
            if ($whiteboard->section) {
                $printsection = get_section_name($course, $whiteboard->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $whiteboard->section;
        }
    } else {
        $printsection = html_writer::span(userdate($whiteboard->timemodified), 'smallinfo');
    }
    $link = html_writer::link(
        new moodle_url('/mod/whiteboard/view.php', ['id' => $cm->id]),
        $cm->get_formatted_name(),
        $whiteboard->visible ? [] : ['class' => 'dimmed']
    );
    $table->data[] = [$printsection, $link, format_module_intro('whiteboard', $whiteboard, $cm->id)];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
