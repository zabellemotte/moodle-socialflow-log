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
        // Include the XMLDB library
        require_once($CFG->libdir . '/ddllib.php');
        mtrace("Hits table update begins ...");
        // Load the DDL manager and xmldb API.
        $dbman = $DB->get_manager();
        
        // create temporary table to store hits informations
        $temp_soci_hits = new \xmldb_table('logstore_socialflow_hits_temp');
        if($dbman->table_exists($temp_soci_hits)) $dbman->drop_table($temp_soci_hits);               
        $temp_soci_hits->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $temp_soci_hits->add_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_hits->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_hits->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_hits->add_field('nbhits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_hits->add_field('lasttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_hits->add_field('userids', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $temp_soci_hits->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($temp_soci_hits);
        if (!$temp_soci_hits) die("temporary hits table creation impossible");
        
        //create temporary table to store closing dates informations
        $temp_soci_closing = new \xmldb_table('logstore_socialflow_closing_temp');
        if($dbman->table_exists($temp_soci_closing)) $dbman->drop_table($temp_soci_closing);               
        $temp_soci_closing->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $temp_soci_closing->add_field('hitid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_closing->add_field('closingdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_closing->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($temp_soci_closing);
        if (!$temp_soci_closing) die("temporary closing table creation impossible");
        
        // create second temporay table to store closing date informations 
        // while avoiding an imbricated request with SELECT and INSERT on the same table
        $temp_soci_closing2 = new \xmldb_table('logstore_socialflow_closing_temp2');
        if($dbman->table_exists($temp_soci_closing2)) $dbman->drop_table($temp_soci_closing2);               
        $temp_soci_closing2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $temp_soci_closing2->add_field('hitid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_closing2->add_field('closingdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temp_soci_closing2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($temp_soci_closing2);
        if (!$temp_soci_closing2) die("temporary closing table 2 creation impossible");
        
        
        $loglifetime = (int) get_config('logstore_socialflow', 'loglifetime');
 

        if (empty($loglifetime) || $loglifetime < 0) {
            $loglifetime=14;
        }

        $loglifetime = time() - ($loglifetime * 3600 * 24); // Value in days.
        
        // hit request depend on the SGBD, thanks to Chatgpt for conversion ;-)
        // storing userids in one table field is the fastest way to store and access this information
        // as far as no select action is made on this field this does not raise performance problem
        $dbtype=$CFG->dbtype;
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
        $result1=$DB->execute($sql1);
        if (!$result1)  die("no data to treat");

        // Closing days computations are stored in an dedicated table 
        // because closing date field depends on the module.
        // For each event with closing date, the appropriate request is build 
        // and closing dates are stored in the table.
        $sql3="SELECT id, moduletable, closingdatefield FROM {logstore_socialflow_evts} WHERE hasclosingdate>0";
        $result3=$DB->get_records_sql($sql3);
        if ($result3) {
            foreach ($result3 as $row){
                $eventid=$row->id;
                $moduletable=$row->moduletable;
                if ($dbman->table_exists($moduletable)) {
                    $closingdatefield=$row->closingdatefield;
                     // in core plugins, the default value for closing date field is zero but in additionnal plugins, default value is sometimes null
                     $sql4="INSERT INTO {logstore_socialflow_closing_temp} (hitid,closingdate)
                                 SELECT DISTINCT h.id AS hitid,mt.".$closingdatefield." AS closingdate FROM {logstore_socialflow_hits} h
                                   INNER JOIN {logstore_socialflow_evts} evts ON h.eventid =".$eventid. 
                                    " INNER JOIN {context} c ON h.contextid = c.id 
                                    INNER JOIN {course_modules} cm ON c.instanceid = cm.id 
                                    INNER JOIN {".$moduletable."} mt ON cm.instance=mt.id
                                    WHERE (mt.".$closingdatefield." IS NOT NULL) AND (mt.".$closingdatefield." > 0)";
                         $result4=$DB->execute($sql4);
                         if (!$result4)  die("error on temp closing table insert");
                    
                 }
            }       
        }
        // To make it possible to exclude actions while closingdate is passed, 
        // I need to have a closing date defined for each event in the hits table.
        // The requests below complete the closing table so that all hits without closing date 
        // has an infinite value for closing date so 9999999999.
        // This operations requires 2 temporary tables because it is impossible to play 
        // an imbricated request with SELECT and INSERT on the same table
       $sql5="INSERT INTO {logstore_socialflow_closing_temp2} (hitid, closingdate) 
                             SELECT h.id AS hitid, 9999999999 AS closingdate
                             FROM {logstore_socialflow_hits} h
                             WHERE h.id NOT IN (SELECT hitid FROM {logstore_socialflow_closing_temp})";
       $result5=$DB->execute($sql5);
       if (!$result5)  die("error on temp closing table insert");
       $sql6="INSERT INTO {logstore_socialflow_closing_temp} (hitid, closingdate) 
                             SELECT hitid, closingdate FROM {logstore_socialflow_closing_temp2}";
       $result6=$DB->execute($sql6);
       if (!$result6)  die("error on temp closing table insert");
       
       mtrace("Informations stored in temporary tables, data replacement begins ...");
       // closing table replacement and temporary tables dropping
       // no generic truncate function in moodle data api, so it is faster to drop table and recreate it, rather than deleteing all records
       $soci_closing = new \xmldb_table('logstore_socialflow_closing');
       $dbman->rename_table($soci_closing,'logstore_socialflow_closing_old');
       $dbman->rename_table($temp_soci_closing,'logstore_socialflow_closing');
       $soci_closing_old = new \xmldb_table('logstore_socialflow_closing_old');
       $dbman->drop_table($soci_closing_old);
       $soci_closing_temp2 = new \xmldb_table('logstore_socialflow_closing_temp2');
       $dbman->drop_table($soci_closing_temp2);
        
       // hits table replacement and temporary table dropping
       // no generic truncate function in moodle data api, so it is faster to drop table and recreate it, rather than deleteing all records
       $soci_hits = new \xmldb_table('logstore_socialflow_hits');
       $dbman->rename_table($soci_hits,'logstore_socialflow_hits_old');
       $dbman->rename_table($temp_soci_hits,'logstore_socialflow_hits');
       $soci_hits_old = new \xmldb_table('logstore_socialflow_hits_old');
       $dbman->drop_table($soci_hits_old);
       mtrace("Hits and closing dates informations updated");
    }
}
