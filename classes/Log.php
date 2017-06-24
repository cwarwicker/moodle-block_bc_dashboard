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


namespace BCDB;

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
    
    public static function add($action, $reportID, $log = ''){
        
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
