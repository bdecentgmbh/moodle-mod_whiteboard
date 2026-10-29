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

use stdClass;

/**
 * Boards of groups: in group mode, each group can have a board of its own.
 *
 * Groups without a board, students without a group and "All participants" see the board link of the whiteboard.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_boards {
    /**
     * Returns the board links of the groups of a whiteboard.
     *
     * @param int $whiteboardid
     * @return string[] Board links by group id.
     */
    public static function get(int $whiteboardid): array {
        global $DB;
        return $DB->get_records_menu('whiteboard_board', ['whiteboard' => $whiteboardid], 'groupid', 'groupid, boardurl');
    }

    /**
     * Saves the board links of groups, as entered: links or embed codes. Empty ones are removed.
     *
     * Groups that are not in $links keep their boards.
     *
     * @param int $whiteboardid
     * @param array $links Links or embed codes by group id.
     */
    public static function save(int $whiteboardid, array $links): void {
        global $DB;
        $existing = $DB->get_records('whiteboard_board', ['whiteboard' => $whiteboardid], '', 'groupid, id, boardurl');
        foreach ($links as $groupid => $input) {
            $groupid = (int)$groupid;
            $link = service::link_from_input((string)$input);
            $board = $existing[$groupid] ?? null;
            if ($link === '') {
                if ($board) {
                    $DB->delete_records('whiteboard_board', ['id' => $board->id]);
                }
            } else if (!$board) {
                $DB->insert_record('whiteboard_board', (object)[
                    'whiteboard' => $whiteboardid,
                    'groupid' => $groupid,
                    'boardurl' => $link,
                    'timemodified' => time(),
                ]);
            } else if ($board->boardurl !== $link) {
                $DB->update_record('whiteboard_board', (object)['id' => $board->id, 'boardurl' => $link, 'timemodified' => time()]);
            }
        }
    }

    /**
     * Returns the board link that a group sees.
     *
     * @param stdClass $whiteboard The whiteboard record.
     * @param int $groupid The group, 0 for "All participants" or no group.
     * @return string
     */
    public static function get_link(stdClass $whiteboard, int $groupid): string {
        global $DB;
        if ($groupid) {
            $link = $DB->get_field('whiteboard_board', 'boardurl', ['whiteboard' => $whiteboard->id, 'groupid' => $groupid]);
            if ($link !== false) {
                return $link;
            }
        }
        return (string)$whiteboard->boardurl;
    }

    /**
     * Deletes the boards of the groups of a whiteboard.
     *
     * @param int $whiteboardid
     */
    public static function delete_for_whiteboard(int $whiteboardid): void {
        global $DB;
        $DB->delete_records('whiteboard_board', ['whiteboard' => $whiteboardid]);
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
}
