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

defined('MOODLE_INTERNAL'] || die(];

/**
 * Install the plugin.
 */
function xmldb_logstore_socialflow_install() {
    global $DB;
    // We define records so that events linked to Moodle activites are tracked.
    // As admin, you may add lines in this table with informations about other events to track.
    // This may enable you to add events linked to other additionnal plugins.

    $records = [
        ['id' => '1', 'eventname' => '\\mod_assign\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'assign', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '2', 'eventname' => '\\mod_assign\\event\\assessable_submitted', 'actiontype' => 'contrib',
        'moduletable' => 'assign', 'hasclosingdate' => '1', 'closingdatefield' => 'cutoffdate',
        'haslatesubmit' => '1', 'latedatefield' => 'duedate'],
        ['id' => '3', 'eventname' => '\\mod_workshop\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'workshop', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '4', 'eventname' => '\\mod_workshop\\event\\submission_created', 'actiontype' => 'contrib',
        'moduletable' => 'workshop', 'hasclosingdate' => '1', 'closingdatefield' => 'submissionend',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '5', 'eventname' => '\\mod_workshop\\event\\submission_assessed', 'actiontype' => 'contrib',
        'moduletable' => 'workshop', 'hasclosingdate' => '1', 'closingdatefield' => 'assessmentend',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '6', 'eventname' => '\\mod_data\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'data', 'hasclosingdate' => '1', 'closingdatefield' => 'timeviewto',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '7', 'eventname' => '\\mod_data\\event\\record_created', 'actiontype' => 'contrib',
        'moduletable' => 'data', 'hasclosingdate' => '1', 'closingdatefield' => 'timeviewfrom',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '8', 'eventname' => '\\mod_folder\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'folder', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '9', 'eventname' => '\\mod_feedback\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'feedback', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '10', 'eventname' => '\\mod_feedback\\event\\response_submitted', 'actiontype' => 'contrib',
        'moduletable' => 'feedback', 'hasclosingdate' => '1', 'closingdatefield' => 'timeclose',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '11', 'eventname' => '\\mod_resource\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'resource', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '12', 'eventname' => '\\mod_forum\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'forum', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '13', 'eventname' => '\\mod_forum\\event\\post_created', 'actiontype' => 'contrib',
        'moduletable' => 'forum', 'hasclosingdate' => '1', 'closingdatefield' => 'cutoffdate',
        'haslatesubmit' => '1', 'latedatefield' => 'duedate'],
        ['id' => '14', 'eventname' => '\\mod_forum\\event\\discussion_created', 'actiontype' => 'contrib',
        'moduletable' => 'forum', 'hasclosingdate' => '1', 'closingdatefield' => 'cutoffdate',
        'haslatesubmit' => '1', 'latedatefield' => 'duedate'],
        ['id' => '15', 'eventname' => '\\mod_glossary\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'glossary', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '16', 'eventname' => '\\mod_glossary\\event\\entry_created', 'actiontype' => 'contrib',
        'moduletable' => 'glossary', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '17', 'eventname' => '\\mod_h5pactivity\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'h5p', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '18', 'eventname' => '\\mod_h5pactivity\\event\\statement_received', 'actiontype' => 'contrib',
        'moduletable' => 'h5p', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '19', 'eventname' => '\\mod_lesson\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'lesson', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '20', 'eventname' => '\\mod_lesson\\event\\lesson_ended', 'actiontype' => 'contrib',
        'moduletable' => 'lesson', 'hasclosingdate' => '1', 'closingdatefield' => 'deadline',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '21', 'eventname' => '\\mod_book\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'book', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '22', 'eventname' => '\\mod_lti\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'lti', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '23', 'eventname' => '\\mod_page\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'page', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '24', 'eventname' => '\\mod_imscp\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'imscp', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '25', 'eventname' => '\\mod_scorm\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'scorm', 'hasclosingdate' => '1', 'closingdatefield' => 'timeclose',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '26', 'eventname' => '\\mod_choice\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'choice', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '27', 'eventname' => '\\mod_choice\\event\\answer_submitted', 'actiontype' => 'contrib',
        'moduletable' => 'choice', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '28', 'eventname' => '\\mod_quiz\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'quiz', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '29', 'eventname' => '\\mod_quiz\\event\\attempt_submitted', 'actiontype' => 'contrib',
        'moduletable' => 'quiz', 'hasclosingdate' => '1', 'closingdatefield' => 'timeclose',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '30', 'eventname' => '\\mod_url\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'url', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '31', 'eventname' => '\\mod_wiki\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'wiki', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '32', 'eventname' => '\\mod_wiki\\event\\page_updated', 'actiontype' => 'contrib',
        'moduletable' => 'wiki', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '33', 'eventname' => '\\mod_organizer\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'organizer', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '34', 'eventname' => '\\mod_organizer\\event\\appointment_added', 'actiontype' => 'contrib',
        'moduletable' => 'organizer', 'hasclosingdate' => '1', 'closingdatefield' => 'duedate',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '35', 'eventname' => '\\mod_studentquiz\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'studentquiz', 'hasclosingdate' => '1', 'closingdatefield' => 'closeansweringfrom',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '36', 'eventname' => '\\mod_choicegroup\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'choicegroup', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '37', 'eventname' => '\\mod_choicegroup\\event\\choice_updated', 'actiontype' => 'contrib',
        'moduletable' => 'choicegroup', 'hasclosingdate' => '1', 'closingdatefield' => 'timeclose',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '38', 'eventname' => '\\mod_cobra\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'cobra', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '39', 'eventname' => '\\mod_dynamo\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'dynamo', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '40', 'eventname' => '\\mod_teamup\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'teamup', 'hasclosingdate' => '1', 'closingdatefield' => 'closed',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '41', 'eventname' => '\\mod_hvp\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'hvp', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '42', 'eventname' => '\\mod_hvp\\event\\attempt_submitted', 'actiontype' => 'contrib',
        'moduletable' => 'hvp', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '43', 'eventname' => '\\mod_game\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'game', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '44', 'eventname' => '\\mod_game\\event\\game_played', 'actiontype' => 'contrib',
        'moduletable' => 'game', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '45', 'eventname' => '\\mod_lightboxgallery\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'lightboxgallery', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '46', 'eventname' => '\\mod_geogebra\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'geogebra', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '47', 'eventname' => '\\mod_ezcast\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'ezcast', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '48', 'eventname' => '\\mod_kalvidres\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'kalvidres', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '49', 'eventname' => '\\mod_checklist\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'checklist', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '50', 'eventname' => '\\mod_checklist\\event\\checklist_completed', 'actiontype' => 'contrib',
        'moduletable' => 'checklist', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '51', 'eventname' => '\\mod_hotpot\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'hotpot', 'hasclosingdate' => '1', 'closingdatefield' => 'timeclose',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '52', 'eventname' => '\\mod_hotpot\\event\\attempt_submitted', 'actiontype' => 'contrib',
        'moduletable' => 'hotpot', 'hasclosingdate' => '1', 'closingdatefield' => 'timeclose',
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '53', 'eventname' => '\\mod_moodleoverflow\\event\\course_module_viewed', 'actiontype' => 'consult',
        'moduletable' => 'moodleoverflow', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '54', 'eventname' => '\\mod_moodleoverflow\\event\\discussion_created', 'actiontype' => 'contrib',
        'moduletable' => 'moodleoverflow', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
        ['id' => '55', 'eventname' => '\\mod_moodleoverflow\\event\\post_created', 'actiontype' => 'contrib',
        'moduletable' => 'moodleoverflow', 'hasclosingdate' => '0', 'closingdatefield' => null,
        'haslatesubmit' => '0', 'latedatefield' => null],
    ];

    // Insert records into the table.
    $DB->insert_records('logstore_socialflow_evts', $records);
}
