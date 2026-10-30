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
 * Imports whiteboard services from JSON.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_whiteboard\form\import_form;
use mod_whiteboard\service;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$url = new moodle_url('/mod/whiteboard/import.php');
admin_externalpage_setup('modsettingwhiteboard', '', null, $url);
$returnurl = new moodle_url('/mod/whiteboard/services.php');
$title = get_string('importservices', 'mod_whiteboard');
$PAGE->navbar->add($title, $url);

$form = new import_form($url);
if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    $result = service::import(service::parse_import($data->json));
    redirect($returnurl, get_string('importdone', 'mod_whiteboard', $result), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$form->display();
echo $OUTPUT->footer();
