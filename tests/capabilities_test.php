<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_externalassignment;

use core_external\external_api;
use mod_externalassignment\external\read_students;
use mod_externalassignment\local\assign;
use mod_externalassignment\local\grade;
use mod_externalassignment\output\view_grading;
use mod_externalassignment\output\view_student;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use required_capability_exception;

/**
 * Tests the default capabilities of the roles (GitHub issue #52)
 *
 * @package mod_externalassignment
 * @category test
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
#[Group('mod_externalassignment')]
final class capabilities_test extends \advanced_testcase {
    /**
     * Non-editing teachers may only view, editing teachers may grade and grant extensions.
     */
    public function test_default_capabilities(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('externalassignment', ['course' => $course->id]);
        $context = \context_module::instance($instance->cmid);

        $nonediting = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $editing = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $write = ['mod/externalassignment:grade', 'mod/externalassignment:grantextension'];
        $view = 'mod/externalassignment:viewgrades';
        $this->assertTrue(has_capability($view, $context, $nonediting));
        $this->assertTrue(has_capability($view, $context, $editing));
        $this->assertFalse(has_capability($view, $context, $student));
        foreach ($write as $capability) {
            $this->assertFalse(has_capability($capability, $context, $nonediting));
            $this->assertTrue(has_capability($capability, $context, $editing));
            $this->assertFalse(has_capability($capability, $context, $student));
        }
        $this->assertNull(get_capability_info('mod/externalassignment:managegrades'));
        $this->assertNull(get_capability_info('mod/externalassignment:reviewgrades'));
    }

    /**
     * Creates a course with an assignment and two graded students.
     * @return array [course, instance, student1, student2]
     */
    private function setup_graded_course(): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('externalassignment', ['course' => $course->id]);
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student', ['firstname' => 'Anna', 'lastname' => 'Adler']);
        $student2 = $this->getDataGenerator()->create_and_enrol(
            $course,
            'student',
            ['firstname' => 'Bert', 'lastname' => 'Zeller']
        );
        foreach ([$student1->id => 10, $student2->id => 20] as $userid => $points) {
            $DB->insert_record('externalassignment_grades', (object)[
                'externalassignment' => $instance->id,
                'userid' => $userid,
                'grader' => 2,
                'externallink' => '',
                'externalgrade' => $points,
                'manualgrade' => 0,
            ]);
        }
        return [$course, $instance, $student1, $student2];
    }

    /**
     * The roles that may view the grades of all students
     * @return array
     */
    public static function teacher_roles(): array {
        return [
            'non-editing teacher' => ['teacher', false],
            'editing teacher' => ['editingteacher', true],
        ];
    }

    /**
     * Teachers, editing or not, see the grades of all students.
     * @param string $role the role of the teacher
     * @param bool $canwrite whether this role may also grade and grant extensions
     */
    #[DataProvider('teacher_roles')]
    public function test_teachers_view_grades_of_all_students(string $role, bool $canwrite): void {
        global $PAGE;
        $this->resetAfterTest(true);
        [$course, $instance, $student1, $student2] = $this->setup_graded_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, $role);
        $this->setUser($teacher);
        $context = \context_module::instance($instance->cmid);
        $PAGE->set_url('/mod/externalassignment/view.php', ['id' => $instance->cmid, 'action' => 'grading']);

        // The grading overview lists every student with the grade.
        $view = new view_grading($instance->cmid, $context, 'lastname', 'asc');
        $data = $view->export_for_template($PAGE->get_renderer('core'));
        $this->assertCount(2, $data->students);
        $this->assertEquals($student1->id, $data->students[0]->userid);
        $this->assertEquals('10.00', $data->students[0]->gradefinal);
        $this->assertEquals($student2->id, $data->students[1]->userid);
        $this->assertEquals('20.00', $data->students[1]->gradefinal);
        // Only roles with the write capabilities get the grade and extension controls.
        $this->assertSame($canwrite, $data->cangrade);
        $this->assertSame($canwrite, $data->canextend);

        // The student list of the web service.
        $result = external_api::clean_returnvalue(
            read_students::execute_returns(),
            read_students::execute($instance->cmid, 'lastname', 'asc', '')
        );
        $this->assertEqualsCanonicalizing([$student1->id, $student2->id], array_column($result, 'userid'));
    }

    /**
     * Students see their own grade, and nothing of the other students.
     */
    public function test_students_view_only_their_own_grade(): void {
        global $PAGE;
        $this->resetAfterTest(true);
        [, $instance, $student1, $student2] = $this->setup_graded_course();
        $context = \context_module::instance($instance->cmid);
        $renderer = $PAGE->get_renderer('core');

        foreach ([$student1->id => '10.00', $student2->id => '20.00'] as $userid => $expected) {
            $this->setUser($userid);
            $this->assertTrue(has_capability('mod/externalassignment:view', $context));
            $assign = new assign(null, $context);
            $assign->load_db($instance->cmid);
            $grade = new grade(null);
            $grade->load_db($assign->get_id(), $userid);
            $data = (new view_student($instance->cmid, $context, $assign, $grade, $userid))->export_for_template($renderer);
            $this->assertEquals($expected, $data->totalgrade);
        }
    }

    /**
     * Students may neither open the grading overview nor read the student list.
     */
    public function test_students_cannot_view_grades_of_others(): void {
        $this->resetAfterTest(true);
        [, $instance, $student1] = $this->setup_graded_course();
        $context = \context_module::instance($instance->cmid);
        $this->setUser($student1);

        $this->assertFalse(has_capability('mod/externalassignment:viewgrades', $context));
        $this->expectException(required_capability_exception::class);
        read_students::execute($instance->cmid, 'lastname', 'asc', '');
    }
}
