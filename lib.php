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
 * Library of functions of the whiteboard that Moodle calls.
 *
 * @package    mod_whiteboard
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds a whiteboard.
 *
 * @param stdClass $whiteboard
 * @param mod_whiteboard_mod_form|null $mform
 * @return int The id of the whiteboard.
 */
function whiteboard_add_instance($whiteboard, $mform = null) {
    global $DB;
    $whiteboard->boardurl = \mod_whiteboard\service::link_from_input($whiteboard->boardurl ?? '');
    $whiteboard->timecreated = time();
    $whiteboard->timemodified = $whiteboard->timecreated;
    $whiteboard->id = $DB->insert_record('whiteboard', $whiteboard);
    $completiontimeexpected = !empty($whiteboard->completionexpected) ? $whiteboard->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $whiteboard->coursemodule,
        'whiteboard',
        $whiteboard->id,
        $completiontimeexpected
    );
    return $whiteboard->id;
}

/**
 * Updates a whiteboard.
 *
 * @param stdClass $whiteboard
 * @param mod_whiteboard_mod_form|null $mform
 * @return bool
 */
function whiteboard_update_instance($whiteboard, $mform = null) {
    global $DB;
    $whiteboard->id = $whiteboard->instance;
    $whiteboard->boardurl = \mod_whiteboard\service::link_from_input($whiteboard->boardurl ?? '');
    $whiteboard->timemodified = time();
    $DB->update_record('whiteboard', $whiteboard);
    $completiontimeexpected = !empty($whiteboard->completionexpected) ? $whiteboard->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $whiteboard->coursemodule,
        'whiteboard',
        $whiteboard->id,
        $completiontimeexpected
    );
    return true;
}

/**
 * Deletes a whiteboard.
 *
 * @param int $id
 * @return bool
 */
function whiteboard_delete_instance($id) {
    global $DB;

    if (!$whiteboard = $DB->get_record('whiteboard', ['id' => $id])) {
        return false;
    }
    if ($cm = get_coursemodule_from_instance('whiteboard', $id)) {
        \core_completion\api::update_completion_date_event($cm->id, 'whiteboard', $id, null);
    }
    $DB->delete_records('whiteboard', ['id' => $whiteboard->id]);
    return true;
}

/**
 * Returns what the whiteboard supports.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function whiteboard_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;
        default:
            return null;
    }
}

/**
 * Marks the activity as viewed (for completion) and triggers the course_module_viewed event.
 *
 * @param stdClass $whiteboard
 * @param stdClass $course
 * @param stdClass|cm_info $cm
 * @param context_module $context
 */
function whiteboard_view($whiteboard, $course, $cm, $context) {
    $event = \mod_whiteboard\event\course_module_viewed::create([
        'context' => $context,
        'objectid' => $whiteboard->id,
    ]);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('whiteboard', $whiteboard);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Returns the action of a calendar event of the whiteboard ("Expect completed on").
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_whiteboard_core_calendar_provide_event_action($event, $factory, $userid = 0) {
    global $USER;
    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['whiteboard'][$event->instance];
    $completion = new \completion_info($cm->get_course());
    $completiondata = $completion->get_data($cm, false, $userid);
    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/whiteboard/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Resets the whiteboards of a course. The boards are kept by the whiteboard service, so there is nothing to reset.
 *
 * @param stdClass $data The data submitted from the reset course form.
 * @return array Status array
 */
function whiteboard_reset_userdata($data) {
    return [];
}

/**
 * Lists the log actions that are views, for the participation report.
 *
 * @return array
 */
function whiteboard_get_view_actions() {
    return ['view', 'view all'];
}

/**
 * Lists the log actions that are posts, for the participation report.
 *
 * @return array
 */
function whiteboard_get_post_actions() {
    return ['update', 'add'];
}

/**
 * Returns the information about a whiteboard that the course page needs.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|null
 */
function whiteboard_get_coursemodule_info($coursemodule) {
    global $DB;

    $whiteboard = $DB->get_record('whiteboard', ['id' => $coursemodule->instance], 'id, name, intro, introformat');
    if (!$whiteboard) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $whiteboard->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('whiteboard', $whiteboard, $coursemodule->id, false);
    }
    return $info;
}
