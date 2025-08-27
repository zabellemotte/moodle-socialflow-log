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
 * CLI script to import data into the SocialFlow logstore.
 *
 * Usage:
 *     $ php import.php
 *
 * @package     logstore_socialflow
 * Fork of logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Modified by Zabelle Motte (UCLouvain)
 */

 defined('MOODLE_INTERNAL') || die();

$offsetid = 0;
$batch = 10000;
$limit = 0;

define('CLI_SCRIPT', true);

if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

require(dirname(__FILE__) . '/../../../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

$usage = "Imports data from table 'logstore_standard_log' into table 'logstore_socialflow_log'.

Options:
    -h --help               Print this help.
    --clean                 Clean the 'logstore_socialflow_log' table before running.
                            Be aware, that this options deletes all data from the
                            table 'logstore_socialflow_log'. This option should only
                            be used before activating the logstore in the settings.
    --startid=<value>       First ID to be imported, leave empty to import all events.
    --pastweeks=<value>     Instead of using startid you can use past-weeks to set how
                            much weeks from the pasts should be imported. The importer
                            will ignore all events that are older. Example: Set this
                            value to 3 to import only the logs from the last 3 weeks.
    --batch=<value>         How many logs to be handled in one batch. Defaults to 10000
    --limit=<value>         For testing/development purposes only. This set the max. ID
                            of the row to limit the number of rows to be imported.

Example:
php cli/import.php
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'clean' => false,
    'startid' => 0,
    'pastweeks' => 0,
    'batch' => 0,
    'limit' => 0,
], [
    'h' => 'help',
]);

if ($options['help']) {
    cli_writeln($usage);
    exit(0);
}

if ($options['startid']) {
    $offsetid = (int) $options['startid'] - 1; // Value -1 as this is treated as offset.
}

if ($options['batch']) {
    $batch = (int) $options['batch'];
}

if ($options['limit']) {
    $limit = (int) $options['limit'];
}
if ($options['pastweeks']) {
    if ($offsetid !== 0) {
        cli_writeln("Please specify only startid or pastweeks but not both.");
        die();
    }
    $pastweeks = (int) $options['pastweeks'];
    $date = new \DateTime();
    $date->modify('Monday this week');
    $date->modify("-{$pastweeks} week");
    $timestamp = $date->getTimestamp();
    cli_writeln("Searching for first event with timecreated >= {$timestamp} (Monday {$pastweeks} weeks ago).");
    $row = $DB->get_records_sql("SELECT id FROM {logstore_standard_log} WHERE timecreated >= ? ORDER BY id LIMIT 1", [$timestamp]);
    $foundid = current($row)->id;
    $offsetid = $foundid - 1;
    cli_writeln("  Found row ID: {$foundid}");
}


/**
 * Truncate the log table.
 *
 * @return void
 */
function truncate_logs() {
    global $DB;
    $DB->execute("TRUNCATE {logstore_socialflow_log}");
}

/**
 * Check if new data exist in standard log table.
 *
 * @return bool
 */
function check_for_rows(int $offsetid) {
    global $DB;
    $row = $DB->get_records_sql("SELECT id FROM {logstore_standard_log} WHERE id > ? LIMIT 1", [$offsetid]);
    return count($row) !== 0;
}


/**
 * Copy data from standard log table to social flow log table
 *
 * @return void
 */
function copy_rows(int $offsetid, int $limitid) {
    global $DB;

    $sql = <<<SQL
        INSERT INTO {logstore_socialflow_log}
            (eventid, courseid, contextid, userid, timecreated)
        SELECT
            e.id AS eventid,
            l.courseid,
            l.contextid,
            l.userid,
            l.timecreated
        FROM {logstore_standard_log} l
        JOIN {logstore_socialflow_evts} e ON
            l.eventname = e.eventname
        WHERE l.id > ? AND l.id <= ?
        ORDER BY l.id
SQL;
    $rows = $DB->execute($sql, [$offsetid, $limitid]);
}

/**
 * Check if new data exist in standard log table.
 *
 * @return int
 */
function log_rows() {
    global $DB;
    return $DB->count_records('logstore_socialflow_log');
}

$rowcount = log_rows();
cli_writeln("Number of rows inside 'logstore_socialflow_log' before import: {$rowcount}");

if ($options['clean']) {
    cli_writeln("  Truncating table 'logstore_socialflow_log'.");
    truncate_logs();

    $rowcount = log_rows();
    cli_writeln("Number of rows inside 'logstore_socialflow_log' after TRUNCATE: {$rowcount}");
}

cli_writeln("Starting import.");

while (check_for_rows($offsetid) && ($limit === 0 || $offsetid < $limit)) {
    $limitid = $offsetid + $batch;
    cli_writeln("  Importing rows from > {$offsetid} to <= {$limitid}");

    copy_rows($offsetid, $limitid);

    $offsetid = $limitid;
}

cli_writeln("Import finished.");

$rowcount = log_rows();
cli_writeln("Number of rows inside `logstore_socialflow_log` after import:  {$rowcount}");

