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
 * logstore_socialflow settings
 * @package     logstore_socialflow
 * Fork of logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Modified by Zabelle Motte (UCLouvain)
 */

$string['pluginname'] = 'Social Flow Log';
$string['privacy:metadata'] = 'This plugin does not store any personal data. All data is logged and stored anonymously.';

// Settings
$string['setting_log_scope'] = 'log_scope';
$string['setting_log_scope_descr'] = 'Defines the scope of the logging process. By default, all courses are logged.';
$string['setting_log_scope_all'] = 'Log events in all courses';
$string['setting_log_scope_include'] = 'Log events only in courses specified via course_ids below';
$string['setting_log_scope_exclude'] = 'Log events EXCLUDING the courses specified via course_ids below';
$string['setting_course_ids'] = 'course_ids';
$string['setting_course_ids_descr'] = 'To be used with the log_scope option "include" or "exclude" to only track specific courses. Example: <code>10,153,102</code>.';
$string['setting_tracking_roles'] = 'tracking_roles';
$string['setting_tracking_roles_descr'] = 'Define which roles should be tracked (whitelist). Specify the role by using the "shortname" (can be found via <em>Site Administration</em> -> <em>Users</em> tab -> <em>Permissions</em> category -> <em>Define roles</em>). By default, only student role is tracked. Example: <code>student,guest</code>';
$string['setting_nontracking_roles'] = 'nontracking_roles';
$string['setting_nontracking_roles_descr'] = 'Define which roles should <strong>not</strong> be tracked (blacklist). This is useful if you don\'t want to track specific roles (like managers or teachers). Specify the role by using the "shortname" (can be found via <em>Site Administration</em> -> <em>Users</em> tab -> <em>Permissions</em> category -> <em>Define roles</em>). By default, all roles are tracked. Example: <code>teacher,editingteacher,manager</code>. This settings has priority over <code>tracking_roles</code>.';
$string['buffersize'] = 'buffersize';

$string['loglifetime'] = 'Keep logs for';
$string['configloglifetime_descr'] = 'This specifies the length of time you want to keep logs about course activity. Logs are suppressed once one action has no more hits during loglifetime.';

$string['privacy:metadata:log'] = 'A collection of past events';
$string['privacy:metadata:log:eventid'] = 'The event id';
$string['privacy:metadata:log:timecreated'] = 'The time when the event occurred';
$string['privacy:metadata:log:userid'] = 'The ID of the user who triggered this event';

// Cleanup Task
$string['taskcleanup'] = 'Log table cleanup';