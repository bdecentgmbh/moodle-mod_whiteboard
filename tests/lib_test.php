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

#[\PHPUnit\Framework\Attributes\CoversFunction('whiteboard_supports')]
#[\PHPUnit\Framework\Attributes\CoversFunction('whiteboard_add_instance')]
#[\PHPUnit\Framework\Attributes\CoversFunction('whiteboard_update_instance')]
#[\PHPUnit\Framework\Attributes\CoversFunction('whiteboard_delete_instance')]
#[\PHPUnit\Framework\Attributes\CoversFunction('whiteboard_get_coursemodule_info')]
#[\PHPUnit\Framework\Attributes\CoversFunction('whiteboard_view')]
/**
 * Tests for the functions in lib.php.
 *
 * @package    mod_whiteboard
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::whiteboard_supports
 * @covers ::whiteboard_add_instance
 * @covers ::whiteboard_update_instance
 * @covers ::whiteboard_delete_instance
 * @covers ::whiteboard_get_coursemodule_info
 * @covers ::whiteboard_view
 */
final class lib_test extends \advanced_testcase {
    #[\Override]
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/whiteboard/lib.php');
        parent::setUpBeforeClass();
    }

    /**
     * The whiteboard is a collaboration activity with a description, completion on view and backup.
     */
    public function test_supports(): void {
        $this->assertSame(MOD_PURPOSE_COLLABORATION, whiteboard_supports(FEATURE_MOD_PURPOSE));
        $this->assertTrue(whiteboard_supports(FEATURE_SHOW_DESCRIPTION));
        $this->assertTrue(whiteboard_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertTrue(whiteboard_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertFalse(whiteboard_supports(FEATURE_GROUPS));
        $this->assertFalse(whiteboard_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertNull(whiteboard_supports(FEATURE_COMMENT));
    }

    /**
     * An embed code is saved as the link it contains, when added and when updated.
     */
    public function test_add_update_instance(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $whiteboard = $this->getDataGenerator()->create_module('whiteboard', [
            'course' => $course->id,
            'boardurl' => ' <iframe width="768" src="https://miro.com/app/live-embed/o9J_kodlngQ=/?a=1&amp;b=2"></iframe> ',
        ]);
        $record = $DB->get_record('whiteboard', ['id' => $whiteboard->id]);
        $this->assertSame('https://miro.com/app/live-embed/o9J_kodlngQ=/?a=1&b=2', $record->boardurl);
        $this->assertGreaterThan(0, $record->timecreated);

        $cm = get_coursemodule_from_id('whiteboard', $whiteboard->cmid);
        $data = (object)[
            'instance' => $whiteboard->id,
            'coursemodule' => $cm->id,
            'name' => 'Ideas',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'boardurl' => '<iframe src="https://www.taskcards.de/#/board/abc"></iframe>',
        ];
        $this->assertTrue(whiteboard_update_instance($data));
        $record = $DB->get_record('whiteboard', ['id' => $whiteboard->id]);
        $this->assertSame('Ideas', $record->name);
        $this->assertSame('https://www.taskcards.de/#/board/abc', $record->boardurl);
    }

    /**
     * Deleting removes the whiteboard.
     */
    public function test_delete_instance(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $whiteboard = $this->getDataGenerator()->create_module('whiteboard', ['course' => $course->id]);
        $this->assertTrue(whiteboard_delete_instance($whiteboard->id));
        $this->assertFalse($DB->record_exists('whiteboard', ['id' => $whiteboard->id]));
        $this->assertFalse(whiteboard_delete_instance($whiteboard->id));
    }

    /**
     * "Display description on course page" shows the description on the course page.
     */
    public function test_get_coursemodule_info(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $shown = $this->getDataGenerator()->create_module('whiteboard', [
            'course' => $course->id, 'name' => 'Shown', 'intro' => 'Brainstorm here', 'showdescription' => 1,
        ]);
        $hidden = $this->getDataGenerator()->create_module('whiteboard', [
            'course' => $course->id, 'name' => 'Hidden', 'intro' => 'Not on the course page', 'showdescription' => 0,
        ]);
        $modinfo = get_fast_modinfo($course);
        $this->assertSame('Shown', $modinfo->get_cm($shown->cmid)->name);
        $this->assertStringContainsString('Brainstorm here', $modinfo->get_cm($shown->cmid)->get_formatted_content());
        $this->assertSame('', $modinfo->get_cm($hidden->cmid)->get_formatted_content());
    }

    /**
     * Viewing triggers the viewed event and completes the activity.
     */
    public function test_view(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $whiteboard = $this->getDataGenerator()->create_module('whiteboard', [
            'course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => COMPLETION_VIEW_REQUIRED,
        ]);
        $cm = get_coursemodule_from_id('whiteboard', $whiteboard->cmid);
        $context = \context_module::instance($cm->id);
        $this->setUser($student);

        $sink = $this->redirectEvents();
        whiteboard_view($DB->get_record('whiteboard', ['id' => $whiteboard->id]), $course, $cm, $context);
        $events = array_values(array_filter(
            $sink->get_events(),
            fn($event) => $event instanceof \mod_whiteboard\event\course_module_viewed
        ));
        $sink->close();
        $this->assertCount(1, $events);
        $this->assertEquals($context, $events[0]->get_context());
        $this->assertEquals($whiteboard->id, $events[0]->objectid);

        $completion = new \completion_info($course);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm, false, $student->id)->completionstate);
    }
}
