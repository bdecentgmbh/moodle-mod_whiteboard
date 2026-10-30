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
 * Upgrade script for the whiteboard.
 *
 * @package    mod_whiteboard
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades the whiteboard.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_whiteboard_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026102800) {
        // Whiteboard services that admins manage replace the fixed choice between Miro and Conceptboard.
        $table = new xmldb_table('whiteboard_service');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('links', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('params', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('display', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // The services of a new site; Miro and Conceptboard stay disabled where an admin had disabled them.
        $config = get_config('whiteboard');
        $wasenabled = [
            'Miro' => $config->enable_miro ?? 1,
            'Conceptboard' => $config->enable_conceptboard ?? 1,
        ];
        foreach (\mod_whiteboard\service::defaults() as $service) {
            if (!$DB->record_exists('whiteboard_service', ['name' => $service['name']])) {
                $service['enabled'] = empty($wasenabled[$service['name']] ?? 1) ? 0 : 1;
                $service['timecreated'] = time();
                $service['timemodified'] = $service['timecreated'];
                $DB->insert_record('whiteboard_service', (object)$service);
            }
        }

        // A board link replaces the board type and ID.
        $table = new xmldb_table('whiteboard');
        $field = new xmldb_field('boardurl', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $type = new xmldb_field('boardtype');
        $id = new xmldb_field('boardid');
        if ($dbman->field_exists($table, $type) && $dbman->field_exists($table, $id)) {
            $rs = $DB->get_recordset('whiteboard', null, '', 'id, boardtype, boardid');
            foreach ($rs as $whiteboard) {
                $DB->set_field(
                    'whiteboard',
                    'boardurl',
                    \mod_whiteboard\service::legacy_board_url($whiteboard->boardtype, $whiteboard->boardid),
                    ['id' => $whiteboard->id]
                );
            }
            $rs->close();
        }
        foreach ([$type, $id] as $field) {
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        $index = new xmldb_index('course', XMLDB_INDEX_NOTUNIQUE, ['course']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        unset_config('enable_miro', 'whiteboard');
        unset_config('enable_conceptboard', 'whiteboard');
        unset_config('defaultwhiteboard', 'whiteboard');

        upgrade_mod_savepoint(true, 2026102800, 'whiteboard');
    }

    if ($oldversion < 2026102900) {
        // Groups and users can have a board of their own.
        $table = new xmldb_table('whiteboard_board');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('whiteboard', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('boardurl', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('whiteboard', XMLDB_KEY_FOREIGN, ['whiteboard'], 'whiteboard', ['id']);
        $table->add_key('groupid', XMLDB_KEY_FOREIGN, ['groupid'], 'groups', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('whiteboardgroupuser', XMLDB_INDEX_UNIQUE, ['whiteboard', 'groupid', 'userid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026102900, 'whiteboard');
    }

    return true;
}
