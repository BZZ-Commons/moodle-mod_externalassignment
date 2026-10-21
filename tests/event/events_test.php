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

namespace mod_externalassignment\event;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/externalassignment/lib.php');

/**
 * Tests for the view events of mod_externalassignment.
 *
 * @package   mod_externalassignment
 * @copyright 2026 Marcel Suter and Kevin Maurizi
 * @author    2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author    2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_externalassignment\event\course_module_viewed
 * @covers \mod_externalassignment\event\course_module_instance_list_viewed
 */
final class events_test extends \advanced_testcase {
    /**
     * The course module viewed event carries the right data.
     */
    public function test_course_module_viewed(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->get_plugin_generator('mod_externalassignment')->create_instance(
            ['course' => $course->id, 'externalname' => 'externalname', 'externalgrademax' => 100]
        );
        $context = \context_module::instance($instance->cmid);

        $sink = $this->redirectEvents();
        $cm = get_coursemodule_from_instance('externalassignment', $instance->id);
        externalassignment_view($instance, $course, $cm, $context);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_externalassignment\event\course_module_viewed', $event);
        $this->assertEquals($context->id, $event->contextid);
        $this->assertEquals($instance->id, $event->objectid);
        $this->assertEquals('externalassignment', $event->objecttable);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * The instance list viewed event is triggered for the course context.
     */
    public function test_course_module_instance_list_viewed(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $sink = $this->redirectEvents();
        course_module_instance_list_viewed::create(['context' => $context])->trigger();
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertEquals($context->id, reset($events)->contextid);
    }
}
