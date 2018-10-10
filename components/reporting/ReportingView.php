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

class ReportingView extends \BCDB\View {
    
    public function main(){
        
        global $CFG;
        
        // Page Title
        $this->set("pageTitle", get_string('reporting', 'block_bc_dashboard'));
        
        // Sub Navigation links
        $nav = array();
        
        if (has_capability('block/bc_dashboard:crud_sql_report', $this->context)){
            $nav[] = array( 'title' => get_string('writesqlreport', 'block_bc_dashboard'), 'icon' => 'database', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/index.php?Qs=reporting/sql' );
        }
        
        if (has_capability('block/bc_dashboard:crud_built_report', $this->context)){
            $nav[] = array( 'title' => get_string('buildreport', 'block_bc_dashboard'), 'icon' => 'bar-chart', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/index.php?Qs=reporting/builder' );
        }
               
        $this->set("subNavigation", $nav);
        
        // Breadcrumbs
        $this->addBreadcrumb( array('title' => get_string('reporting', 'block_bc_dashboard')) );
        
        // Side navigation
        $this->set("sideNavigation", self::buildSideNav());
        
    }
    
    /**
     * Build the array for the side navigation, so we can call this from other view files
     * @global \BCDB\Views\type $CFG
     * @return string
     */
    public static function buildSideNav(){
        
        global $CFG;
        
        // Side Nav
        $sideNav = array();
        
        // Public reports
        $sideNav[] = array( 'title' => get_string('publicreports', 'block_bc_dashboard'), 'icon' => 'fa-folder|fa-folder-open', 'children' => self::createSideNav(\BCDB\Report::pub()) );
        
        
        
        // Private reports
        $privReports = \BCDB\Report::priv();
        $children = array();
        if ($privReports)
        {
            foreach($privReports as $report)
            {
                $children[] = array('title' => $report->getName(), 'icon' => 'fa-bar-chart', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/index.php?Qs=reporting/'.$report->getType().'/view/' . $report->getID(), 'class' => 'report');
            }
        
        }
            
        $sideNav[] = array( 'title' => get_string('myprivatereports', 'block_bc_dashboard'), 'icon' => 'fa-folder|fa-folder-open', 'children' => $children );
        
        
        
        // My Reports (All of yours, public or private)
        $myReports = \BCDB\Report::my();
        $children = array();
        if ($myReports)
        {
            foreach($myReports as $report)
            {
                $children[] = array('title' => $report->getName(), 'icon' => 'fa-bar-chart', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/index.php?Qs=reporting/'.$report->getType().'/view/' . $report->getID(), 'class' => 'report');
            }
        }
            
        $sideNav[] = array( 'title' => get_string('myreports', 'block_bc_dashboard'), 'icon' => 'fa-folder|fa-folder-open', 'children' => $children );
        
        
        
        return $sideNav;
        
    }
    
    
    /**
     * Create the side navigation for report categories and reports
     * @global type $CFG
     * @param type $cats
     * @return type
     */
    public static function createSideNav($cats){
        
        global $CFG;
        
        $return = array();
        
        if ($cats)
        {
            foreach($cats as $cat)
            {
                
                $arr = array('title' => $cat->name, 'icon' => 'fa-folder|fa-folder-open');
                
                // Sub Categories
                if (isset($cat->children) && $cat->children){
                    $subCats = self::createSideNav($cat->children);
                    $arr['children'] = $subCats;
                }
                
                // Reports in this category
                if (isset($cat->reports) && $cat->reports){
                    
                    foreach($cat->reports as $report)
                    {
                        $arr['children'][] = array('title' => $report->getName(), 'icon' => 'fa-bar-chart', 'class' => 'report', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/index.php?Qs=reporting/' . $report->getType() . '/view/' . $report->getID());
                    }
                                        
                } 
                
                if ( (!isset($cat->children) || !$cat->children) && (!isset($cat->reports) || !$cat->reports) ){
                    $arr['children'][] = array('title' => get_string('noreports', 'block_bc_dashboard'), 'url' => '#', 'class' => 'report');
                }
                
                // Append to side nav
                $return[] = $arr;
                
            }
        }

        return $return;
        
    }
    
    
    
    
    
}
