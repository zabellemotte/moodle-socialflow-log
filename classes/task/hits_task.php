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
 * Class hits_task
 *
 * This class handles the scheduled task to store hits.
 *
 * It is triggered by Moodle's task scheduler and computes hit for each task in the log_socialflow table.
 *
 * @package     logstore_socialflow
 * Fork of logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Modified by Zabelle Motte (UCLouvain)
 */
class hits_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskhits', 'logstore_socialflow');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;
        // Include the XMLDB library.
        require_once($CFG->libdir . '/ddllib.php');
        mtrace("Hits table update begins ...");
        // Load the DDL manager and xmldb API.
        $dbman = $DB->get_manager();

        // Create temporary table to store hits informations.
        $tempsocihits = new \xmldb_table('logstore_socialflow_hits_temp');
        if ($dbman->table_exists($tempsocihits)) {
            $dbman->drop_table($tempsocihits);
        }
        $tempsocihits->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $tempsocihits->add_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsocihits->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsocihits->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsocihits->add_field('nbhits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsocihits->add_field('lasttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsocihits->add_field('userids', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $tempsocihits->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($tempsocihits);
        if (!$tempsocihits) {
            die("temporary hits table creation impossible");
        }

        // Create temporary table to store closing dates informations.
        $tempsociclosing = new \xmldb_table('logstore_socialflow_closing_temp');
        if ($dbman->table_exists($tempsociclosing)) {
            $dbman->drop_table($tempsociclosing);
        }
        $tempsociclosing->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $tempsociclosing->add_field('hitid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsociclosing->add_field('closingdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsociclosing->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($tempsociclosing);
        if (!$tempsociclosing) {
            die("temporary closing table creation impossible");
        }

        // Create second temporay table to store closing date informations.
        // This is necessary because it is impossible to exectute an imbricated query with SELECT and INSERT on the same table.
        $tempsociclosing2 = new \xmldb_table('logstore_socialflow_closing_temp2');
        if ($dbman->table_exists($tempsociclosing2)) {
            $dbman->drop_table($tempsociclosing2);
        }
        $tempsociclosing2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $tempsociclosing2->add_field('hitid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsociclosing2->add_field('closingdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tempsociclosing2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($tempsociclosing2);
        if (!$tempsociclosing2) {
            die("temporary closing table 2 creation impossible");
        }

        $loglifetime = (int) get_config('logstore_socialflow', 'loglifetime');

        if (empty($loglifetime) || $loglifetime < 0) {
            $loglifetime = 14;
        }

        $loglifetime = time() - ($loglifetime * 3600 * 24); // Value in days.

        // Hit query depend on the SGBD, thanks to Chatgpt for the conversion !
        // Storing userids in one table field is the fastest way to store and access this information.
        // As far as no select action is made on this field this does not raise performance problem.
        $dbtype = $CFG->dbtype;
        switch ($dbtype) {
            case 'mariadb':
                $sql1 = "INSERT INTO {logstore_socialflow_hits_temp} (contextid, eventid, courseid, nbhits, lasttime, userids)
                              SELECT log.contextid, log.eventid, log.courseid,
                                 COUNT(DISTINCT log.userid) AS nbhits,
                                 MAX(log.timecreated) AS lasttime,
                                 GROUP_CONCAT(DISTINCT log.userid ORDER BY log.userid) AS userids
                              FROM {logstore_socialflow_log} log
                              GROUP BY log.contextid, log.eventid, log.courseid";
                break;
            case 'mysqli':
                $sql1 = "INSERT INTO {logstore_socialflow_hits_temp} (contextid, eventid, courseid, nbhits, lasttime, userids)
                              SELECT log.contextid, log.eventid, log.courseid,
                                 COUNT(DISTINCT log.userid) AS nbhits,
                                 MAX(log.timecreated) AS lasttime,
                                 GROUP_CONCAT(DISTINCT log.userid ORDER BY log.userid) AS userids
                              FROM {logstore_socialflow_log} log
                              GROUP BY log.contextid, log.eventid, log.courseid";
                break;
            case 'pgsql':
                $sql1 = "INSERT INTO {logstore_socialflow_hits_temp} (contextid, eventid, courseid, nbhits, lasttime, userids)
                          SELECT log.contextid, log.eventid, log.courseid,
                                COUNT(DISTINCT log.userid) AS nbhits,
                                MAX(log.timecreated) AS lasttime,
                                STRING_AGG(DISTINCT log.userid::TEXT, ',' ORDER BY log.userid) AS userids
                          FROM {logstore_socialflow_log} log
                          GROUP BY log.contextid, log.eventid, log.courseid";
                break;
            case 'sqlsrv':
                $sql1 = "INSERT INTO {logstore_socialflow_hits_temp} (contextid, eventid, courseid, nbhits, lasttime, userids)
                         SELECT log.contextid, log.eventid, log.courseid,
                                COUNT(DISTINCT log.userid) AS nbhits,
                                MAX(log.timecreated) AS lasttime,
                                STRING_AGG(DISTINCT CONVERT(VARCHAR(10), log.userid), ',') AS userids
                         FROM {logstore_socialflow_log} log
                         GROUP BY log.contextid, log.eventid, log.courseid";
                break;
            case 'oci':
                $sql1 = "INSERT INTO {logstore_socialflow_hits_temp} (contextid, eventid, courseid, nbhits, lasttime, userids)
                              SELECT log.contextid, log.eventid, log.courseid,
                                  COUNT(DISTINCT log.userid) AS nbhits,
                                  MAX(log.timecreated) AS lasttime,
                                  LISTAGG(log.userid, ',') WITHIN GROUP (ORDER BY log.userid) AS userids
                               FROM (
                                   SELECT DISTINCT contextid, eventid, courseid, userid, timecreated
                                   FROM {logstore_socialflow_log} ) log
                               GROUP BY log.contextid, log.eventid, log.courseid";
                break;
            default:
                throw new moodle_exception('unsupporteddbtype', 'error', '', $dbtype);
        }
        $result1 = $DB->execute($sql1);

        // Hits table replacement and temporary table dropping.
        // No generic truncate function in moodle data api.
        // So it is faster to drop table and recreate it, rather than deleteing all records.
        $socihits = new \xmldb_table('logstore_socialflow_hits');
        $dbman->rename_table($socihits, 'logstore_socialflow_hits_old');
        $dbman->rename_table($tempsocihits, 'logstore_socialflow_hits');
        $socihitsold = new \xmldb_table('logstore_socialflow_hits_old');
        $dbman->drop_table($socihitsold);
        mtrace("Hits informations updated");
        mtrace("Closing table update begins ...");
        // Closing days computations are stored in an dedicated table because closing date field depends on the module.
        // For each event with closing date, the appropriate query is build and closing dates are stored in the table.
        $sql3 = "SELECT id, moduletable, closingdatefield FROM {logstore_socialflow_evts} WHERE hasclosingdate>0";
        $result3 = $DB->get_records_sql($sql3);
        if ($result3) {
            foreach ($result3 as $row) {
                $eventid = $row->id;
                $moduletable = $row->moduletable;
                if ($dbman->table_exists($moduletable)) {
                    $closingdatefield = $row->closingdatefield;
                    // In core plugins, the default value for closing date field is zero.
                    // But in additionnal plugins, default value is sometimes null.
                    $sql4 = "INSERT INTO {logstore_socialflow_closing_temp} (hitid,closingdate)
                                 SELECT DISTINCT h.id AS hitid,mt." . $closingdatefield . "
                                 AS closingdate FROM {logstore_socialflow_hits} h
                                 INNER JOIN {logstore_socialflow_evts} evts ON h.eventid =" . $eventid . "
                                 INNER JOIN {context} c ON h.contextid = c.id
                                 INNER JOIN {course_modules} cm ON c.instanceid = cm.id
                                 INNER JOIN {" . $moduletable . "} mt ON cm.instance=mt.id
                                 WHERE (mt." . $closingdatefield . " IS NOT NULL) AND (mt." . $closingdatefield . " > 0)";
                    $result4 = $DB->execute($sql4);
                    if (!$result4) {
                        die("error on temp closing table insert");
                    }
                }
            }
        }
        // To make it possible to exclude actions while closingdate is passed ...
        // I need to have a closing date defined for each event in the hits table.
        // The requests below complete the closing table ...
        // So that all hits without closing date has an infinite value for closing date so 9999999999.
        // This operations requires 2 temporary tables ...
        // Because it is impossible to play an imbricated query with SELECT and INSERT on the same table.
        $sql5 = "INSERT INTO {logstore_socialflow_closing_temp2} (hitid, closingdate)
                             SELECT h.id AS hitid, 9999999999 AS closingdate
                             FROM {logstore_socialflow_hits} h
                             WHERE h.id NOT IN (SELECT hitid FROM {logstore_socialflow_closing_temp})";
        $result5 = $DB->execute($sql5);
        if (!$result5) {
            die("error on temp closing table insert");
        }
        $sql6 = "INSERT INTO {logstore_socialflow_closing_temp} (hitid, closingdate)
                             SELECT hitid, closingdate FROM {logstore_socialflow_closing_temp2}";
        $result6 = $DB->execute($sql6);
        if (!$result6) {
            die("error on temp closing table insert");
        }
        // Closing table replacement and temporary tables dropping.
        // No generic truncate function in moodle data api?
        // So it is faster to drop table and recreate it, rather than deleteing all records.
        $sociclosing = new \xmldb_table('logstore_socialflow_closing');
        $dbman->rename_table($sociclosing, 'logstore_socialflow_closing_old');
        $dbman->rename_table($tempsociclosing, 'logstore_socialflow_closing');
        $sociclosingold = new \xmldb_table('logstore_socialflow_closing_old');
        $dbman->drop_table($sociclosingold);
        $sociclosingtemp2 = new \xmldb_table('logstore_socialflow_closing_temp2');
        $dbman->drop_table($sociclosingtemp2);
        mtrace("Closing dates informations updated");
    }
}
