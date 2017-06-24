<?php
/**
 * 
 * @copyright 2017 Bedford College
 * @package Bedford College Dashboard (BCDB)
 * @version 2.0
 * @author Conn Warwicker <cwarwicker@bedford.ac.uk> <conn@cmrwarwicker.com>
 * 
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 * 
 */

namespace BCDB\Views;

class SqlView extends \BCDB\View {
        
        
    public function action_edit($args){
        
        global $CFG;
                        
        // Page Title
        $this->set("pageTitle", get_string('sqlreport', 'block_bc_dashboard'));
        $this->set("args", $args);
        
        
        // Sub Navigation links
        $subnav = array();
        $subnav[] = array( 'title' => get_string('save', 'block_bc_dashboard'), 'icon' => 'save', 'url' => '#', 'form' => 'report_form' );
        
        // Breadcrumb
        $this->addBreadcrumb( array('title' => get_string('reporting', 'block_bc_dashboard'), 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting') );
        
        if ($this->get('report') && $this->get('report')->isValid()){
            
            if ($this->get('report')->canDelete()){
                $subnav[] = array( 'title' => get_string('delete', 'block_bc_dashboard'), 'icon' => 'trash', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/delete/' . $this->get('report')->getID() );
            }
            
            if ($this->get('report')->canSchedule()){
                $subnav[] = array( 'title' => get_string('schedule', 'block_bc_dashboard'), 'icon' => 'calendar', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/schedule/' . $this->get('report')->getID() );
            }
            
            if ($this->get('report')->canRun()){
                $subnav[] = array( 'title' => get_string('run', 'block_bc_dashboard'), 'icon' => 'play-circle-o', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/run/' . $this->get('report')->getID(), 'js' => 'return runFromEdit($(this));' );
            }
            
            if ($this->get('report')->canView()){
                $subnav[] = array( 'title' => get_string('view', 'block_bc_dashboard'), 'icon' => 'eye', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/view/' . $this->get('report')->getID() );
            }
            
            $this->addBreadcrumb( array('title' => $this->get('report')->getName()) );
        } else {
            $this->addBreadcrumb( array('title' => get_string('createnewreport', 'block_bc_dashboard')) );
        }
                
        $this->set("subNavigation", $subnav);
        
        
        // All possible parameter formats
        $this->set("formats", \BCDB\SQLParameter::getAvailableFormats());
        
        $config = new \BCDB\Config();
        $this->set("reportCats", $config->buildCategoryList());
        
        if ($this->get('report')){
            $this->set("sqlFields", $this->get('report')->fields());
        }
        
        // Side navigation
        $this->set("sideNavigation", \BCDB\Views\ReportingView::buildSideNav());
        
        
    }
    
    
    public function action_view($args){
        
        global $CFG;
        
        // Breadcrumbs
        $this->addBreadcrumb( array('title' => get_string('reporting', 'block_bc_dashboard'), 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting') );
        
        
        // If we have an integer passed through, then it is a report ID we are editing             
        $report = $this->Controller->getReportFromArgs($args);
        if ($report && $report->canView()){
            
            $this->set("pageTitle", $report->getName());
            $this->set("report", $report);
            $this->set("logs", $report->getLogs());
            $this->set("tasks", $report->getAllScheduledTasks());

            // BReadcrumb
            $this->addBreadcrumb( array('title' => $report->getName()) );
            
            // Sub Navigation links
            $subnav = array();
            
            if ($report->canRun()){
                $subnav[] = array( 'title' => get_string('run', 'block_bc_dashboard'), 'icon' => 'play-circle-o', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/run/' . $report->getID() );
            }
            
            if ($report->canSchedule()){
                $subnav[] = array( 'title' => get_string('schedule', 'block_bc_dashboard'), 'icon' => 'calendar', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/schedule/' . $report->getID() );
            }
            
            if ($report->canEdit()){
                $subnav[] = array( 'title' => get_string('edit', 'block_bc_dashboard'), 'icon' => 'pencil', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/edit/' . $report->getID() );
            }
            
            if ($report->canDelete()){
                $subnav[] = array( 'title' => get_string('delete', 'block_bc_dashboard'), 'icon' => 'trash', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/delete/' . $report->getID() );
            }
            
            
            
            // Export as XML
            if (has_capability('block/bc_dashboard:export_reports', $this->context)){
                $subnav[] = array( 'title' => get_string('export', 'block_bc_dashboard'), 'icon' => 'download', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/exportxml/' . $report->getID() );
            }
            
            $this->set("subNavigation", $subnav);
            
            // Side navigation
            $this->set("sideNavigation", \BCDB\Views\ReportingView::buildSideNav());

        } else {
            bcdb_fatalError('error:invalidreport', 'block_bc_dashboard');
        }
                    
    }
    
    
    public function action_run($args){
        
        global $CFG;
        
        $this->addBreadcrumb( array('title' => get_string('reporting', 'block_bc_dashboard'), 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting') );

        $report = $this->Controller->getReportFromArgs($args);
        if ($report && $report->canRun()){
                        
            // Report
            $this->set("report", $report);
            $this->set("parameters", $this->getSQLParams($report));
            
            // Breadcrumbs
            $this->addBreadcrumb( array('title' => $report->getName()) );
            
            // Sub Navigation links
            $subnav = array();
            
            if ($report->canEdit()){
                $subnav[] = array( 'title' => get_string('edit', 'block_bc_dashboard'), 'icon' => 'pencil', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/edit/' . $report->getID() );
            }
            
            if ($report->canSchedule()){
                $subnav[] = array( 'title' => get_string('schedule', 'block_bc_dashboard'), 'icon' => 'calendar', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/schedule/' . $report->getID() );
            }
            
            if ($report->canView()){
                $subnav[] = array( 'title' => get_string('view', 'block_bc_dashboard'), 'icon' => 'eye', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/view/' . $report->getID() );
            }
            $this->set("subNavigation", $subnav);
                        
            if (\block_instance('gradetracker')){
                $listOfQuals = \bcdb_get_gradetracker_quals();
                $this->set("listOfQuals", $listOfQuals);
            }
            
            // Side navigation
            $this->set("sideNavigation", \BCDB\Views\ReportingView::buildSideNav());
            
        } else {
            bcdb_fatalError('error:invalidreport', 'block_bc_dashboard');
        }
        
    }
    
    
    public function action_delete($args){
        
        global $CFG;
        
        $report = $this->Controller->getReportFromArgs($args);
                
        if (!isset($_POST['confirm'])){

            $output = "";
            $output .= "<form action='' method='post'>";
            $output .= "<p>".get_string('areyousuredeletereport', 'block_bc_dashboard')."</p>";
            $output .= "<h4>{$report->getName()}</h4>";
            $output .= "<br><br>";
            $output .= "<button class='btn btn-primary' type='submit' name='confirm'>".get_string('delete', 'block_bc_dashboard')."</button>&nbsp;&nbsp;";
            $output .= "<a href='{$CFG->wwwroot}/blocks/bc_dashboard/reporting/sql/view/{$report->getID()}' class='btn btn-danger'>".get_string('cancel')."</a></form>";

            \bcdb_confirmation_page( get_string('deletereport', 'block_bc_dashboard'), $output, null, null, new \BCDB\Views\SqlView());

        }
        
        
    }
    
    
    
    
    public function action_schedule($args){
        
        global $CFG;
        
        // Breadcrumbs
        $this->addBreadcrumb( array('title' => get_string('reporting', 'block_bc_dashboard'), 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting') );
        
        
        // If we have an integer passed through, then it is a report ID we are editing             
        $report = $this->Controller->getReportFromArgs($args);
        if ($report && $report->canSchedule()){
            
            $this->set("pageTitle", $report->getName());
            $this->set("report", $report);

            // Breadcrumbs
            $this->addBreadcrumb( array('title' => $report->getName(), 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/view/' . $report->getID()) );
            $this->addBreadcrumb( array('title' => get_string('schedule', 'block_bc_dashboard')) );
            
            // Sub Navigation links
            $subnav = array();
                        
            // Save the schedule form
            $subnav[] = array( 'title' => get_string('save', 'block_bc_dashboard'), 'icon' => 'save', 'url' => '#', 'form' => 'schedule_form' );    
            
            if ($report->canEdit()){
                $subnav[] = array( 'title' => get_string('edit', 'block_bc_dashboard'), 'icon' => 'pencil', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/edit/' . $report->getID() );
            }
                        
            if ($report->canDelete()){
                $subnav[] = array( 'title' => get_string('delete', 'block_bc_dashboard'), 'icon' => 'trash', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/delete/' . $report->getID() );
            }
            
            if ($report->canRun()){
                $subnav[] = array( 'title' => get_string('run', 'block_bc_dashboard'), 'icon' => 'play-circle-o', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/run/' . $report->getID() );
            }
            
            if ($report->canView()){
                $subnav[] = array( 'title' => get_string('view', 'block_bc_dashboard'), 'icon' => 'eye', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql/view/' . $report->getID() );
            }
        
            $this->set("subNavigation", $subnav);
            
            // Side navigation
            $this->set("sideNavigation", \BCDB\Views\ReportingView::buildSideNav());
            
            // Other variables
            if (!isset($this->vars['tasks'])){
                $this->set("tasks", $report->getEditableScheduledTasks());
            }
            
            $this->set("parameters", $this->getSQLParams($report, $this->vars['tasks']));
            
            if (\block_instance('gradetracker')){
                $listOfQuals = \bcdb_get_gradetracker_quals();
                $this->set("listOfQuals", $listOfQuals);
            }

        } else {
            bcdb_fatalError('error:invalidreport', 'block_bc_dashboard');
        }
        
    }
    
    /**
     * Get possible parameters from a report
     * @param type $report
     * @return type
     */
    private function getSQLParams($report, $forceValuesIntoTasks = false){
        
        $params = array();
        if ($report->getParams()){
            
            foreach($report->getParams() as $key => $param){
                
                $param->num = $key;
                
                if (isset($param->default)){
                    $param->default = (isset($_GET[$key])) ? $_GET[$key] : $param->default; // e.g. /run/6&0=valueforparam0&1=valueforparam1
                }
                                
                $obj = \BCDB\SQLParameter::load($param);
                
                // Force value
                if ($forceValuesIntoTasks){
                                        
                    foreach($forceValuesIntoTasks as $num => $task){
                        $obj->elementValue[$num] = $task->getParam($key);
                    }
                                        
                }
                
                $params[] = $obj;
                
            }
            
        }
        
        return $params;
        
    }
    
    
}
