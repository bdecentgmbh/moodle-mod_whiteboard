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

namespace mod_whiteboard\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider of the whiteboard: the boards that teachers give to users of their own.
 *
 * The boards themselves, and what users do on them, are kept by the whiteboard service.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describes the personal data that the whiteboard stores.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('whiteboard_board', [
            'userid' => 'privacy:metadata:whiteboard_board:userid',
            'boardurl' => 'privacy:metadata:whiteboard_board:boardurl',
            'timemodified' => 'privacy:metadata:whiteboard_board:timemodified',
        ], 'privacy:metadata:whiteboard_board');
        return $collection;
    }

    /**
     * Returns the contexts of the whiteboards in which a user has a board.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = 'SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {whiteboard_board} b ON b.whiteboard = cm.instance
                 WHERE b.userid = :userid';
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'modname' => 'whiteboard', 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Adds the users who have a board in a whiteboard.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $sql = 'SELECT b.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {whiteboard_board} b ON b.whiteboard = cm.instance
                 WHERE cm.id = :cmid AND b.userid > 0';
        $userlist->add_from_sql('userid', $sql, ['modname' => 'whiteboard', 'cmid' => $context->instanceid]);
    }

    /**
     * Exports the boards of a user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $whiteboardid = self::get_whiteboard_id($context);
            if (!$whiteboardid) {
                continue;
            }
            $board = $DB->get_record('whiteboard_board', ['whiteboard' => $whiteboardid, 'userid' => $userid]);
            if (!$board) {
                continue;
            }
            $data = helper::get_context_data($context, $contextlist->get_user());
            $data->boardurl = $board->boardurl;
            $data->timemodified = transform::datetime($board->timemodified);
            writer::with_context($context)->export_data([], $data);
            helper::export_context_files($context, $contextlist->get_user());
        }
    }

    /**
     * Deletes the boards of all users of a whiteboard.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($whiteboardid = self::get_whiteboard_id($context)) {
            $DB->delete_records_select('whiteboard_board', 'whiteboard = ? AND userid > 0', [$whiteboardid]);
        }
    }

    /**
     * Deletes the boards of a user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($whiteboardid = self::get_whiteboard_id($context)) {
                $DB->delete_records('whiteboard_board', ['whiteboard' => $whiteboardid, 'userid' => $userid]);
            }
        }
    }

    /**
     * Deletes the boards of users of a whiteboard.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $whiteboardid = self::get_whiteboard_id($userlist->get_context());
        $userids = array_filter($userlist->get_userids());
        if (!$whiteboardid || !$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select(
            'whiteboard_board',
            "whiteboard = :whiteboard AND userid {$insql}",
            ['whiteboard' => $whiteboardid] + $params
        );
    }

    /**
     * Returns the id of the whiteboard of a context.
     *
     * @param \context $context
     * @return int|null
     */
    protected static function get_whiteboard_id(\context $context): ?int {
        if (!$context instanceof \context_module) {
            return null;
        }
        $cm = get_coursemodule_from_id('whiteboard', $context->instanceid);
        return $cm ? (int)$cm->instance : null;
    }
}
