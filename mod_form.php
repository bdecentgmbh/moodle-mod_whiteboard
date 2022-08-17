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
 * Whiteboard configuration form
 *
 * @package    mod_whiteboard
 * @copyright  lmsace dev team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/whiteboard/lib.php');

/**
 * Whiteboard module form.
 */
class mod_whiteboard_mod_form extends moodleform_mod {

    /**
     * Define the mform.
     */
    public function definition() {
        global $CFG, $DB, $OUTPUT;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        // Select the type.
        $config = get_config('whiteboard');
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $miro = isset($config->enable_miro) ? $config->enable_miro : '';
        $conceptboard = isset($config->enable_conceptboard) ? $config->enable_conceptboard : '';
        $type = array();
        if ($miro) {
            $type['miro'] = get_string('MIRO', 'whiteboard');
        }
        if ($conceptboard) {
             $type['conceptboard'] = get_string('Conceptboard', 'whiteboard');
        }

        // Board ID.
        $mform->addElement('text', 'boardid', get_string('whiteboardid', 'whiteboard'), array('size' => '48'));
        $mform->setType('boardid', PARAM_TEXT);
        $mform->addRule('boardid', null, 'required', null, 'client');
	
	if (count($type) > 1) {
	        $mform->addElement('select', 'boardtype', get_string('boardtypename', 'whiteboard'), $type);
        	$mform->setDefault('boardtype', $config->defaultwhiteboard);
	} else if (!empty($type)) {
		$board = reset(array_keys($type));
		$mform->addElement('hidden', 'boardtype', $board);
	} else {
		throw new moodle_exception('boardtypenotavailable');
	}

        $this->standard_intro_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
