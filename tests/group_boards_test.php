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

#[\PHPUnit\Framework\Attributes\CoversClass(group_boards::class)]
/**
 * Tests for the boards of groups.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_whiteboard\group_boards
 */
final class group_boards_test extends \advanced_testcase {
    /**
     * Groups see their board; groups without one, and "All participants", see the board link of the whiteboard.
     */
    public function test_get_link(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $groupa = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $groupb = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $instance = $this->getDataGenerator()->create_module('whiteboard', [
            'course' => $course->id,
            'boardurl' => 'https://miro.com/app/board/all=/',
            'groupboard' => [$groupa->id => 'https://miro.com/app/board/a=/'],
        ]);
        $whiteboard = $DB->get_record('whiteboard', ['id' => $instance->id]);

        $this->assertSame('https://miro.com/app/board/a=/', group_boards::get_link($whiteboard, $groupa->id));
        $this->assertSame('https://miro.com/app/board/all=/', group_boards::get_link($whiteboard, $groupb->id));
        $this->assertSame('https://miro.com/app/board/all=/', group_boards::get_link($whiteboard, 0));
    }

    /**
     * Saving adds, changes and removes boards, and leaves groups that are not given alone.
     */
    public function test_save(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $whiteboard = $this->getDataGenerator()->create_module('whiteboard', ['course' => $course->id]);

        group_boards::save($whiteboard->id, [11 => 'https://miro.com/app/board/a=/', 12 => 'https://miro.com/app/board/b=/']);
        $before = $DB->get_record('whiteboard_board', ['whiteboard' => $whiteboard->id, 'groupid' => 12]);
        group_boards::save($whiteboard->id, [
            11 => '<iframe src="https://miro.com/app/board/c=/"></iframe>',
            12 => 'https://miro.com/app/board/b=/',
            13 => '',
        ]);
        $this->assertSame(
            [11 => 'https://miro.com/app/board/c=/', 12 => 'https://miro.com/app/board/b=/'],
            group_boards::get($whiteboard->id)
        );
        $this->assertEquals($before, $DB->get_record('whiteboard_board', ['whiteboard' => $whiteboard->id, 'groupid' => 12]));

        group_boards::save($whiteboard->id, [11 => '']);
        $this->assertSame([12 => 'https://miro.com/app/board/b=/'], group_boards::get($whiteboard->id));
    }

    /**
     * When a group is deleted, its boards are deleted.
     */
    public function test_group_deleted(): void {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $groupa = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $groupb = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $whiteboard = $this->getDataGenerator()->create_module('whiteboard', [
            'course' => $course->id,
            'groupboard' => [$groupa->id => 'https://miro.com/app/board/a=/', $groupb->id => 'https://miro.com/app/board/b=/'],
        ]);

        groups_delete_group($groupa->id);
        $this->assertSame([$groupb->id => 'https://miro.com/app/board/b=/'], group_boards::get($whiteboard->id));
    }
}
