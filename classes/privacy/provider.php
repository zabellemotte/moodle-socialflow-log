<?php
// This file is part of Moodle.
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
 * Privacy provider for logstore_socialflow.
 *
 * @package    logstore_socialflow
 * @category   privacy
 * @copyright  Zabelle Motte 2025
 * @author     Zabelle Motte (isabelle.motte@uclouvain.be)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_socialflow\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Privacy provider for the logstore_socialflow plugin.
 *
 * @package    logstore_socialflow
 * @copyright  Zabelle Motte 2025
 * @author     Zabelle Motte (isabelle.motte@uclouvain.be)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    plugin_provider {
    /**
     * Returns metadata about this plugin's stored personal data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'logstore_socialflow_log',
            [
                'userid'      => 'privacy:metadata:userid',
                'contextid'   => 'privacy:metadata:contextid',
                'courseid'    => 'privacy:metadata:courseid',
                'timecreated' => 'privacy:metadata:timecreated',
                'eventid'     => 'privacy:metadata:eventid',
            ],
            'privacy:metadata:logstore'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "
            SELECT DISTINCT contextid
              FROM {logstore_socialflow_log}
             WHERE userid = :userid
        ";

        $contextlist->add_from_sql($sql, ['userid' => $userid]);

        return $contextlist;
    }

    /**
     * Export user data for the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->is_empty()) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $contextids = $contextlist->get_contextids();

        list($insql, $params) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
        $sql = "
            SELECT l.contextid,
                   l.courseid,
                   l.timecreated,
                   e.eventname
              FROM {logstore_socialflow_log} l
              JOIN {logstore_socialflow_evts} e
                   ON e.id = l.eventid
             WHERE l.userid = :userid
               AND l.contextid $insql
             ORDER BY l.timecreated
        ";
        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $context = context::instance_by_id($record->contextid);
            $data = [
               'event'       => $record->eventname,
               'courseid'    => $record->courseid,
               'timecreated' => transform::datetime($record->timecreated),
            ];
            writer::with_context($context)->export_data([], (object)$data);
        }
    }

    /**
     * Delete all user data for the specified user, in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->is_empty()) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $contextids = $contextlist->get_contextids();

        list($insql, $params) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;

        $DB->execute(
            "
            DELETE FROM {logstore_socialflow_log}
             WHERE userid = :userid
               AND contextid $insql
            ",
            $params
        );
    }

    /**
     * Delete all user data for all users in the specified context.
     *
     * @param context $context
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        $DB->delete_records(
            'logstore_socialflow_log',
            ['contextid' => $context->id]
        );
    }
}
