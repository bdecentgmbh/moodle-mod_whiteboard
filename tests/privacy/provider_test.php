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

namespace mod_whiteboard\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_whiteboard\boards;

#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
/**
 * Tests for the privacy provider of the whiteboard.
 *
 * @package    mod_whiteboard
 * @category   test
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_whiteboard\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** @var \stdClass The whiteboard. */
    protected $whiteboard;

    /** @var \context_module The context of the whiteboard. */
    protected $context;

    /** @var \stdClass[] Two users with a board each. */
    protected $users;

    /** @var int The group with a board. */
    protected $groupid;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $this->whiteboard = $generator->create_module('whiteboard', ['course' => $course->id]);
        $this->context = \context_module::instance($this->whiteboard->cmid);
        $this->groupid = $generator->create_group(['courseid' => $course->id])->id;
        $this->users = [$generator->create_user(), $generator->create_user()];
        $plugingenerator = $generator->get_plugin_generator('mod_whiteboard');
        $plugingenerator->create_board([
            'whiteboardid' => $this->whiteboard->id,
            'groupid' => $this->groupid,
            'boardurl' => 'https://miro.com/app/board/g=/',
        ]);
        foreach ($this->users as $i => $user) {
            $plugingenerator->create_board([
                'whiteboardid' => $this->whiteboard->id,
                'userid' => $user->id,
                'boardurl' => 'https://miro.com/app/board/u' . $i . '=/',
            ]);
        }
    }

    /**
     * The boards of users are described.
     */
    public function test_get_metadata(): void {
        $items = provider::get_metadata(new \core_privacy\local\metadata\collection('mod_whiteboard'))->get_collection();
        $this->assertCount(1, $items);
        $this->assertSame('whiteboard_board', $items[0]->get_name());
        $this->assertArrayHasKey('userid', $items[0]->get_privacy_fields());
    }

    /**
     * Users with a board are found in the whiteboard; others are not.
     */
    public function test_get_contexts_and_users(): void {
        $this->assertEquals([$this->context->id], provider::get_contexts_for_userid($this->users[0]->id)->get_contextids());
        $this->assertEquals([], provider::get_contexts_for_userid($this->getDataGenerator()->create_user()->id)->get_contextids());

        $userlist = new userlist($this->context, 'mod_whiteboard');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing(array_column($this->users, 'id'), $userlist->get_userids());
    }

    /**
     * The board of a user is exported with the whiteboard.
     */
    public function test_export_user_data(): void {
        provider::export_user_data(new approved_contextlist($this->users[1], 'mod_whiteboard', [$this->context->id]));
        $data = writer::with_context($this->context)->get_data();
        $this->assertSame('https://miro.com/app/board/u1=/', $data->boardurl);
        $this->assertSame($this->whiteboard->name, $data->name);
    }

    /**
     * Deleting for a user, for some users and for all users deletes their boards and keeps the boards of groups.
     */
    public function test_delete(): void {
        provider::delete_data_for_user(new approved_contextlist($this->users[0], 'mod_whiteboard', [$this->context->id]));
        $this->assertEquals([$this->users[1]->id], array_keys(boards::get_links($this->whiteboard->id, boards::USER)));

        provider::delete_data_for_users(new approved_userlist($this->context, 'mod_whiteboard', [$this->users[1]->id]));
        $this->assertSame([], boards::get_links($this->whiteboard->id, boards::USER));

        boards::save($this->whiteboard->id, boards::USER, $this->users[0]->id, 'https://miro.com/app/board/u=/');
        provider::delete_data_for_all_users_in_context($this->context);
        $this->assertSame([], boards::get_links($this->whiteboard->id, boards::USER));
        $this->assertEquals([$this->groupid], array_keys(boards::get_links($this->whiteboard->id, boards::GROUP)));
    }
}
