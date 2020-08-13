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
 * Dashboard Reporting
 *
 * The Reporting Dashboard plugin is a block which runs alongside the ELBP and Grade Tracker blocks, to provide a better experience and extra features,
 * such as combined reporting across both plugins. It also allows you to create your own custom SQL reports which can be run on any aspect of Moodle.
 *
 * @package     block_bc_dashboard
 * @copyright   2017-onwards Conn Warwicker
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 * @link        https://github.com/cwarwicker/moodle-block_elbp
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Originally developed at Bedford College, now maintained by Conn Warwicker
 *
 */


namespace block_bc_dashboard;

defined('MOODLE_INTERNAL') or die();

/**
 * Description of Log
 *
 * @author cwarwicker
 */
class Log {

    const LOG_RUN_REPORT = 'ran report';
    const LOG_CREATE_REPORT = 'created report';
    const LOG_EDIT_REPORT = 'edited report';
    const LOG_EXPORT_REPORT = 'exported report';
    const LOG_DELETE_REPORT = 'deleted report';
    const LOG_CREATE_SCHEDULED_TASK = 'created new scheduled task';
    const LOG_EDIT_SCHEDULED_TASK = 'edited scheduled task';
    const LOG_DELETED_SCHEDULED_TASK = 'deleted scheduled task';
    const LOG_SCHEDULED_TASK_RAN = 'executed scheduled report task';

    public static function add($action, $reportID, $log = '') {

        global $DB, $USER;

        $obj = new \stdClass();
        $obj->action = $action;
        $obj->userid = $USER->id;
        $obj->reportid = $reportID;
        $obj->log = $log;
        $obj->timestamp = time();

        return $DB->insert_record("block_bcdb_logs", $obj);

    }

}
