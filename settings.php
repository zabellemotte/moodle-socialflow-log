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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings->add(new admin_setting_configselect(
        'logstore_socialflow/log_scope',
        get_string('setting_log_scope', 'logstore_socialflow'),
        get_string('setting_log_scope_descr', 'logstore_socialflow'),
        'all', // Default value.
        [
            'all' => get_string('setting_log_scope_all', 'logstore_socialflow'),
            'include' => get_string('setting_log_scope_include', 'logstore_socialflow'),
            'exclude' => get_string('setting_log_scope_exclude', 'logstore_socialflow'),
        ]
    ));

    // This is only a textarea to make it more comforable entering the values.
    $settings->add(new admin_setting_configtextarea(
        'logstore_socialflow/course_ids',
        get_string('setting_course_ids', 'logstore_socialflow'),
        get_string('setting_course_ids_descr', 'logstore_socialflow'),
        '',
        PARAM_RAW,
        '60',
        '2'
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_socialflow/tracking_roles',
        get_string('setting_tracking_roles', 'logstore_socialflow'),
        get_string('setting_tracking_roles_descr', 'logstore_socialflow'),
        'student', // Default value.
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_socialflow/nontracking_roles',
        get_string('setting_nontracking_roles', 'logstore_socialflow'),
        get_string('setting_nontracking_roles_descr', 'logstore_socialflow'),
        '', // Default value.
        PARAM_RAW
    ));

    $options = [
        14  => get_string('numweeks', '', 2 ),
        7  => get_string('numweeks', '', 1 ),
    ];
    $settings->add(new admin_setting_configselect('logstore_socialflow/loglifetime',
        new lang_string('loglifetime', 'logstore_socialflow'),
        new lang_string('configloglifetime_descr', 'logstore_socialflow'), 0, $options));

    $settings->add(new admin_setting_configtext(
        'logstore_socialflow/buffersize',
        get_string('buffersize', 'logstore_socialflow'),
        '',
        '50',
        PARAM_INT
    ));
}
