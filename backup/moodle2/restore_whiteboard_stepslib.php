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
 * Define all the restore steps that will be used by the restore_whiteboard_activity_task
 * @package    mod_whiteboard
 * @subpackage backup-moodle2
 * @copyright 2022 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Structure step to restore one whiteboard activity
 */
class restore_whiteboard_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define structure.
     */
    protected function define_structure() {

        $paths = [];
        $paths[] = new restore_path_element('whiteboard', '/activity/whiteboard');
        $paths[] = new restore_path_element('whiteboard_board', '/activity/whiteboard/boards/board');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * process the data
     * @param object $data
     */
    protected function process_whiteboard($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // Backups of version 1.0 have a board type and ID instead of the link.
        if (!isset($data->boardurl)) {
            $data->boardurl = \mod_whiteboard\service::legacy_board_url($data->boardtype ?? null, $data->boardid ?? null);
        }
        unset($data->boardtype, $data->boardid);

        // Insert the whiteboard record.
        $newitemid = $DB->insert_record('whiteboard', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores the board of a group, if the group was restored.
     *
     * @param array $data
     */
    protected function process_whiteboard_board($data) {
        global $DB;

        $data = (object)$data;
        $data->whiteboard = $this->get_new_parentid('whiteboard');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        if ($data->groupid) {
            $DB->insert_record('whiteboard_board', $data);
        }
    }

    /**
     * After execute add related files.
     */
    protected function after_execute() {
        // Add whiteboard related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_whiteboard', 'intro', null);
    }
}
