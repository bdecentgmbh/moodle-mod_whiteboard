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

#[\PHPUnit\Framework\Attributes\CoversClass(service::class)]
/**
 * Tests for the whiteboard services.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_whiteboard\service
 */
final class service_test extends \advanced_testcase {
    /**
     * Returns a service by name.
     *
     * @param string $name
     * @return \stdClass
     */
    protected function get_service(string $name): \stdClass {
        global $DB;
        return $DB->get_record('whiteboard_service', ['name' => $name], '*', MUST_EXIST);
    }

    /**
     * A new site has the services that were checked with real boards.
     */
    public function test_defaults(): void {
        $this->resetAfterTest();
        $services = service::get_all();
        $this->assertSame(['Collaboard', 'Conceptboard', 'Miro', 'TaskCards'], array_values(array_column($services, 'name')));
        foreach ($services as $service) {
            $this->assertEquals(1, $service->enabled);
            $this->assertEmpty(service::validate($service), $service->name);
        }
        $this->assertSame('autoplay=true', $this->get_service('Miro')->params);
        $this->assertEquals(service::DISPLAY_NEWTAB, $this->get_service('Conceptboard')->display);
    }

    /**
     * Teachers can paste a link or an embed code.
     */
    public function test_link_from_input(): void {
        $cases = [
            '  https://miro.com/app/board/x=/  ' => 'https://miro.com/app/board/x=/',
            '<iframe width="768" src="https://miro.com/app/live-embed/x=/?a=1&amp;b=2" allowfullscreen></iframe>' =>
                'https://miro.com/app/live-embed/x=/?a=1&b=2',
            "<iframe src='https://web.collaboard.app/share/abc' width=\"720\"></iframe>" => 'https://web.collaboard.app/share/abc',
            '<IFRAME style="border:0" SRC = "https://www.taskcards.de/#/board/abc"></IFRAME>' =>
                'https://www.taskcards.de/#/board/abc',
            '<a href="https://miro.com/app/board/x=/">Board</a>' => '<a href="https://miro.com/app/board/x=/">Board</a>',
        ];
        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, service::link_from_input($input), $input);
        }
    }

    /**
     * A board link belongs to the service whose link it starts with; anything else is refused.
     */
    public function test_find(): void {
        $this->resetAfterTest();
        $cases = [
            'https://miro.com/app/board/uXjVPfdzxzc=/' => 'Miro',
            'https://miro.com/app/board/uXjVPfdzxzc=/?share_link_id=123' => 'Miro',
            'https://MIRO.com/app/board/uXjVPfdzxzc=/' => 'Miro',
            'https://miro.com/app/live-embed/o9J_kodlngQ=/?moveToWidget=1&embedId=2' => 'Miro',
            'https://www.taskcards.de/#/board/43cc9837-9499-40ff-b6a9-d47eb96b024f' => 'TaskCards',
            'https://web.collaboard.app/share/mC8ddeXFfxyBS389_Fi0yA' => 'Collaboard',
            'https://app.conceptboard.com/board/b6b6-0ccf-8z9s-qmqi-8bt5' => 'Conceptboard',
            'http://miro.com/app/board/uXjVPfdzxzc=/' => null,
            'https://miro.com.example.org/app/board/x/' => null,
            'https://miro.com@example.org/app/board/x/' => null,
            'https://example.org/https://miro.com/app/board/x/' => null,
            'https://miro.com/app/dashboard/' => null,
            'https://miro.com/app/BOARD/x/' => null,
            'https://miro.com/app/board/x" onload="alert(1)' => null,
            'javascript:alert(1)//https://miro.com/app/board/' => null,
            'https://miro.com/app/board/' . str_repeat('x', service::MAX_LINK_LENGTH) => null,
            '' => null,
        ];
        foreach ($cases as $link => $expected) {
            $service = service::find($link);
            $this->assertSame($expected, $service ? $service->name : null, $link);
        }
        foreach (['', 'https:', 'https://'] as $link) {
            $this->assertFalse(service::matches((object)['links' => 'https://miro.com/'], $link), $link);
        }
    }

    /**
     * Boards of disabled services are not found.
     */
    public function test_find_disabled(): void {
        $this->resetAfterTest();
        $miro = $this->get_service('Miro');
        service::set_enabled($miro->id, false);
        $this->assertNull(service::find('https://miro.com/app/board/x=/'));
        $this->assertSame('Miro', service::find('https://miro.com/app/board/x=/', service::get_all())->name);
        service::set_enabled($miro->id, true);
        $this->assertSame('Miro', service::find('https://miro.com/app/board/x=/')->name);
    }

    /**
     * The link parameters of a service are added to the board link, unless it has them.
     */
    public function test_board_url(): void {
        $service = (object)['params' => 'autoplay=true&mode=view'];
        $cases = [
            'https://miro.com/app/board/x=/' => 'https://miro.com/app/board/x=/?autoplay=true&mode=view',
            'https://miro.com/app/board/x=/?share=1' => 'https://miro.com/app/board/x=/?share=1&autoplay=true&mode=view',
            'https://miro.com/app/board/x=/?autoplay=false' => 'https://miro.com/app/board/x=/?autoplay=false&mode=view',
            'https://miro.com/app/board/x=/?' => 'https://miro.com/app/board/x=/?autoplay=true&mode=view',
            'https://www.taskcards.de/#/board/x' => 'https://www.taskcards.de/?autoplay=true&mode=view#/board/x',
            'https://miro.com/app/board/x=/?autoplay=1&mode=2' => 'https://miro.com/app/board/x=/?autoplay=1&mode=2',
        ];
        foreach ($cases as $link => $expected) {
            $this->assertSame($expected, service::board_url($service, $link), $link);
        }
        $this->assertSame('https://a.org/b', service::board_url((object)['params' => ''], 'https://a.org/b'));
        $this->assertSame('https://a.org/b', service::board_url((object)['params' => null], 'https://a.org/b'));
    }

    /**
     * Services need a unique name, https links with a host and a path, and simple parameters.
     */
    public function test_validate(): void {
        $this->resetAfterTest();
        $valid = ['name' => 'Excalidraw', 'links' => "https://excalidraw.com/\n\nhttps://app.example.org:8443/b/",
            'params' => 'a=1&b-c=x_y.z~%20', 'display' => service::DISPLAY_NEWTAB];
        $this->assertSame([], service::validate((object)$valid));

        $miro = $this->get_service('Miro');
        $this->assertSame([], service::validate((object)(['id' => $miro->id, 'name' => 'MIRO'] + $valid)));

        $invalid = [
            'name' => ['', '  ', 'miro', str_repeat('x', 256)],
            'links' => ['', 'http://example.org/', 'https://example.org', 'https://localhost/', 'https://example.org/ b',
                'https://example.org/"x', "https://example.org/\nexample.org/", 'ftp://example.org/'],
            'params' => ['a', 'a=b c', 'a=b&', '&a=b', 'a=<b>', 'a=b#c'],
            'display' => [2, -1, 'embed'],
        ];
        foreach ($invalid as $field => $values) {
            foreach ($values as $value) {
                $errors = service::validate((object)([$field => $value] + $valid));
                $this->assertSame([$field], array_keys($errors), "$field: " . var_export($value, true));
            }
        }
    }

    /**
     * Services are saved with clean links, and can be disabled and deleted.
     */
    public function test_save_and_delete(): void {
        global $DB;
        $this->resetAfterTest();
        $id = service::save((object)['name' => ' Example ', 'links' => " https://example.org/b/ \r\n\r\nhttps://example.org/c/",
            'display' => service::DISPLAY_EMBED, 'enabled' => 1]);
        $service = service::get($id);
        $this->assertSame('Example', $service->name);
        $this->assertSame("https://example.org/b/\nhttps://example.org/c/", $service->links);
        $this->assertSame(['https://example.org/b/', 'https://example.org/c/'], service::parse_links($service->links));
        $this->assertSame('', (string)$service->params);
        $this->assertGreaterThan(0, $service->timecreated);

        service::save((object)['id' => $id, 'name' => 'Example', 'links' => 'https://example.org/d/', 'params' => 'x=1',
            'display' => service::DISPLAY_NEWTAB, 'enabled' => 0]);
        $service = service::get($id);
        $this->assertSame('https://example.org/d/', $service->links);
        $this->assertEquals(service::DISPLAY_NEWTAB, $service->display);
        $this->assertEquals(0, $service->enabled);

        service::delete($id);
        $this->assertFalse($DB->record_exists('whiteboard_service', ['id' => $id]));
        $this->assertFalse(service::get($id, IGNORE_MISSING));
    }

    /**
     * The services page counts the activities of each service.
     */
    public function test_count_activities(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator();
        $generator->create_module('whiteboard', ['course' => $course->id]);
        $generator->create_module('whiteboard', ['course' => $course->id, 'boardurl' => 'https://miro.com/app/live-embed/a=/']);
        $generator->create_module('whiteboard', ['course' => $course->id, 'boardurl' => 'https://www.taskcards.de/#/board/a']);
        $generator->create_module('whiteboard', ['course' => $course->id, 'boardurl' => 'https://example.org/board/a']);

        $services = service::get_all();
        $counts = service::count_activities($services);
        $byname = array_combine(array_column($services, 'name'), array_map(fn($s) => $counts[$s->id], $services));
        $this->assertSame(['Collaboard' => 0, 'Conceptboard' => 0, 'Miro' => 2, 'TaskCards' => 1], $byname);
    }

    /**
     * Exported services can be imported on another site; services with the same name are updated.
     */
    public function test_export_import(): void {
        $this->resetAfterTest();
        service::set_enabled($this->get_service('Collaboard')->id, false);
        $json = service::export();
        $exported = json_decode($json, true);
        $this->assertCount(4, $exported);
        $this->assertSame(['name' => 'Miro', 'links' => ['https://miro.com/app/board/', 'https://miro.com/app/live-embed/'],
            'params' => 'autoplay=true', 'display' => 'embed', 'enabled' => true], $exported[2]);
        $this->assertFalse($exported[0]['enabled']);
        $this->assertSame('newtab', $exported[1]['display']);

        // Import into a site where Miro was changed and TaskCards deleted.
        $miro = $this->get_service('Miro');
        service::save((object)['id' => $miro->id, 'name' => 'Miro', 'links' => 'https://miro.com/x/', 'display' => 1]);
        service::delete($this->get_service('TaskCards')->id);
        $exported[] = ['name' => 'Excalidraw', 'links' => 'https://excalidraw.com/', 'display' => 'newtab'];
        $result = service::import(service::parse_import(json_encode($exported)));
        $this->assertEquals((object)['added' => 2, 'updated' => 3], $result);
        $this->assertSame("https://miro.com/app/board/\nhttps://miro.com/app/live-embed/", $this->get_service('Miro')->links);
        $this->assertEquals(service::DISPLAY_EMBED, $this->get_service('Miro')->display);
        $this->assertEquals(1, $this->get_service('TaskCards')->enabled);
        $this->assertEquals(0, $this->get_service('Collaboard')->enabled);
        $this->assertEquals(1, $this->get_service('Excalidraw')->enabled);
        $this->assertCount(5, service::get_all());

        // A single service can be imported as an object.
        $services = service::parse_import('{"name": "WBO", "links": ["https://wbo.ophir.dev/boards/"], "enabled": false}');
        $this->assertCount(1, $services);
        $this->assertSame(0, $services[0]->id);
        $this->assertFalse($services[0]->enabled);
    }

    /**
     * Imports that are not valid are refused as a whole.
     */
    public function test_import_invalid(): void {
        $this->resetAfterTest();
        $cases = [
            'not json',
            '[]',
            '"Miro"',
            '[{"links": "https://example.org/"}]',
            '[{"name": "A", "links": "https://example.org/"}, {"name": "B", "links": "http://example.org/"}]',
            '[{"name": "A", "links": "https://example.org/"}, {"name": "a", "links": "https://example.org/"}]',
            '[{"name": "A", "links": "https://example.org/", "display": "popup"}]',
            '[{"name": "A", "links": "https://example.org/", "params": "a b"}]',
            '[{"name": "A", "links": [{"link": "https://example.org/"}]}]',
            '[{"name": "A", "links": {"link": "https://example.org/"}}]',
        ];
        foreach ($cases as $json) {
            try {
                service::parse_import($json);
                $this->fail('No exception for ' . $json);
            } catch (\moodle_exception $e) {
                $this->assertSame('importinvalid', $e->errorcode, $json);
            }
        }
        $this->assertCount(4, service::get_all());
    }

    /**
     * Board type and ID of version 1.0 become the links that version 1.0 showed.
     */
    public function test_legacy_board_url(): void {
        $cases = [
            ['miro', 'uXjVPfdzxzc=', 'https://miro.com/app/live-embed/uXjVPfdzxzc=/'],
            ['miro', ' /uXjVPfdzxzc=/ ', 'https://miro.com/app/live-embed/uXjVPfdzxzc=/'],
            ['conceptboard', '42za-8nk7-petm-xdao-aa3i', 'https://app.conceptboard.com/board/42za-8nk7-petm-xdao-aa3i'],
            ['conceptboard', 'a b"c', 'https://app.conceptboard.com/board/a%20b%22c'],
            ['miro', 'https://miro.com/app/board/x=/', 'https://miro.com/app/board/x=/'],
            ['miro', '', ''],
            [null, 'abc', ''],
            ['other', 'abc', ''],
        ];
        foreach ($cases as [$type, $id, $expected]) {
            $this->assertSame($expected, service::legacy_board_url($type, $id), "$type $id");
        }
    }
}
