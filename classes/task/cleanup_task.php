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

class cleanup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcleanup', 'logstore_socialflow');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;
        mtrace("Log cleanup begins ...");
        $loglifetime = (int) get_config('logstore_socialflow', 'loglifetime');
 

        if (empty($loglifetime) || $loglifetime < 0) {
            return;
        }

        $loglifetime = time() - ($loglifetime * 3600 * 24); // Value in days.
    
        $start = time();
        
        // Events are deleted when no event arised on the contextid during the longlifetime.
        // As far as a there are events linked to a contextid during the longlifetime, data are preserved 
        // to be able to indicate user if he had an action linked to this contextid (even if this action is outside the longlifetime).
        $old_data = $DB->get_records_sql(
    "SELECT id FROM {logstore_socialflow_log} WHERE timecreated <= $loglifetime 
            AND contextid NOT IN (SELECT DISTINCT contextid 
                                FROM {logstore_socialflow_log}
                                WHERE timecreated > $loglifetime)"  );
    if ($old_data) {
        $ids = array();
        foreach ($old_data as $row) {
            $ids[] = $row->id; 
        }
        // data suppression is chunked in several delete actions of 500 records to avoid database trashing
        while (count($ids)>0){
            $lids=count($ids);
            $cids=array_slice($ids, 0, 50);
            $ids= array_slice($ids, 50, $lids - 50);
            $clauseIN = implode(', ', $cids);
            $select = "id IN ($clauseIN)";
            $DB->delete_records_select("logstore_socialflow_log",$select);
         }
        mtrace("Old log records from socialflow log store deleted.");
    }
    else{
            mtrace("No old data to delete in socialflow logs.");
    }
    }
}
