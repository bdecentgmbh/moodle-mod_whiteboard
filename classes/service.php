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

namespace mod_whiteboard;

use stdClass;

/**
 * Whiteboard services: which board links teachers can add, and how their boards are shown.
 *
 * A service is a record in whiteboard_service. A board link belongs to a service when it starts with one of the
 * service's links, e.g. https://miro.com/app/board/. The board link itself is shown, only with the service's link
 * parameters added, so no service needs code of its own.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service {
    /** @var int The board is shown in the activity page. */
    const DISPLAY_EMBED = 0;

    /** @var int The activity page has a button that opens the board in a new tab. */
    const DISPLAY_NEWTAB = 1;

    /** @var string Features of the browser that embedded boards may use. */
    const IFRAME_ALLOW = 'fullscreen; clipboard-read; clipboard-write';

    /** @var int Maximum length of a board link (size of whiteboard.boardurl). */
    const MAX_LINK_LENGTH = 1333;

    /** @var string A link parameter: name=value, without characters that would need encoding. */
    const PARAM_PATTERN = '[A-Za-z0-9_.~-]+=[A-Za-z0-9_.~%-]*';

    /**
     * The services that a new site starts with. Checked with real boards in October 2026.
     *
     * @return array[] Records for whiteboard_service, without timestamps.
     */
    public static function defaults(): array {
        return [
            [
                'name' => 'Collaboard',
                'links' => 'https://web.collaboard.app/share/',
                'params' => '',
                'display' => self::DISPLAY_EMBED,
                'enabled' => 1,
            ],
            [
                // Conceptboard does not allow other websites to show its boards.
                'name' => 'Conceptboard',
                'links' => 'https://app.conceptboard.com/board/',
                'params' => '',
                'display' => self::DISPLAY_NEWTAB,
                'enabled' => 1,
            ],
            [
                // Without autoplay, Miro shows a preview that has to be clicked first.
                'name' => 'Miro',
                'links' => "https://miro.com/app/board/\nhttps://miro.com/app/live-embed/",
                'params' => 'autoplay=true',
                'display' => self::DISPLAY_EMBED,
                'enabled' => 1,
            ],
            [
                'name' => 'TaskCards',
                'links' => 'https://www.taskcards.de/#/board/',
                'params' => '',
                'display' => self::DISPLAY_EMBED,
                'enabled' => 1,
            ],
        ];
    }

    /**
     * Returns a service.
     *
     * @param int $id
     * @param int $strictness MUST_EXIST or IGNORE_MISSING
     * @return stdClass|false
     */
    public static function get(int $id, int $strictness = MUST_EXIST) {
        global $DB;
        return $DB->get_record('whiteboard_service', ['id' => $id], '*', $strictness);
    }

    /**
     * Returns all services, by name.
     *
     * @return stdClass[]
     */
    public static function get_all(): array {
        global $DB;
        return $DB->get_records('whiteboard_service', null, 'name, id');
    }

    /**
     * Returns the services that teachers can use, by name.
     *
     * @return stdClass[]
     */
    public static function get_enabled(): array {
        global $DB;
        return $DB->get_records('whiteboard_service', ['enabled' => 1], 'name, id');
    }

    /**
     * Returns the board link in what a teacher entered: the link itself, or the src of an embed code.
     *
     * @param string $input
     * @return string
     */
    public static function link_from_input(string $input): string {
        $input = trim($input);
        if (preg_match('~<iframe\b[^>]*?\ssrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')~i', $input, $matches)) {
            $input = html_entity_decode($matches[1] !== '' ? $matches[1] : ($matches[2] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return trim($input);
    }

    /**
     * Whether a board link is safe to show: a plain https link that Moodle's URL cleaning leaves as it is.
     *
     * @param string $link
     * @return bool
     */
    public static function is_valid_link(string $link): bool {
        return str_starts_with(strtolower($link), 'https://')
            && strlen($link) <= self::MAX_LINK_LENGTH
            && clean_param($link, PARAM_URL) === $link;
    }

    /**
     * Returns the service of a board link.
     *
     * @param string $link
     * @param stdClass[]|null $services Services to look in, by default the enabled ones.
     * @return stdClass|null The first service with a matching link, null if there is none or the link is not valid.
     */
    public static function find(string $link, ?array $services = null): ?stdClass {
        if (!self::is_valid_link($link)) {
            return null;
        }
        foreach ($services ?? self::get_enabled() as $service) {
            if (self::matches($service, $link)) {
                return $service;
            }
        }
        return null;
    }

    /**
     * Returns the names of the enabled services, for teachers.
     *
     * @return string Names separated by commas, empty if no service is enabled.
     */
    public static function get_enabled_names(): string {
        $names = array_map(
            fn($service) => format_string($service->name, true, ['context' => \context_system::instance()]),
            self::get_enabled()
        );
        return implode(', ', $names);
    }

    /**
     * Returns the information for teachers below the field of a board link: the services they can use.
     *
     * @return string HTML
     */
    public static function get_supported_info(): string {
        global $OUTPUT;
        $names = self::get_enabled_names();
        if (!$names) {
            return $OUTPUT->notification(get_string('noservices', 'mod_whiteboard'), 'warning', false);
        }
        return get_string('supportedservices', 'mod_whiteboard', $names);
    }

    /**
     * Checks that what a teacher entered, a link or an embed code, is a board of an enabled service.
     *
     * @param string $input
     * @return string|null The error, null if the link is fine.
     */
    public static function check_input(string $input): ?string {
        $link = self::link_from_input($input);
        if (strlen($link) > self::MAX_LINK_LENGTH) {
            return get_string('maximumchars', '', self::MAX_LINK_LENGTH);
        }
        if (!self::find($link)) {
            $names = self::get_enabled_names();
            return $names ? get_string('unsupportedlink', 'mod_whiteboard', $names) : get_string('noservices', 'mod_whiteboard');
        }
        return null;
    }

    /**
     * Whether a board link starts with one of the links of a service.
     *
     * @param stdClass $service
     * @param string $link
     * @return bool
     */
    public static function matches(stdClass $service, string $link): bool {
        [$origin, $path] = self::split_link($link);
        foreach (self::parse_links($service->links) as $prefix) {
            [$prefixorigin, $prefixpath] = self::split_link($prefix);
            if ($origin === $prefixorigin && str_starts_with($path, $prefixpath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Splits a link into scheme with host, which are compared in lower case, and the rest from the first "/" on.
     *
     * The rest must begin with "/", so a service link such as https://miro.com/ never matches a link to another host
     * that begins with the same letters, such as https://miro.com.example.org/, or has a user name before its host.
     *
     * @param string $link
     * @return string[] [origin, path]
     */
    protected static function split_link(string $link): array {
        $slash = strlen($link) > strlen('https://') ? strpos($link, '/', strlen('https://')) : false;
        if ($slash === false) {
            return [strtolower($link), ''];
        }
        return [strtolower(substr($link, 0, $slash)), substr($link, $slash)];
    }

    /**
     * Returns the link that shows the board: the board link with the service's link parameters added.
     *
     * Parameters that the board link has already are left as they are.
     *
     * @param stdClass $service
     * @param string $link
     * @return string
     */
    public static function board_url(stdClass $service, string $link): string {
        $params = trim((string)$service->params);
        if ($params === '') {
            return $link;
        }
        $fragment = '';
        if (($hash = strpos($link, '#')) !== false) {
            $fragment = substr($link, $hash);
            $link = substr($link, 0, $hash);
        }
        $query = (string)parse_url($link, PHP_URL_QUERY);
        $existing = [];
        foreach (explode('&', $query) as $pair) {
            $existing[explode('=', $pair, 2)[0]] = true;
        }
        $add = [];
        foreach (explode('&', $params) as $pair) {
            if (!isset($existing[explode('=', $pair, 2)[0]])) {
                $add[] = $pair;
            }
        }
        if ($add) {
            if (!str_contains($link, '?')) {
                $link .= '?';
            } else if (!str_ends_with($link, '?') && !str_ends_with($link, '&')) {
                $link .= '&';
            }
            $link .= implode('&', $add);
        }
        return $link . $fragment;
    }

    /**
     * Returns the links of a service, one per line in the database, without empty lines.
     *
     * @param mixed $links A string with one link per line, or an array of links (e.g. imported).
     * @return string[]
     */
    public static function parse_links($links): array {
        if (!is_array($links)) {
            $links = is_scalar($links) ? preg_split('/\R/', (string)$links) : [];
        }
        $links = array_map(fn($link) => is_scalar($link) ? trim((string)$link) : '', $links);
        return array_values(array_filter($links, fn($link) => $link !== ''));
    }

    /**
     * Whether a service link is valid: https, a host name and the beginning of a path.
     *
     * @param string $link
     * @return bool
     */
    public static function is_valid_service_link(string $link): bool {
        $host = '[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+';
        return (bool)preg_match('~^https://' . $host . '(?::[0-9]{1,5})?/~i', $link)
            && strlen($link) <= 255
            && clean_param($link, PARAM_URL) === $link;
    }

    /**
     * Checks the fields of a service, as entered in the form or imported.
     *
     * @param stdClass $data name, links, params, display
     * @return string[] Errors by field name, empty if the service is valid.
     */
    public static function validate(stdClass $data): array {
        global $DB;
        $errors = [];
        $name = trim((string)($data->name ?? ''));
        if ($name === '') {
            $errors['name'] = get_string('required');
        } else if (\core_text::strlen($name) > 255) {
            $errors['name'] = get_string('maximumchars', '', 255);
        } else {
            $select = $DB->sql_equal('name', ':name', false) . ' AND id <> :id';
            if ($DB->record_exists_select('whiteboard_service', $select, ['name' => $name, 'id' => (int)($data->id ?? 0)])) {
                $errors['name'] = get_string('serviceexists', 'mod_whiteboard');
            }
        }
        $links = self::parse_links($data->links ?? '');
        if (!$links) {
            $errors['links'] = get_string('required');
        } else {
            $invalid = array_filter($links, fn($link) => !self::is_valid_service_link($link));
            if ($invalid) {
                $errors['links'] = get_string('linksinvalid', 'mod_whiteboard', s(reset($invalid)));
            }
        }
        $params = trim((string)($data->params ?? ''));
        if ($params !== '' && !preg_match('/^' . self::PARAM_PATTERN . '(?:&' . self::PARAM_PATTERN . ')*$/', $params)) {
            $errors['params'] = get_string('paramsinvalid', 'mod_whiteboard');
        }
        $display = $data->display ?? null;
        if (!is_numeric($display) || !in_array((int)$display, [self::DISPLAY_EMBED, self::DISPLAY_NEWTAB], true)) {
            $errors['display'] = get_string('invaliddata', 'error');
        }
        return $errors;
    }

    /**
     * Adds or updates a service.
     *
     * @param stdClass $data name, links, params, display, enabled, and id to update
     * @return int The id of the service.
     */
    public static function save(stdClass $data): int {
        global $DB;
        $record = (object)[
            'name' => trim($data->name),
            'links' => implode("\n", self::parse_links($data->links)),
            'params' => trim((string)($data->params ?? '')),
            'display' => (int)$data->display,
            'enabled' => empty($data->enabled) ? 0 : 1,
            'timemodified' => time(),
        ];
        if (!empty($data->id)) {
            $record->id = (int)$data->id;
            $DB->update_record('whiteboard_service', $record);
            return $record->id;
        }
        $record->timecreated = $record->timemodified;
        return $DB->insert_record('whiteboard_service', $record);
    }

    /**
     * Enables or disables a service.
     *
     * @param int $id
     * @param bool $enabled
     */
    public static function set_enabled(int $id, bool $enabled): void {
        global $DB;
        $DB->update_record('whiteboard_service', (object)['id' => $id, 'enabled' => (int)$enabled, 'timemodified' => time()]);
    }

    /**
     * Deletes a service. Activities with its board links show a notice instead of the board.
     *
     * @param int $id
     */
    public static function delete(int $id): void {
        global $DB;
        $DB->delete_records('whiteboard_service', ['id' => $id]);
    }

    /**
     * Counts the activities of each service: activities whose board, or the board of one of their groups or users, it has.
     *
     * @param stdClass[] $services
     * @return int[] Number of activities by service id.
     */
    public static function count_activities(array $services): array {
        global $DB;
        $used = [];
        $sql = 'SELECT id AS whiteboard, boardurl FROM {whiteboard}
                 UNION ALL
                SELECT whiteboard, boardurl FROM {whiteboard_board}';
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $board) {
            if ($service = self::find((string)$board->boardurl, $services)) {
                $used[$service->id][$board->whiteboard] = true;
            }
        }
        $rs->close();
        $counts = [];
        foreach ($services as $id => $service) {
            $counts[$id] = count($used[$id] ?? []);
        }
        return $counts;
    }

    /**
     * Returns all services as JSON, to be imported on another site.
     *
     * @return string
     */
    public static function export(): string {
        $services = [];
        foreach (self::get_all() as $service) {
            $services[] = [
                'name' => $service->name,
                'links' => self::parse_links($service->links),
                'params' => (string)$service->params,
                'display' => $service->display == self::DISPLAY_NEWTAB ? 'newtab' : 'embed',
                'enabled' => (bool)$service->enabled,
            ];
        }
        return json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * Reads services from JSON, as exported, or a single service as an object.
     *
     * A service whose name exists already gets that service's id, so that it is updated.
     *
     * @param string $json
     * @return stdClass[] Services to save.
     * @throws \moodle_exception if the JSON or a service is not valid
     */
    public static function parse_import(string $json): array {
        global $DB;
        $entries = json_decode($json);
        if (is_object($entries)) {
            $entries = [$entries];
        }
        if (!is_array($entries) || !$entries) {
            throw new \moodle_exception('importinvalid', 'mod_whiteboard', '', get_string('importnotjson', 'mod_whiteboard'));
        }
        $services = [];
        $names = [];
        foreach ($entries as $entry) {
            if (!is_object($entry) || !isset($entry->name) || !is_string($entry->name)) {
                throw new \moodle_exception('importinvalid', 'mod_whiteboard', '', get_string('importnoname', 'mod_whiteboard'));
            }
            $display = $entry->display ?? 'embed';
            $service = (object)[
                'name' => trim($entry->name),
                'links' => is_array($entry->links ?? null) || is_string($entry->links ?? null) ? $entry->links : '',
                'params' => is_string($entry->params ?? null) ? $entry->params : '',
                'display' => $display === 'newtab' ? self::DISPLAY_NEWTAB : ($display === 'embed' ? self::DISPLAY_EMBED : -1),
                'enabled' => !isset($entry->enabled) || !empty($entry->enabled),
            ];
            $service->links = self::parse_links($service->links);
            $existing = $DB->get_record_select(
                'whiteboard_service',
                $DB->sql_equal('name', ':name', false),
                ['name' => $service->name],
                'id',
                IGNORE_MULTIPLE
            );
            $service->id = $existing ? (int)$existing->id : 0;
            $key = \core_text::strtolower($service->name);
            if (isset($names[$key])) {
                $errors = ['name' => get_string('serviceexists', 'mod_whiteboard')];
            } else {
                $errors = self::validate($service);
            }
            if ($errors) {
                throw new \moodle_exception('importinvalid', 'mod_whiteboard', '', s($service->name) . ': ' . reset($errors));
            }
            $names[$key] = true;
            $services[] = $service;
        }
        return $services;
    }

    /**
     * Saves services read by {@see parse_import()}.
     *
     * @param stdClass[] $services
     * @return stdClass Numbers of services added and updated.
     */
    public static function import(array $services): stdClass {
        global $DB;
        $result = (object)['added' => 0, 'updated' => 0];
        $transaction = $DB->start_delegated_transaction();
        foreach ($services as $service) {
            if ($service->id) {
                $result->updated++;
            } else {
                $result->added++;
            }
            self::save($service);
        }
        $transaction->allow_commit();
        return $result;
    }

    /**
     * Returns the board link of a whiteboard of version 1.0, which had a board type and a board ID.
     *
     * Version 1.0 showed miro.com/app/live-embed/<ID> and app.conceptboard.com/board/<ID>.
     *
     * @param string|null $type miro or conceptboard
     * @param string|null $id The board ID, or a link if a teacher entered one.
     * @return string The board link, empty if there is none.
     */
    public static function legacy_board_url(?string $type, ?string $id): string {
        $id = trim((string)$id);
        if ($id === '' || preg_match('~^https?://~i', $id)) {
            return $id;
        }
        $id = trim($id, '/');
        if (!preg_match('/^[A-Za-z0-9_.~=-]+$/', $id)) {
            $id = rawurlencode($id);
        }
        switch ($type) {
            case 'miro':
                return 'https://miro.com/app/live-embed/' . $id . '/';
            case 'conceptboard':
                return 'https://app.conceptboard.com/board/' . $id;
            default:
                return '';
        }
    }
}
