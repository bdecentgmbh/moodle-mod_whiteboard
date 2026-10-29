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
 * Lists the whiteboard services, and enables, disables, deletes and exports them.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_whiteboard\service;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('modsettingwhiteboard');
$baseurl = new moodle_url('/mod/whiteboard/services.php');

switch ($action) {
    case 'export':
        send_file(service::export(), 'whiteboard-services.json', 0, 0, true, true, 'application/json');
        break;

    case 'enable':
    case 'disable':
        require_sesskey();
        service::get($id);
        service::set_enabled($id, $action === 'enable');
        redirect($baseurl);
        break;

    case 'delete':
        $service = service::get($id);
        if (optional_param('confirm', false, PARAM_BOOL) && confirm_sesskey()) {
            service::delete($id);
            redirect($baseurl, get_string('servicedeleted', 'mod_whiteboard'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
        $count = service::count_activities([$service->id => $service])[$service->id];
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('deleteserviceconfirm', 'mod_whiteboard', (object)[
                'name' => format_string($service->name),
                'count' => $count,
            ]),
            new moodle_url($baseurl, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
            $baseurl
        );
        echo $OUTPUT->footer();
        die;
}

$services = service::get_all();
$counts = service::count_activities($services);

$table = new html_table();
$table->head = [
    get_string('name'),
    get_string('links', 'mod_whiteboard'),
    get_string('display', 'mod_whiteboard'),
    get_string('activities'),
    get_string('actions'),
];
$table->attributes['class'] = 'generaltable mod_whiteboard-services';
$table->data = [];
foreach ($services as $service) {
    $actions = [];
    $toggle = $service->enabled ? 'disable' : 'enable';
    $actions[] = $OUTPUT->action_icon(
        new moodle_url($baseurl, ['action' => $toggle, 'id' => $service->id, 'sesskey' => sesskey()]),
        new pix_icon($service->enabled ? 't/hide' : 't/show', get_string($toggle))
    );
    $actions[] = $OUTPUT->action_icon(
        new moodle_url('/mod/whiteboard/service.php', ['id' => $service->id]),
        new pix_icon('t/edit', get_string('edit'))
    );
    $actions[] = $OUTPUT->action_icon(
        new moodle_url($baseurl, ['action' => 'delete', 'id' => $service->id]),
        new pix_icon('t/delete', get_string('delete'))
    );

    $links = array_map('s', service::parse_links($service->links));
    $display = get_string($service->display == service::DISPLAY_NEWTAB ? 'displaynewtab' : 'displayembed', 'mod_whiteboard');
    if (trim((string)$service->params) !== '') {
        $display .= html_writer::div(get_string('paramsshort', 'mod_whiteboard', s($service->params)), 'small text-muted');
    }

    $row = new html_table_row([
        format_string($service->name),
        implode('<br>', $links),
        $display,
        $counts[$service->id],
        implode(' ', $actions),
    ]);
    if (!$service->enabled) {
        $row->attributes['class'] = 'dimmed_text';
    }
    $table->data[] = $row;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('services', 'mod_whiteboard'));
echo html_writer::tag('p', get_string('services_desc', 'mod_whiteboard'));
echo html_writer::div(
    $OUTPUT->single_button(
        new moodle_url('/mod/whiteboard/service.php'),
        get_string('addservice', 'mod_whiteboard'),
        'get',
        ['type' => single_button::BUTTON_PRIMARY]
    ) . ' ' .
    $OUTPUT->single_button(new moodle_url('/mod/whiteboard/import.php'), get_string('import'), 'get') . ' ' .
    $OUTPUT->single_button(new moodle_url($baseurl, ['action' => 'export']), get_string('export', 'mod_whiteboard'), 'get'),
    'mb-3'
);
if ($services) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('noservicesadmin', 'mod_whiteboard'), 'info', false);
}
echo $OUTPUT->footer();
