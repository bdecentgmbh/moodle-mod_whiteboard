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
 * List of whiteboard in course
 *
 * @package    mod_whiteboard
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_whiteboard\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strwhiteboard      = get_string('modulename', 'whiteboard');
$strwhiteboards     = get_string('modulenameplural', 'whiteboard');
$strname      = get_string('name');
$strintro     = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/whiteboard/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '. $strwhiteboards);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strwhiteboards);
echo $OUTPUT->header();
echo $OUTPUT->heading($strwhiteboards);

if (!$whiteboards = get_all_instances_in_course('whiteboard', $course)) {
    notice(get_string('thereareno', 'moodle', $strwhiteboards), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
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
        $printsection = '<span class="smallinfo">'.userdate($whiteboard->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        // Each whiteboard has an icon in 2.0.
        $icon = $OUTPUT->pix_icon($cm->icon, get_string('modulename', $cm->modname)) . ' ';
    }

    $class = $whiteboard->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.
    $table->data[] = array (
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($whiteboard->name)."</a>",
        format_module_intro('whiteboard', $whiteboard, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
