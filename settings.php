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
 * Whiteboard module admin settings and defaults
 *
 * @package    mod_whiteboard
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configcheckbox('whiteboard/enable_miro',
        get_string('enable_miro', 'mod_whiteboard'), get_string('configenable_miro', 'mod_whiteboard'), true));

    $settings->add(new admin_setting_configcheckbox('whiteboard/enable_conceptboard',
        get_string('enable_conceptboard', 'mod_whiteboard'), get_string('configenable_conceptboard', 'mod_whiteboard'), true));

    // Default board.
    $name = "whiteboard/defaultwhiteboard";
    $title = get_string('defaultwhiteboard', 'mod_whiteboard');
    $description = "";
    $options = [
        'miro' => get_string('strmiro', 'mod_whiteboard'),
        'conceptboard' => get_string('strconceptboard', 'mod_whiteboard')
    ];
    $setting = new admin_setting_configselect($name, $title, $description, 'miro', $options);
    $settings->add($setting);
}
