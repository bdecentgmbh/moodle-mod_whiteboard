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

namespace mod_whiteboard;

use cm_info;
use stdClass;

/**
 * Boards of groups and of users, which teachers add on the "Boards" page of a whiteboard.
 *
 * A user's own board comes first. In group mode, the board of the user's current group comes next. Everybody else sees
 * the board link of the whiteboard. A board has either a groupid or a userid; the other one is 0.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class boards {
    /** @var string Boards of groups. */
    const GROUP = 'group';

    /** @var string Boards of users. */
    const USER = 'user';

    /**
     * Returns a board of a whiteboard that the current user can manage, with the name of its group or user.
     *
     * @param cm_info $cm
     * @param int $id
     * @return stdClass
     * @throws \moodle_exception If there is no such board, or the current user cannot manage it.
     */
    public static function get_managed(cm_info $cm, int $id): stdClass {
        global $DB;
        $board = $DB->get_record('whiteboard_board', ['id' => $id, 'whiteboard' => $cm->instance], '*', MUST_EXIST);
        $list = self::get_list($cm, self::get_type($board));
        if (!isset($list[$id])) {
            throw new \moodle_exception('nopermissions', 'error', '', get_string('whiteboard:manageboards', 'mod_whiteboard'));
        }
        return $list[$id];
    }

    /**
     * Returns the name of the group or user of a board from get_list(), for HTML.
     *
     * @param cm_info $cm
     * @param stdClass $board
     * @return string
     */
    public static function format_name(cm_info $cm, stdClass $board): string {
        return $board->groupid ? format_string($board->name, true, ['context' => $cm->context]) : s($board->name);
    }

    /**
     * Whether a board is the board of a group or of a user.
     *
     * @param stdClass $board
     * @return string self::GROUP or self::USER
     */
    public static function get_type(stdClass $board): string {
        return $board->groupid ? self::GROUP : self::USER;
    }

    /**
     * Returns the board link of a group or user.
     *
     * @param int $whiteboardid
     * @param string $type self::GROUP or self::USER
     * @param int $id The id of the group or user.
     * @return string|null The link, null if the group or user has no board of its own.
     */
    public static function get_link(int $whiteboardid, string $type, int $id): ?string {
        global $DB;
        if (!$id) {
            return null;
        }
        $link = $DB->get_field('whiteboard_board', 'boardurl', ['whiteboard' => $whiteboardid] + self::owner($type, $id));
        return $link === false ? null : $link;
    }

    /**
     * Returns the board links of the groups or users of a whiteboard.
     *
     * @param int $whiteboardid
     * @param string $type self::GROUP or self::USER
     * @return string[] Links by group or user id.
     */
    public static function get_links(int $whiteboardid, string $type): array {
        global $DB;
        $field = self::field($type);
        return $DB->get_records_select_menu(
            'whiteboard_board',
            "whiteboard = ? AND {$field} > 0",
            [$whiteboardid],
            $field,
            "{$field}, boardurl"
        );
    }

    /**
     * Returns the boards of the groups or users of a whiteboard that the current user can manage, sorted by name.
     *
     * @param cm_info $cm
     * @param string $type self::GROUP or self::USER
     * @return stdClass[] Boards by id, with the name of their group or user and whether it is shown (active).
     */
    public static function get_list(cm_info $cm, string $type): array {
        global $DB;
        $all = self::can_access_all_groups($cm);
        $groups = self::get_groups($cm);

        if (self::field($type) === 'groupid') {
            $sql = 'SELECT b.*, g.name
                      FROM {whiteboard_board} b
                      JOIN {groups} g ON g.id = b.groupid
                     WHERE b.whiteboard = :whiteboard
                  ORDER BY g.name, g.id';
            $boards = $DB->get_records_sql($sql, ['whiteboard' => $cm->instance]);
            foreach ($boards as $id => $board) {
                // Groups that are not in the grouping of the whiteboard, or do not take part in activities, never see it.
                $board->active = isset($groups[$board->groupid]);
                if (!$board->active && !$all) {
                    unset($boards[$id]);
                }
            }
            return $boards;
        }

        $params = ['whiteboard' => $cm->instance];
        $ingroups = '';
        if (!$all) {
            // Without access to all groups in separate groups, only the users of one's own groups.
            if (!$groups) {
                return [];
            }
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($groups), SQL_PARAMS_NAMED);
            $ingroups = "AND EXISTS (SELECT 1 FROM {groups_members} gm WHERE gm.userid = b.userid AND gm.groupid {$insql})";
            $params += $inparams;
        }
        $namefields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        [$sort, $sortparams] = users_order_by_sql('u');
        $sql = "SELECT b.*, {$namefields}
                  FROM {whiteboard_board} b
                  JOIN {user} u ON u.id = b.userid AND u.deleted = 0
                 WHERE b.whiteboard = :whiteboard {$ingroups}
              ORDER BY {$sort}";
        $boards = $DB->get_records_sql($sql, $params + $sortparams);
        foreach ($boards as $board) {
            $board->name = fullname($board);
            $board->active = is_enrolled($cm->context, $board->userid, 'mod/whiteboard:view', true);
        }
        return $boards;
    }

    /**
     * Returns the groups or users that the current user can add a board for: those that have none yet.
     *
     * @param cm_info $cm
     * @param string $type self::GROUP or self::USER
     * @return string[] Names by group or user id.
     */
    public static function get_choices(cm_info $cm, string $type): array {
        $choices = [];
        if (self::field($type) === 'groupid') {
            foreach (self::get_groups($cm) as $group) {
                $choices[$group->id] = format_string($group->name, true, ['context' => $cm->context]);
            }
        } else {
            $showemail = in_array('email', \core_user\fields::get_identity_fields($cm->context, false));
            foreach (self::get_users($cm) as $user) {
                $choices[$user->id] = $showemail ? fullname($user) . ', ' . $user->email : fullname($user);
            }
        }
        return array_diff_key($choices, self::get_links($cm->instance, $type));
    }

    /**
     * Whether the current user can manage the boards of all groups and users, or only those of their own groups.
     *
     * @param cm_info $cm
     * @return bool
     */
    public static function can_access_all_groups(cm_info $cm): bool {
        return groups_get_activity_groupmode($cm) == NOGROUPS || has_capability('moodle/site:accessallgroups', $cm->context);
    }

    /**
     * Returns the groups that can see a board of their own, and whose boards the current user can manage.
     *
     * @param cm_info $cm
     * @return stdClass[] Groups by id.
     */
    public static function get_groups(cm_info $cm): array {
        if (self::can_access_all_groups($cm)) {
            return groups_get_all_groups($cm->course, 0, $cm->groupingid, 'g.*', false, true);
        }
        return groups_get_activity_allowed_groups($cm);
    }

    /**
     * Returns the users who can see the whiteboard and whose boards the current user can manage.
     *
     * @param cm_info $cm
     * @return stdClass[] Users by id, with their names and email.
     */
    protected static function get_users(cm_info $cm): array {
        $groupids = 0;
        if (!self::can_access_all_groups($cm)) {
            $groupids = array_keys(self::get_groups($cm));
            if (!$groupids) {
                return [];
            }
        }
        $fields = 'u.id, u.email, ' . \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        [$sort] = users_order_by_sql('u');
        return get_enrolled_users($cm->context, 'mod/whiteboard:view', $groupids, $fields, $sort, 0, 0, true);
    }

    /**
     * Saves the board of a group or user, from a link or an embed code.
     *
     * @param int $whiteboardid
     * @param string $type self::GROUP or self::USER
     * @param int $id The id of the group or user.
     * @param string $input The link or embed code.
     * @return int The id of the board.
     */
    public static function save(int $whiteboardid, string $type, int $id, string $input): int {
        global $DB;
        $owner = ['whiteboard' => $whiteboardid] + self::owner($type, $id);
        $link = service::link_from_input($input);
        if ($board = $DB->get_record('whiteboard_board', $owner)) {
            $DB->update_record('whiteboard_board', (object)['id' => $board->id, 'boardurl' => $link, 'timemodified' => time()]);
            return $board->id;
        }
        return $DB->insert_record('whiteboard_board', (object)($owner + ['boardurl' => $link, 'timemodified' => time()]));
    }

    /**
     * Deletes a board.
     *
     * @param int $id
     */
    public static function delete(int $id): void {
        global $DB;
        $DB->delete_records('whiteboard_board', ['id' => $id]);
    }

    /**
     * Deletes the boards of the groups and users of a whiteboard.
     *
     * @param int $whiteboardid
     */
    public static function delete_for_whiteboard(int $whiteboardid): void {
        global $DB;
        $DB->delete_records('whiteboard_board', ['whiteboard' => $whiteboardid]);
    }

    /**
     * Deletes the boards of the users of whiteboards, for example when a course is reset.
     *
     * @param int[] $whiteboardids
     */
    public static function delete_user_boards(array $whiteboardids): void {
        global $DB;
        if (!$whiteboardids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($whiteboardids);
        $DB->delete_records_select('whiteboard_board', "userid > 0 AND whiteboard {$insql}", $params);
    }

    /**
     * Deletes the boards of a deleted group.
     *
     * @param \core\event\group_deleted $event
     */
    public static function group_deleted(\core\event\group_deleted $event): void {
        global $DB;
        $DB->delete_records('whiteboard_board', ['groupid' => $event->objectid]);
    }

    /**
     * Deletes the boards of a deleted user.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;
        $DB->delete_records('whiteboard_board', ['userid' => $event->objectid]);
    }

    /**
     * Returns the field that holds the group or user of a board.
     *
     * @param string $type self::GROUP or self::USER
     * @return string groupid or userid
     */
    protected static function field(string $type): string {
        if ($type !== self::GROUP && $type !== self::USER) {
            throw new \coding_exception('Unknown type of board: ' . $type);
        }
        return $type . 'id';
    }

    /**
     * Returns the fields that identify the group or user of a board.
     *
     * @param string $type self::GROUP or self::USER
     * @param int $id The id of the group or user.
     * @return int[] groupid and userid
     */
    protected static function owner(string $type, int $id): array {
        return [self::field($type) => $id] + ['groupid' => 0, 'userid' => 0];
    }
}
