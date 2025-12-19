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

namespace logstore_socialflow\log;

use tool_log\log\manager as log_manager;
use tool_log\helper\store as helper_store;
use tool_log\helper\reader as helper_reader;
use tool_log\helper\buffered_writer as helper_writer; // We are overwriting write(), see below.
use core\event\base as event_base;
use stdClass;
use context_course;

/**
 * Indicates the API mode for Moodle.
 *
 * @var int
 */
const MOODLE_API = 10100;


/**
 * logstore_socialflow
 *
 * @package     logstore_socialflow
 * Fork of logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Modified by Zabelle Motte (UCLouvain)
 */
class store implements \tool_log\log\writer {
    use helper_store;
    use helper_reader;
    use helper_writer;


    /**
     * Creating the new log store
     *
     * @param log_manager $manager The log manager instance.
     */
    public function __construct(log_manager $manager) {
        $this->helper_setup($manager);
    }

    /**
     * Function to exclude anonymous and guest actions form log table
     *
     * @param event_base $event The event to check.
     * @return bool True if the event should be ignored, false otherwise.
     */
    protected function is_event_ignored(event_base $event) {
        if ((!CLI_SCRIPT || PHPUNIT_TEST)) {
            // Always log inside CLI scripts because we do not login there.
            if (!isloggedin()) {
                return true;
            }
        }

        return false;
    }
    /**
     * Function adapted form buffer_writer
     *
    * @param \core\event\base $event The event to write to the buffer.
     * @return void
     */
    public function write(\core\event\base $event) {
        // Copied mostly from "tool_log\helper\buffered_writer" with some modifications.
        global $PAGE;

        if ($this->is_event_ignored($event)) {
            return;
        }

        $entry = $event->get_data();
        // Add similar data as in buffered_writer to make it more compatible.
        $entry['realuserid'] = \core\session\manager::is_loggedinas() ? $GLOBALS['USER']->realuser : null;

        $this->buffer[] = $entry;
        $this->count++;

        if (!isset($this->buffersize)) {
            $this->buffersize = $this->get_config('buffersize', 50);
        }
        if ($this->count >= $this->buffersize) {
            $this->flush();
        }
    }

    /**
     * Function storing events that are predefined in evts table
     *
     * @param array $events An array of events to insert. Each event should contain keys
     *                      'eventname', 'timecreated', 'courseid', 'contextid', and 'userid'.
     * @return void
     */
    protected function insert_event_entries($events) {
        global $DB, $CFG;

        $courseids = [];
        $logscope = get_config('logstore_socialflow', 'log_scope'); // Value all, include, exclude.
        if ($logscope === false) {
            $logscope = 'all';
        }

        if ($logscope === 'include' || $logscope === 'exclude') {
            $courseids = get_config('logstore_socialflow', 'course_ids');
            if ($courseids === false || $courseids === '') {
                $courseids = [];
            } else {
                $courseids = array_map('trim', explode(',', $courseids));
            }
        }

        $trackingrolesstr = get_config('logstore_socialflow', 'tracking_roles');
        $trackingroles = [];
        if ($trackingrolesstr !== false && $trackingrolesstr !== '') {
            $trackingroles = array_map('trim', explode(',', $trackingrolesstr));
        }

        $nottrackingrolesstr = get_config('logstore_socialflow', 'nontracking_roles');
        $nottrackingroles = [];
        if ($nottrackingrolesstr !== false && $nottrackingrolesstr !== '') {
            $nottrackingroles = array_map('trim', explode(',', $nottrackingrolesstr));
        }

        $records = [];
        foreach ($events as $event) {
            if (
                ($logscope !== 'all') // First checking the fast option.
                && (($logscope === 'include' && !in_array($event['courseid'], $courseids))
                || ($logscope === 'exclude' && in_array($event['courseid'], $courseids)))
            ) {
                continue;
            }
            if (count($trackingroles) !== 0 || count($nottrackingroles) !== 0) {
                $coursecontext = context_course::instance($event['courseid'], IGNORE_MISSING);
                $trackevent = true;
                if ($coursecontext) { // Context might not be defined for global events like login, main page.
                    $userroles = get_user_roles($coursecontext, $event['userid']);
                    if (isguestuser()) {
                        // We "fake" a guest role here as only the shortname matters.
                        // That way, we don't need another database query.
                        $guestrole = new \stdClass();
                        $guestrole->shortname = 'guest';
                        $userroles[] = $guestrole;
                    }
                    if (count($trackingroles) !== 0) { // Whitelist mode, respecting blacklist.
                        $trackevent = false;
                        foreach ($userroles as $role) {
                            if (in_array($role->shortname, $nottrackingroles)) { // Blacklisted.
                                $trackevent = false;
                                break;
                            }
                            if (in_array($role->shortname, $trackingroles)) { // Whitelisted.
                                $trackevent = true;
                            }
                        }
                    } else { // Blacklist mode, no whitelist defined.
                        foreach ($userroles as $role) {
                            if (in_array($role->shortname, $nottrackingroles)) {
                                $trackevent = false;
                                break;
                            }
                        }
                    }
                } else if (count($trackingroles) !== 0) {
                    // Whitelist is active -> only track specific roles, therefore skip this one as no role is defined.
                    $trackevent = false;
                }
                if (!$trackevent) {
                    continue;
                }
            }

            $eventid = 0;
            $dbevent = $DB->get_record('logstore_socialflow_evts', ['eventname' => $event['eventname']], 'id');
            if ($dbevent) {
                $eventid = $dbevent->id;
            } else {
                continue;
            }
            $record = new stdClass();
            $record->eventid = $eventid;
            $record->timecreated = $event['timecreated'];
            $record->courseid = $event['courseid'];
            $record->contextid = $event['contextid'];
            $record->userid = $event['userid'];
            $records[] = $record;
        }

        if (count($records) !== 0) {
            $DB->insert_records('logstore_socialflow_log', $records);
        }
    }
}
