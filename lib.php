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
 * Define for lib functions.
 *
 * @package    mod_whiteboard
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Add whiteboard instance.
 * @param stdClass $whiteboard
 * @param mod_whiteboard_mod_form $mform
 * @return int new whiteboard instance id
 */
function whiteboard_add_instance($whiteboard, $mform = null) {
    global $CFG, $DB, $OUTPUT;
    $whiteboard->timecreated = time();
    $whiteboard->id = $DB->insert_record('whiteboard', $whiteboard);
    $completiontimeexpected = !empty($whiteboard->completionexpected) ? $whiteboard->completionexpected : null;
    \core_completion\api::update_completion_date_event($whiteboard->coursemodule,
        'whiteboard', $whiteboard->id, $completiontimeexpected);
    return $whiteboard->id;
}

/**
 * Update page instance.
 * @param stdClass $whiteboard
 * @param mod_whiteboard_mod_form $mform
 * @return bool true
 */
function whiteboard_update_instance($whiteboard, $mform) {
    global $CFG, $DB;
    $whiteboard->id = $whiteboard->instance;
    $whiteboard->timemodified = time();
    $DB->update_record('whiteboard', $whiteboard);
    $completiontimeexpected = !empty($whiteboard->completionexpected) ? $whiteboard->completionexpected : null;
    \core_completion\api::update_completion_date_event($whiteboard->coursemodule,
        'whiteboard', $whiteboard->id, $completiontimeexpected);
    return true;

}

/**
 * Delete page instance.
 * @param int $id
 * @return bool true
 */
function whiteboard_delete_instance($id) {
    global $CFG, $DB;

    if (!$whiteboard = $DB->get_record('whiteboard', array('id' => $id))) {
        return false;
    }
    $cm = get_coursemodule_from_instance('whiteboard', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'whiteboard', $id, null);
    $DB->delete_records('whiteboard', array('id' => $whiteboard->id));
    return true;
}

/**
 * View the whiteboard
 * @param stdClass $whiteboard
 * @return string
 */
function whiteboard_view_board($whiteboard) {
    global $CFG, $DB, $OUTPUT;
    $urlmiro = "https://miro.com/app/live-embed";
    $urlconceptboard = " https://app.conceptboard.com/board/";
    $boardid = $whiteboard->boardid;

    if ($whiteboard->boardtype == 'miro') {
        $url = $urlmiro ."/". $boardid;
    } else {
        $url = $urlconceptboard ."/". $boardid;
    }

    $templatecontext = [
            'url' => $url,
    ];
    return $OUTPUT->render_from_template('mod_whiteboard/whiteboard', $templatecontext);
}

/**
 * List of features supported in Whiteboard module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function whiteboard_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $whiteboard whiteboard object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function whiteboard_view($whiteboard, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $whiteboard->id
    );

    $event = \mod_whiteboard\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('whiteboard', $whiteboard);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}


/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param object $event
 * @param object $factory
 * @param int $userid
 * @return object|null
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
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function whiteboard_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return array();
}


/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function whiteboard_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function whiteboard_get_post_actions() {
    return array('update', 'add');
}


/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@see course_modinfo::get_array_of_activities()}
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function whiteboard_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    if ($whiteboard = $DB->get_record('whiteboard', array('id' => $coursemodule->instance),
        'id, name, intro, introformat', 'boardtype', 'boardid')) {
        if (empty($whiteboard->name)) {
            // Label name missing, fix it.
            $whiteboard->name = "whiteboard{$whiteboard->id}";
            $DB->set_field('whiteboard', 'name', $whiteboard->name, array('id' => $whiteboard->id));
        }
        $info = new cached_cm_info();
        $info->name  = $whiteboard->name;
        return $info;
    } else {
        return null;
    }
}





