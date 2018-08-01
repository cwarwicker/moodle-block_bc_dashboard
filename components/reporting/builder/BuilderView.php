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

namespace BCDB\Views;

class BuilderView extends \BCDB\View {
    
    public function action_edit($args){
        
        global $CFG;
        
        $Config = new \BCDB\Config();
        
        // Sub Navigation links
        $subnav = array();
        $subnav[] = array( 'title' => get_string('save', 'block_bc_dashboard'), 'icon' => 'save', 'url' => '#', 'form' => 'report_form' );    
        
        // Breadcrumb
        $this->addBreadcrumb( array('title' => get_string('reporting', 'block_bc_dashboard'), 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting') );
        
        if ($this->get('report') && $this->get('report')->isValid()){
            
            if ($this->get('report')->canDelete()){
                $subnav[] = array( 'title' => get_string('delete', 'block_bc_dashboard'), 'icon' => 'trash', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/delete/' . $this->get('report')->getID() );
            }
            
            if ($this->get('report')->canSchedule()){
                $subnav[] = array( 'title' => get_string('schedule', 'block_bc_dashboard'), 'icon' => 'calendar', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/schedule/' . $this->get('report')->getID() );
            }
            
            if ($this->get('report')->canRun()){
                $subnav[] = array( 'title' => get_string('run', 'block_bc_dashboard'), 'icon' => 'play-circle-o', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/run/' . $this->get('report')->getID(), 'js' => 'return runFromEdit($(this));' );
            }
            
            if ($this->get('report')->canView()){
                $subnav[] = array( 'title' => get_string('view', 'block_bc_dashboard'), 'icon' => 'eye', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/view/' . $this->get('report')->getID() );
            }
            
            $this->addBreadcrumb( array('title' => $this->get('report')->getName()) );
            
        } else {
            $this->addBreadcrumb( array('title' => get_string('createnewreport', 'block_bc_dashboard')) );
        }
          
        
        $this->set("subNavigation", $subnav);
        
        // Side navigation
        $this->set("sideNavigation", \BCDB\Views\ReportingView::buildSideNav());
        
        $courseCats = $Config->getCourseCategories();
        $this->set("courseCats", $courseCats);
        
        $elements = \BCDB\Report\Element::retrieve();
        $this->set("elements", $elements);
        
        $this->set("reportCats", $Config->buildCategoryList());
        $this->set("userFields", $this->getUserFilters());
        
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
            
            // Breadcrumbs
            $this->addBreadcrumb( array('title' => $report->getName()) );
            
            // Sub Navigation links
            $subnav = array();
            
            if ($report->canRun()){
                $subnav[] = array( 'title' => get_string('run', 'block_bc_dashboard'), 'icon' => 'play-circle-o', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/run/' . $this->get('report')->getID() );
            }
            
            if ($report->canSchedule()){
                $subnav[] = array( 'title' => get_string('schedule', 'block_bc_dashboard'), 'icon' => 'calendar', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/schedule/' . $this->get('report')->getID() );
            }
            
            if ($report->canEdit()){
                $subnav[] = array( 'title' => get_string('edit', 'block_bc_dashboard'), 'icon' => 'pencil', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/edit/' . $this->get('report')->getID() );
            }
                        
            if ($report->canDelete()){
                $subnav[] = array( 'title' => get_string('delete', 'block_bc_dashboard'), 'icon' => 'trash', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/delete/' . $report->getID() );
            }
            
            
            
            // Export as XML - DOn't think this would work for Built, as references things by ID
//            if (has_capability('block/bc_dashboard:export_reports', $this->context)){
//                $subnav[] = array( 'title' => get_string('export', 'block_bc_dashboard'), 'icon' => 'download', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/exportxml/' . $this->get('report')->getID() );
//            }
            
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
                        
            // Breadcrumbs
            $this->addBreadcrumb( array('title' => $report->getName()) );
            
            // Sub Navigation links
            $subnav = array();
            
            if ($report->canEdit()){
                $subnav[] = array( 'title' => get_string('edit', 'block_bc_dashboard'), 'icon' => 'pencil', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/edit/' . $report->getID() );
            }
            
            if ($report->canSchedule()){
                $subnav[] = array( 'title' => get_string('schedule', 'block_bc_dashboard'), 'icon' => 'calendar', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/schedule/' . $report->getID() );
            }
            
            if ($report->canView()){
                $subnav[] = array( 'title' => get_string('view', 'block_bc_dashboard'), 'icon' => 'eye', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/view/' . $report->getID() );
            }
            
            $this->set("subNavigation", $subnav);
                                    
            // Side navigation
            $this->set("sideNavigation", \BCDB\Views\ReportingView::buildSideNav());
            
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
            $output .= "<a href='{$CFG->wwwroot}/blocks/bc_dashboard/reporting/builder/view/{$report->getID()}' class='btn btn-danger'>".get_string('cancel')."</a></form>";

            \bcdb_confirmation_page( get_string('deletereport', 'block_bc_dashboard'), $output, null, null, new \BCDB\Views\BuilderView());

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
            $this->addBreadcrumb( array('title' => $report->getName(), 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/view/' . $report->getID()) );
            $this->addBreadcrumb( array('title' => get_string('schedule', 'block_bc_dashboard')) );
            
            // Sub Navigation links
            $subnav = array();
                        
            // Save the schedule form
            $subnav[] = array( 'title' => get_string('save', 'block_bc_dashboard'), 'icon' => 'save', 'url' => '#', 'form' => 'schedule_form' );    
            
            if ($report->canEdit()){
                $subnav[] = array( 'title' => get_string('edit', 'block_bc_dashboard'), 'icon' => 'pencil', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/edit/' . $report->getID() );
            }
                        
            if ($report->canDelete()){
                $subnav[] = array( 'title' => get_string('delete', 'block_bc_dashboard'), 'icon' => 'trash', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/delete/' . $report->getID() );
            }
            
            if ($report->canRun()){
                $subnav[] = array( 'title' => get_string('run', 'block_bc_dashboard'), 'icon' => 'play-circle-o', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/run/' . $report->getID() );
            }
            
            if ($report->canView()){
                $subnav[] = array( 'title' => get_string('view', 'block_bc_dashboard'), 'icon' => 'eye', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder/view/' . $report->getID() );
            }
        
            $this->set("subNavigation", $subnav);
            
            // Side navigation
            $this->set("sideNavigation", \BCDB\Views\ReportingView::buildSideNav());
            
            // Other variables
            if (!isset($this->vars['tasks'])){
                $this->set("tasks", $report->getEditableScheduledTasks());
            }

        } else {
            bcdb_fatalError('error:invalidreport', 'block_bc_dashboard');
        }
        
    }
    
    /**
     * Get all the user properties so we can apply user filters
     * @global type $USER
     * @return type
     */
    protected function getUserFilters(){
        
        global $USER;
                
        $fields = array();
        foreach($USER as $prop => $val)
        {
            $fields[] = $prop;
        }
        
        sort($fields);
        
        $extraFields = array();
        $custom = profile_get_custom_fields();
        if ($custom)
        {
            foreach($custom as $field)
            {
                $extraFields[] = 'custom_' . $field->shortname;
            }
        }
        
        sort($extraFields);
        
        return array_merge($fields, $extraFields);
        
    }
    
}
