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

/**
 * Logstore socialflow installation tasks
 *
 * @package     logstore_socialflow
 * Fork of logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Modified by Zabelle Motte (UCLouvain)
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install the plugin.
 */
function xmldb_logstore_socialflow_install() {
    global $DB;
    // Define records so that events linked to default Moodle activites are tracked
    $records = array(
        array('id' => '1', 'eventname' => '\\mod_assign\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '2', 'eventname' => '\\mod_assign\\event\\assessable_submitted', 'actiontype' => 'contrib'),
        array('id' => '3', 'eventname' => '\\mod_workshop\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '4', 'eventname' => '\\mod_workshop\\event\\submission_created', 'actiontype' => 'contrib'),
        array('id' => '5', 'eventname' => '\\mod_workshop\\event\\submission_assessed', 'actiontype' => 'contrib'),
        array('id' => '6', 'eventname' => '\\mod_data\\event\\course_module_viewd​', 'actiontype' => 'consult'),
        array('id' => '7', 'eventname' => '\\mod_data\\event\\record_created', 'actiontype' => 'contrib'),
        array('id' => '8', 'eventname' => '\\mod_folder\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '9', 'eventname' => '\\mod_feedback\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '10', 'eventname' => '\\mod_feedback\\event\\response_submitted', 'actiontype' => 'contrib'),
        array('id' => '11', 'eventname' => '\\mod_resource\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '12', 'eventname' => '\\mod_forum\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '13', 'eventname' => '\\mod_forum\\event\\post_created', 'actiontype' => 'contrib'),
        array('id' => '14', 'eventname' => '\\mod_forum\\event\\discussion_created', 'actiontype' => 'contrib'),
        array('id' => '15', 'eventname' => '\\mod_glossary\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '16', 'eventname' => '\\mod_glossary\\event\\entry_created', 'actiontype' => 'contrib'),
        array('id' => '17', 'eventname' => '\\mod_h5pactivity\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '18', 'eventname' => '\\mod_lesson\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '19', 'eventname' => '\\mod_lesson\\event\\lesson_ended', 'actiontype' => 'contrib'),
        array('id' => '20', 'eventname' => '\\mod_book\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '21', 'eventname' => '\\mod_lti\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '22', 'eventname' => '\\mod_page\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '23', 'eventname' => '\\mod_imscp\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '24', 'eventname' => '\\mod_scorm\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '25', 'eventname' => '\\mod_choice\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '26', 'eventname' => '\\mod_choice\\event\\answer_submitted', 'actiontype' => 'contrib'),
        array('id' => '27', 'eventname' => '\\mod_quiz\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '28', 'eventname' => '\\mod_quiz\\event\\attempt_submitted', 'actiontype' => 'contrib'),
        array('id' => '29', 'eventname' => '\\mod_url\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '30', 'eventname' => '\\mod_wiki\\event\\course_module_viewed', 'actiontype' => 'consult'),
        array('id' => '31', 'eventname' => '\\mod_wiki\\event\\page_updated', 'actiontype' => 'contrib')
    );

    // Insert records into the table
    $DB->insert_records('logstore_socialflow_evts', $records);
}