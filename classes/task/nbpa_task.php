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

namespace logstore_socialflow\task;

// This file was basically copied from "logstore_standard/task/cleanup_task", so functionality is very similar to the native one.

/**
 * Scheduled number of participants task.
 *
 * @package     logstore_socialflow
 * Fork of logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Modified by Zabelle Motte (UCLouvain) */
class nbpa_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasknbpa', 'logstore_socialflow');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;
        // Include the XMLDB library.
        require_once($CFG->libdir . '/ddllib.php');
        mtrace("Number of participants computations begin ...");
        // Load the DDL manager and xmldb API.
        $dbman = $DB->get_manager();

        // Temporary table to store number of active participants informations.
        $tempsocinbpa = new \xmldb_table('logstore_socialflow_nbpa_temp');
        if ($dbman->table_exists($tempsocinbpa)) {
            $dbman->drop_table($tempsocinbpa);
        }
        $tempsocinbpa->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $tempsocinbpa->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsocinbpa->add_field('nbpa', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsocinbpa->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($tempsocinbpa);
        if (!$tempsocinbpa) {
            die("temporary nbpa table creation impossible");
        }

        // Get list of all courses in log table.
        $sql2 = "SELECT DISTINCT(courseid) FROM mdl_logstore_socialflow_log";
        $result2 = $DB->get_records_sql($sql2);
        if (!$result2) {
            die("no data to treat");
        }
        $now = time();
        // Get number of active participants in the course.
        // Numbrer of active participants needs a heavy computation.
        // Verifications : active user, active enrolment method, active enrolment, student role.
        // It is sufficient to compute it 1 time a day, so it is stored in a dedicated table.
        // That is updated via this nbpa crontask.
        foreach ($result2 as $row) {
            $courseid = $row->courseid;
            $sql3 = "
                SELECT COUNT(DISTINCT u.id) AS nbpa
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {context} ct
                      ON ct.id = ra.contextid
                     AND ct.contextlevel = :contextlevel
                  JOIN {course} c
                      ON c.id = ct.instanceid
                     AND e.courseid = c.id
                  JOIN {role} r
                      ON r.id = ra.roleid
                     AND r.shortname = :roleshortname
                 WHERE e.status = :enrolstatus
                   AND ue.status = :uestatus
                   AND u.suspended = :suspended
                   AND u.deleted = :deleted
                   AND (ue.timeend = 0 OR ue.timeend > :now)
                   AND c.id = :courseid
            ";
            $params3 = [
                'contextlevel'  => CONTEXT_COURSE,
                'roleshortname' => 'student',
                'enrolstatus'   => 0,
                'uestatus'      => 0,
                'suspended'     => 0,
                'deleted'       => 0,
                'now'           => $now,
                'courseid'      => $courseid,
            ];
            $result3 = $DB->get_record_sql($sql3, $params3);
            if (!$result3) {
                die("nbpa computation impossible");
            }
            $nbpa = $result3->nbpa;
            $data = new \stdClass();
            $data->courseid = $courseid;
            $data->nbpa = $nbpa;
            $result4 = $DB->insert_record('logstore_socialflow_nbpa_temp', $data);
            if (!$result4) {
                die("insert new nbpa data impossible");
            }
        }
        mtrace("Computations completed, data replacement begins ...");

        // Table nbpa replacement and temporary table dropping.
        // No generic truncate function in moodle data api.
        // So it is faster to drop table and recreate it, rather than deleteing all records.
        $socinbpa = new \xmldb_table('logstore_socialflow_nbpa');
        $dbman->rename_table($socinbpa, 'logstore_socialflow_nbpa_old');
        $dbman->rename_table($tempsocinbpa, 'logstore_socialflow_nbpa');
        $socinbpaold = new \xmldb_table('logstore_socialflow_nbpa_old');
        $dbman->drop_table($socinbpaold);
        mtrace("Nbpa informations updated");
    }
}
