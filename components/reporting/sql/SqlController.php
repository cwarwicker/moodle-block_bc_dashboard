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

namespace block_bc_dashboard\Controllers\reporting;

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot . '/blocks/bc_dashboard/components/config/classes/Config.php');
require_once($CFG->dirroot . '/blocks/bc_dashboard/lib/php-sql-parser/src/PHPSQLParser.php');


/**
 * Description of DashboardController
 *
 * @author cwarwicker
 */
class SqlController extends \block_bc_dashboard\Controller {

    protected $action = 'action_edit';

    public function getReportFromArgs($args) {

        if (isset($args[0]) && is_numeric($args[0])) {
            $id = $args[0];
            $report = \block_bc_dashboard\Report::load($id);
            if ($report && $report->isValid() && !$report->isDeleted() && $report->getType() == 'sql') {
                return $report;
            }
        }

        return false;

    }



    public function action_ajax() {

        global $CFG;

        // Only allow actions if we POST to the script.
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            exit;
        }

        $settings = array(
            'action' => optional_param('action', false, PARAM_TEXT),
            'sql' => optional_param('sql', false, PARAM_TEXT),
        );

        switch ($settings['action']) {
            case 'count_sql_params':
                echo json_encode( \block_bc_dashboard\Report\SQLReport::getParamsInQuery($settings['sql']) );
                break;

            case 'parse_sql':

                require_once($CFG->dirroot . '/blocks/bc_dashboard/lib/php-sql-parser/src/PHPSQLParser.php');
                $fields = \block_bc_dashboard\Report\SQLReport::getSQLFields($settings['sql']);

                echo json_encode($fields);
                exit;

                break;

            case 'run':
            case 'export':

                $settings['params'] = optional_param('params', false, PARAM_TEXT);

                // Get params from serialized form
                $params = false;
                if ($settings['params']) {
                    parse_str($settings['params'], $params);
                }

                if (!isset($params['id'])) {
                    echo get_string('error:invalidreport', 'block_bc_dashboard');
                    exit;
                }

                $report = \block_bc_dashboard\Report::load($params['id']);
                if (!$report) {
                    echo get_string('error:invalidreport', 'block_bc_dashboard');
                    exit;
                }

                // Can we run it?
                if (!$report->canRun()) {
                    echo get_string('error:invalidaccess', 'block_bc_dashboard');
                    exit;
                }

                // Apply the parameter values we set into the report
                if (isset($params['param'])) {
                    $report->applyParams( $params['param'] );
                }

                // Run the report
                $report->run();

                if ($settings['action'] == 'export') {

                    $report->export();
                    exit;

                } else {

                    // Return the results to the webpage
                    $result = array(
                        'ok' => 1,
                        'reporttype' => $report->getType(),
                        'reportsubtype' => $report->getOption('type'),
                        'x-axis' => $report->getOption('xaxis'),
                        'y-axis' => $report->getOption('yaxis'),
                        'datatypes' => $report->getDataTypes(),
                        'headers' => $report->getHeaders(),
                        'data' => $report->getData()
                    );

                    \block_bc_dashboard\Log::add(\block_bc_dashboard\Log::LOG_RUN_REPORT, $report->getID());

                    echo json_encode($result);
                    exit;

                }

                break;

        }

        exit;

    }

    public function action_edit($args) {

        global $USER;

        $settings = array(
            'report_id' => optional_param('report_id', false, PARAM_INT),
        );

        if (!has_capability('block/bc_dashboard:crud_sql_report', $this->context)) {
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }

        // If we submitted an import from XML, build up a report object from that and load the form, so they can change any bits they want to
        if (isset($_FILES['file'])) {
            $report = \block_bc_dashboard\Report::loadFromXML($_FILES['file']);
            $this->view->set("report", $report);
        } else if (isset($args[0]) && is_numeric($args[0])) {
            // If we have an integer passed through, then it is a report ID we are editing
            $id = $args[0];
            $report = \block_bc_dashboard\Report::load($id);
            if ($report && $report->isValid() && !$report->isDeleted() && $report->getType() == 'sql') {

                // If it's not our report, we need permission to edit all
                if ($report->getCreatedByID() <> $USER->id && !has_capability('block/bc_dashboard:edit_any_sql_report', $this->context)) {
                    \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
                }

                $this->view->set("report", $report);

            }
        }

        // Have we submitted the form?
        if ($settings['report_id'] !== false) {
            return $this->submit_edit();
        }

    }


    private function submit_edit() {

        $messages = array();

        $report = new \block_bc_dashboard\Report\SQLReport();
        $report->loadFormData();

        if (!$report->hasErrors()) {
            if ($report->save()) {
                $messages['success'] = get_string('reportsaved', 'block_bc_dashboard');;
            } else {
                $messages['errors'] = get_string('error:unknownsaveerror', 'block_bc_dashboard');
            }
        } else {
            $messages['errors'] = $report->getErrors();
        }

        $this->view->set("messages", $messages);
        $this->view->set("report", $report);

    }

    public function action_delete($args) {

        $submission = array(
            'confirm' => optional_param('confirm', false, PARAM_TEXT),
        );

        $report = $this->getReportFromArgs($args);
        if (!$report || !$report->canDelete()) {
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }

        if ($submission['confirm']) {

            require_sesskey();

            $messages = array();

            if ($report->delete() ) {
                $messages['success'] = get_string('reportdeleted', 'block_bc_dashboard');
            } else {
                $messages['errors'] = get_string('error:delete', 'block_bc_dashboard');
            }

            $this->view->set("messages", $messages);

        }

    }


    /**
     * Schedule page
     * @param type $args
     */
    public function action_schedule($args) {

        $submission = array(
            'submitted' => optional_param('submitted', false, PARAM_TEXT),
        );

        $settings = array(
            'sid' => df_optional_param_array_recursive('sid', false, PARAM_INT),
            'time' => df_optional_param_array_recursive('time', false, PARAM_INT),
            'rep_type' => df_optional_param_array_recursive('rep_type', false, PARAM_TEXT),
            'rep' => df_optional_param_array_recursive('rep', false, PARAM_TEXT),
            'param' => df_optional_param_array_recursive('param', false, PARAM_TEXT),
            'emails' => df_optional_param_array_recursive('emails', false, PARAM_TEXT),
        );

        $report = $this->getReportFromArgs($args);
        if (!$report || !$report->canSchedule()) {
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }

        if ($submission['submitted']) {

            require_sesskey();

            $taskArray = array();
            $messages = array('success' => array(), 'errors' => array());

            $sIDs = ($settings['sid']) ? $settings['sid'] : array();
            $time = ($settings['time']) ? $settings['time'] : array();
            $repetitionType = ($settings['rep_type']) ? $settings['rep_type'] : array();
            $repetitionValues = ($settings['rep']) ? $settings['rep'] : array();
            $params = ($settings['param']) ? $settings['param'] : array();
            $emails = ($settings['emails']) ? $settings['emails'] : array();

            foreach ($sIDs as $i => $sID) {

                $obj = new \block_bc_dashboard\ScheduledTask($sIDs[$i]);
                if ($obj->canEdit()) {

                    $obj->setReportID($report->getID());
                    $obj->setScheduledTime(@implode(":", $time[$i]));
                    $obj->setRepetitionType(@$repetitionType[$i]);
                    $obj->setRepetitionValues(@implode(",", (array)$repetitionValues[$i]));
                    $obj->setParams( @implode(",", (array)$params[$i]) );
                    $obj->setEmailTo($emails[$i]);

                    if (!$obj->hasErrors()) {
                        if (!$obj->save()) {
                            $messages['errors'][] = get_string('error:unknownsaveerror', 'block_bc_dashboard');
                        }
                    } else {
                        $messages['errors'] = array_merge($messages['errors'], $obj->getErrors());
                    }

                    $taskArray[$obj->getID()] = $obj;

                }

            }

            // Now delete any we didn't submit
            $allTasks = $report->getEditableScheduledTasks();
            if ($allTasks) {
                foreach ($allTasks as $task) {
                    if (!array_key_exists($task->getID(), $taskArray)) {
                        $task->delete();
                    }
                }
            }

            // If none of them had any errors, display the success message
            if (!$messages['errors']) {
                $messages['success'][] = get_string('scheduledtasksaved', 'block_bc_dashboard');
            } else {
                // Otherwise, use these objects for the sticky form
                $this->view->set("tasks", $taskArray);
            }

            $this->view->set("messages", $messages);

        }

    }


    public function action_view() {
    }
    public function action_run($args) {
    }

}
