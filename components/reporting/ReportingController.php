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
