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
 * Strings for component 'mod_whiteboard', language 'en'.
 *
 * @package   mod_whiteboard
 * @copyright 2022 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addservice'] = 'Add a service';
$string['boardofgroup'] = '{$a->name} ({$a->group})';
$string['boardurl'] = 'Board link';
$string['boardurl_help'] = 'Paste the link to the board, or the code to embed it, as the whiteboard service gives it to you. Share the board in the service so that your students can open it, for example with "Anyone with the link".';
$string['deleteserviceconfirm'] = 'Delete the whiteboard service "{$a->name}"? Activities with its boards: {$a->count}. They will show a notice instead of their board.';
$string['display'] = 'Display';
$string['display_help'] = 'In the page: the activity page shows the board. In a new tab: the activity page has a button that opens the board in a new tab. Choose it for services that do not allow other websites to show their boards.';
$string['displayembed'] = 'In the page';
$string['displaynewtab'] = 'In a new tab';
$string['editservice'] = 'Edit service';
$string['enabled'] = 'Enabled';
$string['exittheatremode'] = 'Exit theatre mode';
$string['export'] = 'Export';
$string['groupboards'] = 'Group boards';
$string['groupboards_desc'] = 'In group mode (see Common module settings), each group can have a board of its own: paste its link or embed code next to the group. Students see the board of their group, teachers choose the group. Groups left empty, students without a group and "All participants" see the board link above.';
$string['importdone'] = 'Services added: {$a->added}. Services updated: {$a->updated}.';
$string['importinvalid'] = 'These services cannot be imported. {$a}';
$string['importjson'] = 'Services (JSON)';
$string['importjson_help'] = 'Paste the services as exported by "Export" on another site, or a service from the documentation. A service with the name of an existing service replaces its settings; the others are added.';
$string['importnoname'] = 'Every service needs a name.';
$string['importnotjson'] = 'The text is not a list of services in JSON.';
$string['importservices'] = 'Import services';
$string['links'] = 'Links';
$string['links_help'] = 'A board link belongs to this service if it starts with one of these links. One per line, each with https://, the address of the service and the beginning of the path, for example https://miro.com/app/board/';
$string['linksinvalid'] = 'Each link must start with https://, the address of the service and a "/": {$a}';
$string['modulename'] = 'Whiteboard';
$string['modulename_help'] = 'The whiteboard activity brings a board of an online whiteboard service, such as Miro or TaskCards, into the course, so that students find it where they find everything else.';
$string['modulenameplural'] = 'Whiteboards';
$string['newtabinfo'] = 'This board opens in {$a} in a new tab.';
$string['noservices'] = 'No whiteboard service is enabled on this site. Please ask your administrator.';
$string['noservicesadmin'] = 'There are no whiteboard services. Add one, or import services.';
$string['openboard'] = 'Open the board';
$string['openinnewtab'] = 'Open in a new tab';
$string['params'] = 'Link parameters';
$string['params_help'] = 'Added to every board link of this service, unless the link has them already. For example, autoplay=true makes Miro show the board right away instead of a preview that has to be clicked first. Separate several with &.';
$string['paramsinvalid'] = 'Enter parameters as name=value, and separate several with &.';
$string['paramsshort'] = 'Adds {$a}';
$string['pluginadministration'] = 'Whiteboard administration';
$string['pluginname'] = 'Whiteboard';
$string['privacy:metadata'] = 'The Whiteboard activity stores no personal data. Boards are kept by the whiteboard service, which the browsers of the users connect to directly.';
$string['servicedeleted'] = 'The service has been deleted.';
$string['serviceexists'] = 'There is a service with this name already.';
$string['services'] = 'Whiteboard services';
$string['services_desc'] = 'Teachers can add boards of the enabled services. A board link belongs to a service if it starts with one of the service\'s links. Whether a service lets other websites show its boards is up to the service and can change at any time.';
$string['supportedservices'] = 'Supported services: {$a}';
$string['theatremode'] = 'Theatre mode';
$string['unavailable'] = 'This board cannot be shown at the moment. Please contact your teacher.';
$string['unavailableedit'] = 'This board cannot be shown: its link is not from a whiteboard service that is enabled on this site. Change the board link in the settings, or ask your administrator to enable the service.';
$string['unsupportedlink'] = 'Paste the link of a board, or its embed code, from one of these services: {$a}';
$string['whiteboard:addinstance'] = 'Add a new whiteboard';
$string['whiteboard:view'] = 'View whiteboard';
