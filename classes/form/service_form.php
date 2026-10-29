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

use mod_whiteboard\service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to add or edit a whiteboard service.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_form extends \moodleform {
    /**
     * Defines the form.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name'), ['size' => 40]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'links', get_string('links', 'mod_whiteboard'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('links', PARAM_RAW_TRIMMED);
        $mform->addRule('links', null, 'required', null, 'client');
        $mform->addHelpButton('links', 'links', 'mod_whiteboard');

        $mform->addElement('text', 'params', get_string('params', 'mod_whiteboard'), ['size' => 40]);
        $mform->setType('params', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('params', 'params', 'mod_whiteboard');

        $mform->addElement('select', 'display', get_string('display', 'mod_whiteboard'), [
            service::DISPLAY_EMBED => get_string('displayembed', 'mod_whiteboard'),
            service::DISPLAY_NEWTAB => get_string('displaynewtab', 'mod_whiteboard'),
        ]);
        $mform->addHelpButton('display', 'display', 'mod_whiteboard');

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'mod_whiteboard'));

        $this->add_action_buttons();
    }

    /**
     * Checks the service.
     *
     * @param array $data
     * @param array $files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $service = (object)$data;
        $service->id = (int)($this->_customdata['id'] ?? 0);
        return array_merge($errors, service::validate($service));
    }
}
