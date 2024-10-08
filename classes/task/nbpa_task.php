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
 * Scheduled cleanup task.
 *
 * @package     logstore_socialflow
 * Fork of logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Modified by Zabelle Motte (UCLouvain) */

namespace logstore_socialflow\task;

// This file was basically copied from "logstore_standard/task/cleanup_task", so functionality is very similar to the native one

defined('MOODLE_INTERNAL') || die();

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
        // Include the XMLDB library
        require_once($CFG->libdir . '/ddllib.php');
        mtrace("Number of participants computations begin ...");
        // Load the DDL manager and xmldb API.
        $dbman = $DB->get_manager();
        
        // temporary table to store number of active participants informations
        $temp_soci_nbpa = new \xmldb_table('logstore_socialflow_nbpa_temp');
        if($dbman->table_exists($temp_soci_nbpa)) $dbman->drop_table($temp_soci_nbpa);               
        $temp_soci_nbpa->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $temp_soci_nbpa->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_nbpa->add_field('nbpa', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_nbpa->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($temp_soci_nbpa);
        if (!$temp_soci_nbpa) die("temporary nbpa table creation impossible");
        
        // get list of all courses in log table
        $sql2 ="SELECT DISTINCT(courseid) FROM mdl_logstore_socialflow_log";
        $result2 = $DB->get_records_sql($sql2);
        if (!$result2)  die("no data to treat");
        $now=time();
        // get number of active participants in the course
        // numbrer of active participants needs a heavy computation 
        // (verifications : active user, active enrolment method, active enrolment, student role)
        // it is sufficient to compute it 1 time a day, so it is stored in a dedicated table 
        // that is updated via the nbpa crontask
        foreach ($result2 as $row) {
            $courseid=$row->courseid;
            $sql3 ="SELECT COUNT(DISTINCT(u.id)) AS nbpa
                         FROM {user} u
                         INNER JOIN {user_enrolments} ue ON ue.userid = u.id
                         INNER JOIN {enrol} e ON e.id = ue.enrolid
                         INNER JOIN {role_assignments} ra ON ra.userid = u.id
                         INNER JOIN {context} ct ON ct.id = ra.contextid AND ct.contextlevel = 50
                         INNER JOIN {course} c ON c.id = ct.instanceid AND e.courseid = c.id
                         INNER JOIN {role} r ON r.id = ra.roleid  AND r.shortname = 'student'
                         WHERE e.status = 0 AND u.suspended = 0 AND u.deleted = 0 AND (ue.timeend = 0 OR ue.timeend > $now) 
                         AND ue.status = 0 AND c.id = $courseid";
            $result3 = $DB->get_record_sql($sql3);
            if(!$result3) die("nbpa computation impossible");
            $nbpa=$result3->nbpa;
            $data= new \stdClass();
            $data->courseid=$courseid;
            $data->nbpa=$nbpa;
            $result4 = $DB->insert_record('logstore_socialflow_nbpa_temp',$data);
            if (!$result4) die("insert new nbpa data impossible");   
        }
    mtrace("Computations completed, data replacement begins ...");

    // nbpa table replacement and temporary table dropping
    // no generic truncate function in moodle data api, 
    // so it is faster to drop table and recreate it, rather than deleteing all records
    $soci_nbpa = new \xmldb_table('logstore_socialflow_nbpa');
    $dbman->rename_table($soci_nbpa,'logstore_socialflow_nbpa_old');
    $dbman->rename_table($temp_soci_nbpa,'logstore_socialflow_nbpa');
    $soci_nbpa_old = new \xmldb_table('logstore_socialflow_nbpa_old');
    $dbman->drop_table($soci_nbpa_old);
    mtrace("Nbpa informations updated");
    }

}
