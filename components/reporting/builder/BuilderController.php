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

namespace BCDB\Controllers\reporting;

/**
 * Description of DashboardController
 *
 * @author cwarwicker
 */
class BuilderController extends \BCDB\Controller {
    
    protected $action = 'action_edit';
    
    public function getReportFromArgs($args){
        
        if (isset($args[0]) && is_numeric($args[0])){
            $id = $args[0];
            $report = \BCDB\Report::load($id);
            if ($report && $report->isValid() && !$report->isDeleted() && $report->getType() == 'builder'){
                return $report;
            }
        }
        
        return false;
        
    }
    
    public function action_ajax(){
                                
        global $CFG;
        
        if ($_SERVER['REQUEST_METHOD'] != 'POST'){
            exit;
        }
                        
        switch($_POST['action'])
        {
            
            case 'run':
            case 'export':
                                
                // Get params from serialized form
                $params = false;
                if (isset($_POST['params'])){
                    parse_str($_POST['params'], $params);
                }
                
                if (!isset($params['id'])){
                    echo get_string('error:invalidreport', 'block_bc_dashboard');
                    exit;
                }
                
                $report = \BCDB\Report::load($params['id']);
                if (!$report){
                    echo get_string('error:invalidreport', 'block_bc_dashboard');
                    exit;
                }
                
                // Can we run it?
                if (!$report->canRun()){
                    echo get_string('error:invalidaccess', 'block_bc_dashboard');
                    exit;
                }
                                
                // Run the report
                $report->execute();
                
                if ($_POST['action'] == 'export'){
                    
                    $report->export();
                    exit;
                    
                } else {
                    
                    // Return the results to the webpage
                    $result = array(
                        'ok' => 1,
                        'reporttype' => $report->getType(),
                        'datatypes' => $report->getDataTypes(),
                        'headers' => $report->getHeaders('html'),
                        'data' =>  $report->getData()
                    );

                    \BCDB\Log::add(\BCDB\Log::LOG_RUN_REPORT, $report->getID());
                    
                    echo json_encode($result);
                    exit;

                }
                
                
            break;
        
        }
        
        exit;
        
    }
    
        
    
    public function action_edit($args){
    
        global $USER;
        
        if (!has_capability('block/bc_dashboard:crud_built_report', $this->context)){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
        // Load the report if editing one
        if (isset($args[0]) && is_numeric($args[0])){
            
            $id = $args[0];
            $report = \BCDB\Report::load($id);
            
            if ($report && $report->isValid() && !$report->isDeleted() && $report->getType() == 'builder'){
                                
                // If it's not our report, we need permission to edit all
                if ($report->getCreatedByID() <> $USER->id && !has_capability('block/bc_dashboard:edit_any_built_report', $this->context)){
                    \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
                }
                
                $this->view->set("report", $report);
                
            }
            
        }
        
        
        // Have we submitted the form?
        if (isset($_POST['report_id'])){
            return $this->submit_edit();
        }
        
        
    }
    
    
    private function submit_edit(){
        
        // todo - check permission
        $messages = array();
        
        $report = new \BCDB\Report\BuiltReport();
        $report->loadFormData($_POST);
                                
        if (!$report->hasErrors()){
            if ($report->save()){
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
    
    
    public function action_delete($args){
        
        $report = $this->getReportFromArgs($args);
        if (!$report || !$report->canDelete()){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
        if (isset($_POST['confirm'])){
            
            $messages = array();
            
            if ($report->delete() ){
                $messages['success'] = get_string('reportdeleted', 'block_bc_dashboard');
            } else {
                $messages['errors'] = get_string('error:delete', 'block_bc_dashboard');
            }        
            
            $this->view->set("messages", $messages);
                
        }
        
    }
    
    
    public function action_schedule($args){
        
        $report = $this->getReportFromArgs($args);
        if (!$report || !$report->canSchedule()){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
        if (isset($_POST['submitted'])){
            
            $taskArray = array();
            $messages = array('success' => array(), 'errors' => array());
            
            $sIDs = (isset($_POST['sid'])) ? $_POST['sid'] : false;
            $time = (isset($_POST['time'])) ? $_POST['time'] : false;
            $repetitionType = (isset($_POST['rep_type'])) ? $_POST['rep_type'] : false;
            $repetitionValues = (isset($_POST['rep'])) ? $_POST['rep'] : false;
            $emails = (isset($_POST['emails'])) ? $_POST['emails'] : false;
                        
            foreach($sIDs as $i => $sID)
            {
                
                $obj = new \BCDB\ScheduledTask($sIDs[$i]);
                if ($obj->canEdit())
                {
                    
                    $obj->setReportID($report->getID());
                    $obj->setScheduledTime(@implode(":", $time[$i]));
                    $obj->setRepetitionType(@$repetitionType[$i]);
                    $obj->setRepetitionValues(@implode(",", (array)$repetitionValues[$i]));
                    $obj->setEmailTo($emails[$i]);
                    if (!$obj->hasErrors()){
                        if (!$obj->save()){
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
            if ($allTasks)
            {
                foreach($allTasks as $task)
                {
                    if (!array_key_exists($task->getID(), $taskArray))
                    {
                        $task->delete();
                    }
                }
            }
            
            
            
            // If none of them had any errors, display the success message
            if (!$messages['errors']){
                $messages['success'][] = get_string('scheduledtasksaved', 'block_bc_dashboard');
            } else {
                // Otherwise, use these objects for the sticky form
                $this->view->set("tasks", $taskArray);
            }
            
            $this->view->set("messages", $messages);
            
        }
        
    }
    
    public function action_view(){}
    public function action_run(){}
    public function action_export(){}

    
}
