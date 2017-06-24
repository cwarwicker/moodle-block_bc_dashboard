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

namespace block_bc_dashboard\task;

require_once $CFG->dirroot . '/blocks/bc_dashboard/lib.php';

class run_scheduled_reports extends \core\task\scheduled_task
{
        
    /**
     * Get the name of the task
     * @return type
     */
    public function get_name(){
        return get_string('task:run_scheduled_reports', 'block_bc_dashboard');
    }
    
    public function execute() {
        
        mtrace("Searching for scheduled reports...");
        \BCDB\ScheduledTask::go();        
        mtrace("Finished");
        
    }
    
}
