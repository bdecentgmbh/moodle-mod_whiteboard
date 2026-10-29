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

namespace mod_whiteboard\form;

use mod_whiteboard\boards;
use mod_whiteboard\service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to add or edit the board of a group or user.
 *
 * Custom data: type (boards::GROUP or boards::USER), and either choices (names by id of the groups or users without a
 * board, to add one) or name (of the group or user whose board is edited).
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class board_form extends \moodleform {
    /**
     * Defines the form.
     */
    protected function definition() {
        $mform = $this->_form;
        $type = $this->_customdata['type'];
        $label = get_string($type === boards::GROUP ? 'group' : 'user');

        if (isset($this->_customdata['name'])) {
            $mform->addElement('static', 'owner', $label, $this->_customdata['name']);
        } else {
            // An empty option first, so that nobody is chosen until the teacher chooses.
            $mform->addElement('autocomplete', 'ownerid', $label, ['' => ''] + $this->_customdata['choices'], [
                'noselectionstring' => get_string($type === boards::GROUP ? 'choosegroup' : 'chooseuser', 'mod_whiteboard'),
            ]);
            $mform->addRule('ownerid', null, 'required', null, 'client');
        }

        // A link or an embed code, which is reduced to its link when saved.
        $mform->addElement('text', 'boardurl', get_string('boardurl', 'mod_whiteboard'), ['size' => '64']);
        $mform->setType('boardurl', PARAM_RAW_TRIMMED);
        $mform->addRule('boardurl', null, 'required', null, 'client');
        $mform->addHelpButton('boardurl', 'boardurl', 'mod_whiteboard');
        $mform->addElement('static', 'services', '', service::get_supported_info());

        $buttons = [
            $mform->createElement('submit', 'submitbutton', get_string('save')),
            $mform->createElement('submit', 'againbutton', get_string('saveandaddanother', 'mod_whiteboard')),
            $mform->createElement('cancel'),
        ];
        $mform->addGroup($buttons, 'buttonbar', '', [' '], false);
        $mform->closeHeaderBefore('buttonbar');
    }

    /**
     * Checks the group or user, and that the board link belongs to an enabled service.
     *
     * @param array $data
     * @param array $files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!isset($this->_customdata['name']) && !isset($this->_customdata['choices'][$data['ownerid'] ?? 0])) {
            $errors['ownerid'] = get_string('required');
        }
        if ($error = service::check_input($data['boardurl'] ?? '')) {
            $errors['boardurl'] = $error;
        }
        return $errors;
    }
}
