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
 * Behat data generator for the whiteboard.
 *
 * @package    mod_whiteboard
 * @category   test
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_whiteboard_generator extends behat_generator_base {
    /**
     * Lists the entities that Behat can create: "the following "mod_whiteboard > services" exist", and boards of
     * groups (by idnumber) or users (by username) of a whiteboard (by name).
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'services' => [
                'singular' => 'service',
                'datagenerator' => 'service',
                'required' => ['name', 'links'],
            ],
            'boards' => [
                'singular' => 'board',
                'datagenerator' => 'board',
                'required' => ['whiteboard', 'boardurl'],
                'switchids' => ['whiteboard' => 'whiteboardid', 'group' => 'groupid', 'user' => 'userid'],
            ],
        ];
    }

    /**
     * Returns the id of a whiteboard.
     *
     * @param string $name The name of the whiteboard.
     * @return int
     */
    protected function get_whiteboard_id(string $name): int {
        global $DB;
        return $DB->get_field('whiteboard', 'id', ['name' => $name], MUST_EXIST);
    }
}
