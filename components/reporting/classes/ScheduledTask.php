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

class ScheduledTask {
    
    private $id = false;
    private $reportID;
    private $time;
    private $repType;
    private $repValues;
    private $params;
    private $emailTo;
    private $lastRun;
    private $nextRun;
    private $createdBy;
    
    private $errors = array();
    
    public function __construct($id = null) {
        
        global $DB;
        
        if ($id > 0){
            
            $record = $DB->get_record("block_bcdb_schedule", array("id" => $id));
            if ($record){
                
                $this->id = $record->id;
                $this->reportID = $record->reportid;
                $this->time = $record->scheduledtime;
                $this->repType = $record->repetitiontype;
                $this->repValues = $record->repetitionvalues;
                $this->params = $record->params;
                $this->emailTo = $record->emailto;
                $this->lastRun = $record->lastrun;
                $this->nextRun = $record->nextrun;
                $this->createdBy = $record->createdbyuserid;
                
            }
            
        }        
        
    }
    
    public function isValid(){
        return ($this->id > 0);
    }
    
    public function getID(){
        return $this->id;
    }
    
    public function getReportID(){
        return $this->reportID;
    }
    
    public function getScheduledTime(){
        return $this->time;
    }
    
    public function getRepetitionType(){
        return $this->repType;
    }
    
    public function getRepetitionValues(){
        return $this->repValues;
    }
    
    public function getParams(){
        return $this->params;
    }
    
    public function getParamsArray(){
        return explode(",", $this->params);
    }
    
    public function getParam($num){
        $params = explode(",", $this->params);
        return (isset($params[$num])) ? $params[$num] : null;
    }
    
    public function getEmailTo(){
        return $this->emailTo;
    }
    
    public function getLastRunUnix(){
        return $this->lastRun;
    }
    
    public function getNextRunUnix(){
        return $this->nextRun;
    }
    
    /**
     * Get a string of when the task last ran
     * @param type $format
     * @return type
     */
    public function getLastRun($format){
        
        if ($this->lastRun > 0){
            return date($format, $this->lastRun);
        } else {
            return get_string('never');
        }
        
    }
    
    /**
     * Get a string of when the task is next due to run
     * @param type $format
     * @return type
     */
    public function getNextRun($format){
        
        if (!is_null($this->nextRun) && $this->nextRun <= time()){
            return '<b>'.get_string('asap', 'block_bc_dashboard').'</b>';
        } elseif (!is_null($this->nextRun) && $this->nextRun > 0){
            return date($format, $this->nextRun);
        } else {
            return get_string('never');
        }
        
    }
    
    
    public function getCreatedBy(){
        return $this->createdBy;
    }
    
    public function getCreatedByName(){
        return \bcdb_get_user_name($this->createdBy);        
    }
    
    public function getErrors(){
        return $this->errors;
    }
    
    public function getFile(){
        
        global $CFG;
        
        $extensions = array('xlsx', 'csv');
        foreach($extensions as $ext)
        {
            if (file_exists($CFG->dataroot . '/BCDB/scheduled_tasks/' . $this->id . '.' . $ext))
            {
                return $this->id . '.' . $ext;
            }
        }
        
        return null;
        
    }
    
    public function setScheduledTime($val){
        $this->time = $val;
        return $this;
    }
    
    public function setRepetitionType($val){
        $this->repType = $val;
        return $this;
    }
    
    public function setRepetitionValues($val){
        $this->repValues = $val;
        return $this;
    }
    
    public function setParams($val){
        $this->params = $val;
        return $this;
    }
    
    public function setEmailTo($val){
        $this->emailTo = $val;
        return $this;
    }
    
    public function setReportID($val){
        $this->reportID = $val;
        return $this;
    }
        
    /**
     * Can they edit/delete this specific scheduled task on the report?
     * @global type $USER
     * @global type $bcdb
     * @return boolean
     */
    public function canEdit(){
        
        global $USER, $bcdb;
        
        // If this is a new one, then yes
        if (!$this->isValid()) return true;
        
        // If we can't load the report, then no
        $report = \BCDB\Report::load($this->reportID);
        if (!$report) return false;
        
        // Was this scheduled task either created by us, or do we have the capability to edit all of them?
        return ( $report->canSchedule() && ($this->createdBy == $USER->id || has_capability('block/bc_dashboard:edit_any_report_schedule', $bcdb['context'])) );
        
    }
    
    /**
     * Check for errors in the submitted form data
     * @return type
     */
    public function hasErrors(){
        
        $this->errors = array();
        
        // Check email contacts are valid usernames
        if ($this->emailTo){
            $contacts = explode(",", $this->emailTo);
            foreach($contacts as $username){
                $user = \bcdb_get_user_by_username($username);
                if (!$user){
                    $this->errors[] = get_string('error:invaliduser', 'block_bc_dashboard') . ' - ' . $username;
                }
            }
        }
        
        return ($this->errors);
        
    }
    
    /**
     * Delete this task
     * @global type $DB
     * @return type
     */
    public function delete(){
        
        global $DB;
        return $DB->delete_records("block_bcdb_schedule", array("id" => $this->id));
        
    }
    
    /**
     * Save a task to the database
     * @global \BCDB\type $DB
     * @global \BCDB\type $USER
     * @return type
     */
    public function save(){
        
        global $DB, $USER;
                
        // Create new
        if (!$this->isValid()){
            
            $obj = new \stdClass();
            $obj->reportid = $this->reportID;
            $obj->scheduledtime = $this->time;
            $obj->repetitiontype = $this->repType;
            $obj->repetitionvalues = $this->repValues;
            $obj->params = $this->params;
            $obj->emailto = $this->emailTo;
            $obj->lastrun = null;
            $obj->nextrun = $this->calculateNextRunTime();
            $obj->createdbyuserid = $USER->id;
            
            $result = $DB->insert_record("block_bcdb_schedule", $obj);
            $this->id = $result;
            
            // Log action
            \BCDB\Log::add( \BCDB\Log::LOG_CREATE_SCHEDULED_TASK, $this->id );
            
        } else {
            
            // Edit existing
            $obj = new \stdClass();
            $obj->id = $this->id;
            $obj->scheduledtime = $this->time;
            $obj->repetitiontype = $this->repType;
            $obj->repetitionvalues = $this->repValues;
            $obj->params = $this->params;
            $obj->emailto = $this->emailTo;
            $obj->nextrun = $this->calculateNextRunTime();
            
            $result = $DB->update_record("block_bcdb_schedule", $obj);
            
            // Log action
            \BCDB\Log::add( \BCDB\Log::LOG_EDIT_SCHEDULED_TASK, $this->id );
            
        }
        
        return $result;
        
    }

    /**
     * Calculate when this task should next run
     * @return int unix timestamp
     */
    private function calculateNextRunTime(){
        
        $unix = false;
        $now = time();
        
        // Date
        if ($this->repType == 'date'){
            
            $unix = strtotime($this->repValues . ' ' . $this->time);
            
            // Have we already run it?
            if ($this->lastRun >= $unix){
                $unix = null;
            }
            
        }
        
        // Daily
        elseif ($this->repType == 'day'){
            
            // Check if this time has already passed today, in which case, next run will be tomorrow
            $today = strtotime($this->time);
            if ($today > $now){
                $unix = $today;
            } else {
                $unix = strtotime("+1 day", $today);
            }
        }
        
        // Weekly
        elseif ($this->repType == 'week'){
            
            $today = strtotime($this->time);
            $todayDayNum = date('N');
            $dayNumbers = explode(",", $this->repValues);
            sort($dayNumbers);
            
            // Check if today is one of the days selected and the time hasn't passed yet
            if (in_array($todayDayNum, $dayNumbers) && $today > $now){
                $unix = $today;
            } else {
            
                // If not, go through the days selected and find the first one after today (or if there are none, then find the first one in the array)
                $afterToday = array_filter($dayNumbers, function($d) use ($todayDayNum) {
                    return ($d > $todayDayNum);
                });

                // If there are some days after today, use the first one
                if ($afterToday){
                    $day = reset($afterToday);
                } else {
                    $day = reset($dayNumbers);
                }
                
                $diff = ($day > $todayDayNum) ? $day - $todayDayNum : $day - $todayDayNum + 7;
                $unix = strtotime("+{$diff} days", $today);
            
            }
                        
        }
        
        // Monthly
        elseif ($this->repType == 'month'){
            
            $today = strtotime($this->time);
            $todayDateDay = date('j');
            $todayDateMonth = date('m');
            $todayDateYear = date('Y');
            $dayNumbers = explode(",", $this->repValues);
            sort($dayNumbers);
            
            // If today is one of the dates selected and the time hasn't passed yet
            if (in_array($todayDateDay, $dayNumbers) && $today > $now){
                $unix = $today;
            } else {
                
                // If not, go through the days selected and find the first one after today (or if there are none, then find the first one in the array)
                $afterToday = array_filter($dayNumbers, function($d) use ($todayDateDay) {
                    return ($d > $todayDateDay);
                });

                // If there are some days after today, use the first one
                if ($afterToday){
                    $day = reset($afterToday);
                    if ($day < 10) $day = '0'.$day;
                    $unix = strtotime($day . '-' . $todayDateMonth . '-' . $todayDateYear . ' ' . $this->time);
                } else {
                    // If not then the day number is lower, so it will be next month
                    $day = reset($dayNumbers);
                    if ($day < 10) $day = '0'.$day;
                    $unix = strtotime($day . '-' . $todayDateMonth . '-' . $todayDateYear . ' ' . $this->time . ' + 1 month');
                }
                                
            }
                        
        }
        
        return ($unix !== false) ? $unix : null;
        
    }
    
    /**
     * Get the repetition type and values as a string to display in the table
     * @return type
     */
    public function repetitionToString(){
    
        if ($this->repType == 'date'){
            return get_string('date') . "<br><small>({$this->repValues})</small>";
        }
        
        elseif ($this->repType == 'day'){
            return get_string('daily', 'block_bc_dashboard');
        }
        
        elseif ($this->repType == 'week'){
            $arr = array();
            $vals = explode(",", $this->repValues);
            if ($vals)
            {
                foreach($vals as $num)
                {
                    $arr[] = date('D', strtotime("Sunday +{$num} days"));
                }
            }
            return get_string('weekly', 'block_bc_dashboard') . "<br><small>(".implode(', ', $arr).")</small>";
        }
        
        elseif ($this->repType == 'month'){
            
            $arr = array();
            $vals = explode(",", $this->repValues);
            if ($vals)
            {
                foreach($vals as $num)
                {
                    $arr[] = bcdb_ordinal($num);
                }
            }
            
            return get_string('monthly', 'block_bc_dashboard') . "<br><small>(".implode(', ', $arr).")</small>";
            
        }
        
    }
    
    public function toJson(){
        
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->reportid = $this->reportID;
        $obj->scheduledtime = $this->time;
        $obj->repetitiontype = $this->repType;
        $obj->repetitionvalues = $this->repValues;
        $obj->params = $this->params;
        $obj->emailto = $this->emailTo;
        $obj->lastrun = $this->lastRun;
        $obj->nextrun = $this->nextRun;
        $obj->createdbyuserid = $this->createdBy;
        $obj->createdbyusername = \bcdb_get_user_name( $this->createdBy );
        
        return json_encode($obj);
        
    }
    
    
    public function run(){
        
        global $CFG, $DB;
        
        $report = \BCDB\Report::load($this->reportID);
        if (!$report){
            mtrace("Unable to load report id {$this->reportID}");
            return false;
        }
        
        mtrace("Loaded report: {$report->getName()}...");
                
        $report->applyParams( $this->getParamsArray() );
        $report->execute();
        $report->runExportExcel();
        
        if (!isset($report->savedFilePath)){
            mtrace("Unable to save excel file for report {$report->getName()}");
            return false;
        }
        
        // Log the action
        \BCDB\Log::add(\BCDB\Log::LOG_SCHEDULED_TASK_RAN, $report->getID(), 'scheduled task ' . $this->id);
        
        
        // Take a copy of the excel file (as the name will be overwritten if it runs again) and copy it to a different path
        $ext = bcdb_get_file_extension($report->savedFilePath);
        $newLocalPath = 'scheduled_tasks/'.$this->id.'.' . $ext;
        $newFile = $CFG->dataroot . '/BCDB/'.$newLocalPath;
        
        \bcdb_create_data_dir('scheduled_tasks');
        
        if (!copy($CFG->dataroot . '/BCDB/' . $report->savedFilePath, $newFile)){
            mtrace("Unable to copy excel file to scheduled_task directory...");
            return false;
        }
        
        // Create a download link
        \bcdb_create_download_code($newLocalPath);
        
        mtrace("Report successfully executed and file copied to {$newFile}...");
        mtrace("Looking for users to contact...");
        
        // Is there anyone to email?
        $emailTo = array_filter( array_map( 'trim', explode(",", $this->emailTo) ) );
        if ($emailTo)
        {
            
            mtrace("Found ".count($emailTo)." users to contact...");
            
            foreach($emailTo as $username)
            {
                                
                $user = \bcdb_get_user_by_username($username);
                if ($user)
                {
                    
                    $message = get_string('scheduledtask:message', 'block_bc_dashboard');
                    $message = str_replace("%url%", $CFG->wwwroot . '/blocks/bc_dashboard/download.php?code=' . \bcdb_create_download_code($newLocalPath), $message);
                    if (email_to_user($user, $user, get_string('scheduledtask:subject', 'block_bc_dashboard') . ' - ' . $report->getName(), clean_param($message, PARAM_TEXT), nl2br($message), 'BCDB' . DIRECTORY_SEPARATOR . $newLocalPath, $report->getName().'.'.$ext) ){
                        mtrace("Sent report to {$user->username} ({$user->email})");
                    } else {
                        mtrace("Unable to sent report to {$user->username} ({$user->email})");
                    }
                                        
                }
                else
                {
                    mtrace($username . " is not a valid user");
                }
                
            }
        }
        
        // Update the last run and next run values
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->lastrun = time() + 1;
        $obj->nextrun = $this->calculateNextRunTime();
        return $DB->update_record("block_bcdb_schedule", $obj);
                
    }
    
    /**
     * Start the moodle scheduled task to find any reports scheduled to be run
     * @global \BCDB\type $DB
     */
    public static function go(){
        
        global $DB;
        
        // Find any scheduled tasks which are overdue
        $now = time();
        $tasks = array();
        
        $records = $DB->get_records_sql("SELECT s.id FROM {block_bcdb_schedule} s INNER JOIN {block_bcdb_reports} r ON r.id = s.reportid WHERE s.nextrun <= ? AND r.del = 0 ORDER BY s.nextrun", array($now));
        if ($records)
        {
            foreach($records as $record)
            {
                $task = new \BCDB\ScheduledTask($record->id);
                if ($task->isValid())
                {
                    $tasks[$task->getID()] = $task;
                }
            }
        }
        
        mtrace("Found ".count($tasks)." scheduled reports waiting to be run...");
        
        if ($tasks)
        {
            foreach($tasks as $task)
            {
                $task->run();
            }
        }
                
    }
    
}
