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

/**
 * Javascript to handle sorting actions for grading.
 *
 * @module     mod_externalassignment/repository
 * @copyright  2026 Marcel Suter and Kevin Maurizi
 * @author     2026 Marcel Suter <marcel.suter+ea@bzz.ch>
 * @author     2026 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.3
 */
import {call as fetchMany} from 'core/ajax';

export const fetchAllStudents = (
    coursemoduleid,
    sort,
    tdir,
    status
) => fetchMany([{
    methodname: 'mod_externalassignment_read_students',
    args: {
        coursemoduleid,
        sort,
        tdir,
        status
    },
}])[0];