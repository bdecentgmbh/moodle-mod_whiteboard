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

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/whiteboard/lib.php');

use mod_whiteboard\service;

/**
 * Whiteboard module form.
 */
class mod_whiteboard_mod_form extends moodleform_mod {
    /**
     * Defines the form.
     */
    public function definition() {
        global $CFG, $OUTPUT;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        $mform->setType('name', empty($CFG->formatstringstriptags) ? PARAM_CLEANHTML : PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // A link or an embed code, which is reduced to its link when saved.
        $mform->addElement('text', 'boardurl', get_string('boardurl', 'mod_whiteboard'), ['size' => '64']);
        $mform->setType('boardurl', PARAM_RAW_TRIMMED);
        $mform->addRule('boardurl', null, 'required', null, 'client');
        $mform->addHelpButton('boardurl', 'boardurl', 'mod_whiteboard');

        $names = $this->get_service_names();
        if ($names) {
            $mform->addElement('static', 'services', '', get_string('supportedservices', 'mod_whiteboard', $names));
        } else {
            $mform->addElement(
                'static',
                'services',
                '',
                $OUTPUT->notification(get_string('noservices', 'mod_whiteboard'), 'warning', false)
            );
        }

        $this->standard_intro_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Checks that the board link belongs to an enabled service.
     *
     * @param array $data
     * @param array $files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $link = service::link_from_input($data['boardurl'] ?? '');
        if (strlen($link) > service::MAX_LINK_LENGTH) {
            $errors['boardurl'] = get_string('maximumchars', '', service::MAX_LINK_LENGTH);
        } else if (!service::find($link)) {
            $names = $this->get_service_names();
            $errors['boardurl'] = $names ? get_string('unsupportedlink', 'mod_whiteboard', $names)
                : get_string('noservices', 'mod_whiteboard');
        }
        return $errors;
    }

    /**
     * Returns the names of the enabled services, for teachers.
     *
     * @return string Names separated by commas, empty if no service is enabled.
     */
    protected function get_service_names(): string {
        $names = array_map(
            fn($service) => format_string($service->name, true, ['context' => context_system::instance()]),
            service::get_enabled()
        );
        return implode(', ', $names);
    }
}
