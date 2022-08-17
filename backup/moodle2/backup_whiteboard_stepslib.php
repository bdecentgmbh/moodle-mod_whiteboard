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
 * Define all the backup steps that will be used by the backup_whiteboard_activity_task
 *
 * @package    mod_whiteboard
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Define the complete whiteboard structure for backup, with file and id annotations
 */
class backup_whiteboard_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define structure.
     * @return void
     */
    protected function define_structure() {

        // The whiteboard module stores no user info.

        // Define each element separated.
        $whiteboard = new backup_nested_element('whiteboard', array('id'), array(
            'name', 'intro', 'introformat', 'boardtype',
            'boardid', 'timecreated', 'timemodified'));

        // Build the tree.

        // Define sources.
        $whiteboard->set_source_table('whiteboard', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations.
        // Module has no id annotations.

        // Define file annotations.
        $whiteboard->annotate_files('mod_whiteboard', 'intro', null); // This file area hasn't itemid.

        // Return the root element (whiteboard), wrapped into standard activity structure.
        return $this->prepare_activity_structure($whiteboard);

    }
}

