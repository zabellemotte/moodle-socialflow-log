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
 * Data provider.
 *
 * @package logstore_socialflow
 * Fork of logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Modified by Zabelle Motte (UCLouvain)
 */

namespace logstore_socialflow\privacy;

/**
 * Privacy Subsystem implementation for logstore_socialflow.
 *
 * This provider declares that the plugin does not store any personal data.
 *
 * @package    logstore_socialflow
 * @category   privacy
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Explain that this plugin does not store any personal data.
     *
     * @return string Language string identifier.
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
