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
 * Whiteboard data generator.
 *
 * @package    mod_whiteboard
 * @category   test
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_whiteboard_generator extends testing_module_generator {
    /**
     * Creates a whiteboard, by default with a Miro board.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (array)$record + ['boardurl' => 'https://miro.com/app/board/uXjVPfdzxzc=/'];
        return parent::create_instance($record, (array)$options);
    }

    /**
     * Creates a whiteboard service.
     *
     * @param array|stdClass $record name and links (one per line), optional params, display (embed or newtab), enabled
     * @return stdClass
     */
    public function create_service($record): stdClass {
        $record = (object)(array)$record;
        if (!isset($record->display) || $record->display === 'embed') {
            $record->display = \mod_whiteboard\service::DISPLAY_EMBED;
        } else if ($record->display === 'newtab') {
            $record->display = \mod_whiteboard\service::DISPLAY_NEWTAB;
        }
        $record->enabled = $record->enabled ?? 1;
        $record->params = $record->params ?? '';
        return \mod_whiteboard\service::get(\mod_whiteboard\service::save($record));
    }

    /**
     * Creates the board of a group or user.
     *
     * @param array|stdClass $record whiteboardid, groupid or userid, and boardurl (a link or an embed code)
     * @return stdClass
     */
    public function create_board($record): stdClass {
        global $DB;
        $record = (array)$record;
        $type = empty($record['userid']) ? \mod_whiteboard\boards::GROUP : \mod_whiteboard\boards::USER;
        $id = \mod_whiteboard\boards::save(
            $record['whiteboardid'],
            $type,
            (int)($record['userid'] ?? $record['groupid']),
            $record['boardurl']
        );
        return $DB->get_record('whiteboard_board', ['id' => $id]);
    }
}
