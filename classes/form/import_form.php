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
 * Form to import whiteboard services from JSON.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {
    /**
     * Defines the form.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement(
            'textarea',
            'json',
            get_string('importjson', 'mod_whiteboard'),
            ['rows' => 16, 'cols' => 80, 'class' => 'text-monospace font-monospace']
        );
        $mform->setType('json', PARAM_RAW);
        $mform->addRule('json', null, 'required', null, 'client');
        $mform->addHelpButton('json', 'importjson', 'mod_whiteboard');

        $this->add_action_buttons(true, get_string('import'));
    }

    /**
     * Checks that the JSON is a valid list of services.
     *
     * @param array $data
     * @param array $files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        try {
            service::parse_import($data['json']);
        } catch (\moodle_exception $e) {
            $errors['json'] = $e->getMessage();
        }
        return $errors;
    }
}
