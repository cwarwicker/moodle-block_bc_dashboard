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

class ReportingView extends \BCDB\View {
    
    public function main(){
        
        global $CFG;
        
        // Page Title
        $this->set("pageTitle", get_string('reporting', 'block_bc_dashboard'));
        
        // Sub Navigation links
        $nav = array();
        
        if (has_capability('block/bc_dashboard:crud_sql_report', $this->context)){
            $nav[] = array( 'title' => get_string('writesqlreport', 'block_bc_dashboard'), 'icon' => 'database', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/sql' );
        }
        
        if (has_capability('block/bc_dashboard:crud_built_report', $this->context)){
            $nav[] = array( 'title' => get_string('buildreport', 'block_bc_dashboard'), 'icon' => 'bar-chart', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/builder' );
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
                $children[] = array('title' => $report->getName(), 'icon' => 'fa-bar-chart', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/'.$report->getType().'/view/' . $report->getID(), 'class' => 'report');
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
                $children[] = array('title' => $report->getName(), 'icon' => 'fa-bar-chart', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/'.$report->getType().'/view/' . $report->getID(), 'class' => 'report');
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
                        $arr['children'][] = array('title' => $report->getName(), 'icon' => 'fa-bar-chart', 'class' => 'report', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/reporting/' . $report->getType() . '/view/' . $report->getID());
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
