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

#[\PHPUnit\Framework\Attributes\CoversClass(boards::class)]
/**
 * Tests for the boards of groups and users.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_whiteboard\boards
 */
final class boards_test extends \advanced_testcase {
    /**
     * Saving adds a board from a link or an embed code, and changes it when saved again.
     */
    public function test_save_and_get_link(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $whiteboard = $this->getDataGenerator()->create_module('whiteboard', ['course' => $course->id]);

        $id = boards::save($whiteboard->id, boards::GROUP, 11, '<iframe src="https://miro.com/app/board/a=/"></iframe>');
        boards::save($whiteboard->id, boards::USER, 11, 'https://miro.com/app/board/u=/');
        $this->assertSame('https://miro.com/app/board/a=/', boards::get_link($whiteboard->id, boards::GROUP, 11));
        $this->assertSame('https://miro.com/app/board/u=/', boards::get_link($whiteboard->id, boards::USER, 11));
        $this->assertNull(boards::get_link($whiteboard->id, boards::GROUP, 12));
        $this->assertNull(boards::get_link($whiteboard->id, boards::USER, 0));

        $this->assertSame($id, boards::save($whiteboard->id, boards::GROUP, 11, 'https://miro.com/app/board/b=/'));
        $this->assertSame([11 => 'https://miro.com/app/board/b=/'], boards::get_links($whiteboard->id, boards::GROUP));
        $this->assertSame([11 => 'https://miro.com/app/board/u=/'], boards::get_links($whiteboard->id, boards::USER));
        $this->assertSame(2, $DB->count_records('whiteboard_board', ['whiteboard' => $whiteboard->id]));

        $this->expectException(\coding_exception::class);
        boards::save($whiteboard->id, 'grouping', 11, 'https://miro.com/app/board/b=/');
    }

    /**
     * Teachers see the boards of groups and users sorted by name, and can add boards for the others.
     */
    public function test_get_list_and_choices(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $groupb = $generator->create_group(['courseid' => $course->id, 'name' => 'Group B']);
        $groupa = $generator->create_group(['courseid' => $course->id, 'name' => 'Group A']);
        $groupc = $generator->create_group(['courseid' => $course->id, 'name' => 'Group C']);
        $grouping = $generator->create_grouping(['courseid' => $course->id]);
        $generator->create_grouping_group(['groupingid' => $grouping->id, 'groupid' => $groupa->id]);
        $generator->create_grouping_group(['groupingid' => $grouping->id, 'groupid' => $groupb->id]);
        $student1 = $generator->create_and_enrol($course, 'student', ['firstname' => 'Zoe', 'lastname' => 'Adams']);
        $student2 = $generator->create_and_enrol($course, 'student', ['firstname' => 'Ann', 'lastname' => 'Brown']);
        $student3 = $generator->create_and_enrol($course, 'student', ['firstname' => 'Bob', 'lastname' => 'Clark']);
        $outsider = $generator->create_user(['firstname' => 'Eve', 'lastname' => 'Doe']);
        $whiteboard = $generator->create_module('whiteboard', [
            'course' => $course->id,
            'groupmode' => SEPARATEGROUPS,
            'groupingid' => $grouping->id,
        ]);
        $cm = get_fast_modinfo($course)->get_cm($whiteboard->cmid);
        $plugingenerator = $generator->get_plugin_generator('mod_whiteboard');
        foreach ([$groupb, $groupa, $groupc] as $group) {
            $plugingenerator->create_board([
                'whiteboardid' => $whiteboard->id,
                'groupid' => $group->id,
                'boardurl' => 'https://miro.com/app/board/' . $group->id . '=/',
            ]);
        }
        foreach ([$student2, $student1, $outsider] as $user) {
            $plugingenerator->create_board([
                'whiteboardid' => $whiteboard->id,
                'userid' => $user->id,
                'boardurl' => 'https://miro.com/app/board/u' . $user->id . '=/',
            ]);
        }
        $this->setAdminUser();

        // Group C is not in the grouping of the whiteboard: it never sees its board.
        $list = boards::get_list($cm, boards::GROUP);
        $this->assertSame(['Group A', 'Group B', 'Group C'], array_values(array_column($list, 'name')));
        $this->assertSame([true, true, false], array_values(array_column($list, 'active')));
        $this->assertSame([], boards::get_choices($cm, boards::GROUP));

        // The user who is not enrolled keeps the board, which is not shown.
        $list = boards::get_list($cm, boards::USER);
        $this->assertSame(['Zoe Adams', 'Ann Brown', 'Eve Doe'], array_values(array_column($list, 'name')));
        $this->assertSame([true, true, false], array_values(array_column($list, 'active')));
        $this->assertEquals([$student3->id], array_keys(boards::get_choices($cm, boards::USER)));

        $board = boards::get_managed($cm, array_key_first($list));
        $this->assertSame('Zoe Adams', $board->name);
        $this->assertSame(boards::USER, boards::get_type($board));
    }

    /**
     * Without access to all groups, in separate groups, teachers only manage the boards of their own groups and users.
     */
    public function test_separate_groups_without_access_to_all_groups(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $groupa = $generator->create_group(['courseid' => $course->id, 'name' => 'Group A']);
        $groupb = $generator->create_group(['courseid' => $course->id, 'name' => 'Group B']);
        $teacher = $generator->create_and_enrol($course, 'teacher');
        $student1 = $generator->create_and_enrol($course, 'student');
        $student2 = $generator->create_and_enrol($course, 'student');
        $generator->create_group_member(['groupid' => $groupa->id, 'userid' => $teacher->id]);
        $generator->create_group_member(['groupid' => $groupa->id, 'userid' => $student1->id]);
        $generator->create_group_member(['groupid' => $groupb->id, 'userid' => $student2->id]);
        $whiteboard = $generator->create_module('whiteboard', ['course' => $course->id, 'groupmode' => SEPARATEGROUPS]);
        $cm = get_fast_modinfo($course)->get_cm($whiteboard->cmid);
        $teacherrole = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        assign_capability('mod/whiteboard:manageboards', CAP_ALLOW, $teacherrole, $cm->context->id);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $teacherrole, $cm->context->id);
        $boardb = $generator->get_plugin_generator('mod_whiteboard')->create_board([
            'whiteboardid' => $whiteboard->id,
            'groupid' => $groupb->id,
            'boardurl' => 'https://miro.com/app/board/b=/',
        ]);
        $this->setUser($teacher);

        $this->assertFalse(boards::can_access_all_groups($cm));
        $this->assertEquals([$groupa->id], array_keys(boards::get_groups($cm)));
        $this->assertSame([], boards::get_list($cm, boards::GROUP));
        $this->assertEquals([$groupa->id], array_keys(boards::get_choices($cm, boards::GROUP)));
        $choices = boards::get_choices($cm, boards::USER);
        $this->assertArrayHasKey($student1->id, $choices);
        $this->assertArrayNotHasKey($student2->id, $choices);

        $this->expectException(\moodle_exception::class);
        boards::get_managed($cm, $boardb->id);
    }

    /**
     * When a group or user is deleted, their boards are deleted. Resetting a course deletes the boards of users.
     */
    public function test_delete(): void {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $groupa = $generator->create_group(['courseid' => $course->id]);
        $groupb = $generator->create_group(['courseid' => $course->id]);
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $whiteboard = $generator->create_module('whiteboard', ['course' => $course->id]);
        $plugingenerator = $generator->get_plugin_generator('mod_whiteboard');
        foreach (['groupid' => [$groupa->id, $groupb->id], 'userid' => [$user1->id, $user2->id]] as $field => $ids) {
            foreach ($ids as $id) {
                $plugingenerator->create_board([
                    'whiteboardid' => $whiteboard->id,
                    $field => $id,
                    'boardurl' => 'https://miro.com/app/board/' . $id . '=/',
                ]);
            }
        }

        groups_delete_group($groupa->id);
        delete_user($user1);
        $this->assertEquals([$groupb->id], array_keys(boards::get_links($whiteboard->id, boards::GROUP)));
        $this->assertEquals([$user2->id], array_keys(boards::get_links($whiteboard->id, boards::USER)));

        boards::delete_user_boards([$whiteboard->id]);
        $this->assertEquals([$groupb->id], array_keys(boards::get_links($whiteboard->id, boards::GROUP)));
        $this->assertSame([], boards::get_links($whiteboard->id, boards::USER));

        boards::delete_for_whiteboard($whiteboard->id);
        $this->assertSame([], boards::get_links($whiteboard->id, boards::GROUP));
    }
}
