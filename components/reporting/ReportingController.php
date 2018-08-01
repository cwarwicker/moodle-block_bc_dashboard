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

namespace BCDB\Controllers;

if (\block_instance('gradetracker')){
    require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';
}



/**
 * Description of DashboardController
 *
 * @author cwarwicker
 */
class ReportingController extends \BCDB\Controller {
    
    protected $component = 'reporting';
    
    protected function initialCheckPermissions(){
                
        // Check permissions to view reporting
        if (!has_capability('block/bc_dashboard:view_reports', $this->context)){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
    }
    
    
    public function action_exportxml($args){
        
        // Check they can export anything
        if (!has_capability('block/bc_dashboard:export_reports', $this->context)){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
        $reportID = (isset($args[0])) ? $args[0] : false;
        $report = \BCDB\Report::load($reportID);
        if (!$report) return false;
        
        // Chekc they have access to this and not trying to export a report they can't actually run
        if (!$report->canRun()) return false;
        
        $report->exportXML();
                
    }
    
}
