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

#[\PHPUnit\Framework\Attributes\CoversClass(\backup_whiteboard_activity_task::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\backup_whiteboard_activity_structure_step::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_whiteboard_activity_task::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_whiteboard_activity_structure_step::class)]
/**
 * Tests for the backup and restore of whiteboards.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \backup_whiteboard_activity_task
 * @covers \backup_whiteboard_activity_structure_step
 * @covers \restore_whiteboard_activity_task
 * @covers \restore_whiteboard_activity_structure_step
 */
final class backup_restore_test extends \advanced_testcase {
    #[\Override]
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/course/lib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Backs up a course for import, or with user data.
     *
     * @param int $courseid
     * @param bool $userdata Whether to back up the users and their data.
     * @return string The backup id: its files are in the backup temp directory of that name.
     */
    protected function backup(int $courseid, bool $userdata = false): string {
        global $USER;
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $courseid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            $userdata ? \backup::MODE_GENERAL : \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        if ($userdata) {
            $bc->get_plan()->get_setting('users')->set_value(true);
        }
        $bc->execute_plan();
        if ($userdata) {
            $file = $bc->get_results()['backup_destination'];
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), make_backup_temp_directory($backupid));
            $file->delete();
        }
        $bc->destroy();
        return $backupid;
    }

    /**
     * Restores a backup into a new course.
     *
     * @param string $backupid
     * @param int $categoryid
     * @param bool $userdata Whether the backup has user data.
     * @return int The id of the new course.
     */
    protected function restore(string $backupid, int $categoryid, bool $userdata = false): int {
        global $USER;
        $courseid = \restore_dbops::create_new_course('Copy', 'copy', $categoryid);
        $rc = new \restore_controller(
            $backupid,
            $courseid,
            \backup::INTERACTIVE_NO,
            $userdata ? \backup::MODE_GENERAL : \backup::MODE_IMPORT,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
        return $courseid;
    }

    /**
     * A copied course has the whiteboard with its name, description and board link.
     */
    public function test_backup_restore(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('whiteboard', [
            'course' => $course->id,
            'name' => 'Ideas',
            'intro' => 'Add your ideas',
            'boardurl' => 'https://www.taskcards.de/#/board/43cc9837-9499-40ff-b6a9-d47eb96b024f',
        ]);

        $newcourseid = $this->restore($this->backup($course->id), $course->category);

        $restored = $DB->get_record('whiteboard', ['course' => $newcourseid], '*', MUST_EXIST);
        $this->assertSame('Ideas', $restored->name);
        $this->assertSame('Add your ideas', $restored->intro);
        $this->assertSame('https://www.taskcards.de/#/board/43cc9837-9499-40ff-b6a9-d47eb96b024f', $restored->boardurl);
    }

    /**
     * Creates a course with a group and a student, and a whiteboard with a board for each of them.
     *
     * @return array The course, group, student and whiteboard.
     */
    protected function create_course_with_boards(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $group = $generator->create_group(['courseid' => $course->id, 'name' => 'Team A']);
        $student = $generator->create_and_enrol($course, 'student');
        $whiteboard = $generator->create_module('whiteboard', ['course' => $course->id, 'groupmode' => SEPARATEGROUPS]);
        $plugingenerator = $generator->get_plugin_generator('mod_whiteboard');
        $plugingenerator->create_board([
            'whiteboardid' => $whiteboard->id,
            'groupid' => $group->id,
            'boardurl' => 'https://www.taskcards.de/#/board/a',
        ]);
        $plugingenerator->create_board([
            'whiteboardid' => $whiteboard->id,
            'userid' => $student->id,
            'boardurl' => 'https://www.taskcards.de/#/board/u',
        ]);
        return [$course, $group, $student, $whiteboard];
    }

    /**
     * A copied course has the boards of the groups, for the copies of the groups, but not the boards of users.
     */
    public function test_backup_restore_group_boards(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course] = $this->create_course_with_boards();

        $newcourseid = $this->restore($this->backup($course->id), $course->category);

        $restored = $DB->get_record('whiteboard', ['course' => $newcourseid], '*', MUST_EXIST);
        $newgroup = $DB->get_record('groups', ['courseid' => $newcourseid, 'name' => 'Team A'], '*', MUST_EXIST);
        $this->assertSame([$newgroup->id => 'https://www.taskcards.de/#/board/a'], boards::get_links($restored->id, boards::GROUP));
        $this->assertSame([], boards::get_links($restored->id, boards::USER));
    }

    /**
     * A course restored with user data has the boards of the users too.
     */
    public function test_backup_restore_user_boards(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course, , $student] = $this->create_course_with_boards();

        $newcourseid = $this->restore($this->backup($course->id, true), $course->category, true);

        $restored = $DB->get_record('whiteboard', ['course' => $newcourseid], '*', MUST_EXIST);
        $this->assertSame([$student->id => 'https://www.taskcards.de/#/board/u'], boards::get_links($restored->id, boards::USER));
        $this->assertCount(1, boards::get_links($restored->id, boards::GROUP));
    }

    /**
     * A duplicated whiteboard has the boards of the same groups, and no boards of users.
     */
    public function test_duplicate_group_boards(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course, $group, , $whiteboard] = $this->create_course_with_boards();

        if (method_exists(\core_courseformat\local\cmactions::class, 'duplicate')) {
            // Moodle 5.2 and later.
            $newcm = \core_courseformat\formatactions::cm($course)->duplicate($whiteboard->cmid);
        } else {
            $newcm = duplicate_module($course, get_fast_modinfo($course)->get_cm($whiteboard->cmid));
        }

        $this->assertSame([$group->id => 'https://www.taskcards.de/#/board/a'], boards::get_links($newcm->instance, boards::GROUP));
        $this->assertSame([], boards::get_links($newcm->instance, boards::USER));
    }

    /**
     * Backups of version 1.0, with board type and ID, are restored with the board link.
     */
    public function test_restore_legacy_backup(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $whiteboard = $this->getDataGenerator()->create_module('whiteboard', ['course' => $course->id]);
        $backupid = $this->backup($course->id);

        // Turn the backup into one of version 1.0.
        $file = make_backup_temp_directory($backupid) . "/activities/whiteboard_{$whiteboard->cmid}/whiteboard.xml";
        $xml = file_get_contents($file);
        $legacy = preg_replace(
            '~<boardurl>.*?</boardurl>~',
            '<boardtype>miro</boardtype><boardid>o9J_kodlngQ=</boardid>',
            $xml,
            1,
            $count
        );
        $this->assertSame(1, $count);
        file_put_contents($file, $legacy);

        $newcourseid = $this->restore($backupid, $course->category);

        $restored = $DB->get_record('whiteboard', ['course' => $newcourseid], '*', MUST_EXIST);
        $this->assertSame('https://miro.com/app/live-embed/o9J_kodlngQ=/', $restored->boardurl);
        $this->assertSame('Miro', service::find($restored->boardurl)->name);
    }
}
