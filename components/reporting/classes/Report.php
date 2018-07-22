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

namespace BCDB;

abstract class Report {
    
    protected $id = false;
    protected $type;
    protected $category;
    protected $name;
    protected $description;
    protected $query;
    protected $params;
    protected $options;
    protected $filters;
    protected $createddate;
    protected $createdby;
    protected $del;
    
    protected $errors = array();
    protected $data;
    protected $schedule = array();

    public function __construct($record = false) {
                            
        if ($record){

            $this->id = $record->id;
            $this->type = $record->type;
            $this->category = $record->category;
            $this->name = $record->name;
            $this->description = $record->description;
            $this->query = $record->query;
            $this->params = (array)json_decode($record->params);
            $this->options = (array)json_decode($record->options);
            $this->filters = (array)json_decode($record->filters);
            $this->createddate = $record->createddate;
            $this->createdby = $record->createdby;
            $this->del = $record->del;

        }
            
        
    }
    
    public function isValid(){
        return ($this->id !== false);
    }
    
    public function isDeleted(){
        return ($this->del == 1);
    }
    
    public function getID(){
        return $this->id;
    }
    
    public function getType(){
        return $this->type;
    }
    
    public function getCategory(){
        return $this->category;
    }
    
    /**
     * Get category name
     * NULL = private
     * 0 = uncategorised
     * > 0 = DB record
     * @global \BCDB\type $DB
     */
    public function getCategoryName(){
        
        global $DB;
        
        if (is_null($this->category)){
            return get_string('myprivatereports', 'block_bc_dashboard');
        } elseif ($this->category == 0){
            return get_string('uncategorised', 'block_bc_dashboard');
        } else {        
            return \bcdb_get_full_category_name($this->category);
        }
        
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function setName($val){
        $this->name = $val;
        return $this;
    }
    
    public function getDescription(){
        return $this->description;
    }
    
    public function setDescription($val){
        $this->description = $val;
        return $this;
    }
    
    public function getQuery(){
        return $this->query;
    }
    
    public function setQuery($val){
        $this->query = $val;
        return $this;
    }
    
    public function getParams(){
        return $this->params;
    }
    
    public function setParams($val){
        $this->params = $val;
        return $this;
    }
    
    public function getOptions(){
        return $this->options;
    }
    
    public function addOption($name, $val){
        $this->options[$name] = $val;
        return $this;
    }
    
    public function setOptions($val){
        $this->options = $val;
        return $this;
    }
    
    public function getFilters(){
        return $this->filters;
    }
    
    public function setFilters($val){
        $this->filters = $val;
        return $this;
    }
    
    public function getCreatedDate($format = false){
        return ($format) ? date($format, $this->createddate) : $this->createddate;
    }
    
    public function getCreatedBy(){
        global $DB;
        return $DB->get_record("user", array("id" => $this->createdby));
    }
    
    public function getCreatedByID(){
        return $this->createdby;
    }
    
    public function getErrors(){
        return $this->errors;
    }
    
    public function getData(){
        return $this->data;
    }
    
    public function hasErrors(){
        return false;
    }
    
    public function getOption($name){
        return (is_array($this->options) && array_key_exists($name, $this->options)) ? $this->options[$name] : null;
    }
    
    public function getSchedule(){
        return $this->schedule;
    }
    
    public function addSchedule(\BCDB\Schedule $schedule){
        $this->schedule[] = $schedule;
    }
    
    /**
     * Can the user view this report?
     * Is it either their own report or one in a public category?
     * @global type $USER
     * @return type
     */
    public function canView(){
        
        global $USER, $bcdb;
        return ( has_capability('block/bc_dashboard:view_reports', $bcdb['context']) && ($this->createdby == $USER->id || !is_null($this->category) || is_siteadmin()));
        
    }
    
    /**
     * Check if current user can run the report
     * @global type $bcdb
     * @return type
     */
    public function canRun(){
        
        global $bcdb;
        return ($this->canView() && has_capability('block/bc_dashboard:run_reports', $bcdb['context']));
        
    }
    
    /**
     * Check if current user can schedule the report to run
     * @return type
     */
    public function canSchedule(){
        global $bcdb;
        return ($this->canRun() && has_capability('block/bc_dashboard:edit_report_schedule', $bcdb['context']));
    }
    
    abstract public function canEdit();
    abstract public function loadFormData($data);
    abstract public function run();
    abstract public function export($method);
    
    public function exportXML() {
                
        // Do not export these options, as they are things that will need to be set on the server its being imported into
        $excludeOptions = array('source');
        
        $doc = new \SimpleXMLElement('<xml/>');

        $xml = $doc->addChild('report');
        $xml->addChild('name', $this->name);
        $xml->addChild('description', $this->description);
        $xml->addChild('type', $this->type);
        $xml->addChild('query', $this->query);
        
        $params = $xml->addChild('parameters');
        if ($this->params)
        {
            foreach($this->params as $param)
            {
                $params->addChild('param', json_encode($param));
            }
        }
        
        $opt = $xml->addChild('options');
        if ($this->options)
        {
            foreach($this->options as $optionName => $option)
            {
                if (!in_array($optionName, $excludeOptions))
                {
                    $opt->addChild($optionName, $option);
                }
            }
        }
        
        $name = preg_replace("/[^a-z0-9]/i", "", $this->name);
        $name = str_replace(" ", "_", $name);

        header('Content-disposition: attachment; filename=bcdb_report_'.$name.'.xml');
        header('Content-type: text/xml');

        echo $doc->asXML();
        exit;
        
    }
    
    
    public function importXML(){}
    public function applyParams($params){}
    
    /**
     * Mark a report as deleted
     * @global \BCDB\type $DB
     * @return boolean
     */
    public function delete(){
        
        global $DB;
        
        if (!$this->isValid()){
            return false;
        }
        
        $record = new \stdClass();
        $record->id = $this->id;
        $record->del = 1;
        
        $result = $DB->update_record("block_bcdb_reports", $record);
        if ($result){
            \BCDB\Log::add( \BCDB\Log::LOG_DELETE_REPORT, $this->id );
        }
        
        return $result;
        
    }
    
    public function save(){
        
        global $DB, $USER;
        
        $record = new \stdClass();
        
        $record->id = ($this->isValid()) ? $this->id : null;
        $record->type = $this->type;
        $record->category = $this->category;
        $record->name = $this->name;
        $record->description = $this->description;
        $record->query = $this->query;
        $record->params = (!is_null($this->params)) ? json_encode($this->params) : null;
        $record->options = (!is_null($this->options)) ? json_encode($this->options) : null;
        $record->filters = (!is_null($this->filters)) ? json_encode($this->filters) : null;

        // Update
        if ($this->isValid()){
            $result = $DB->update_record("block_bcdb_reports", $record);
            \BCDB\Log::add( \BCDB\Log::LOG_EDIT_REPORT, $this->id );
        } else {
            $record->createddate = time();
            $record->createdby = $USER->id;
            $this->id = $DB->insert_record("block_bcdb_reports", $record);
            $result = $this->id;
            \BCDB\Log::add( \BCDB\Log::LOG_CREATE_REPORT, $this->id );
        }
            
        return $result;
            
    }
    
    /**
     * 
     * @global type $DB
     * @return type
     */
    public function getLastRun(){
        
        global $DB;
        
        $record = $DB->get_records("block_bcdb_logs", array("reportid" => $this->id, "action" => "run"), "id DESC", "*", 0, 1);
        return ($record) ? array('time' => $record->timestamp, 'by' => $record->userid) : false;
        
    }
    
    /**
     * Get logs about this report
     * @global \BCDB\type $DB
     * @param type $action
     * @param type $limit
     * @return type
     */
    public function getLogs($action = false, $limit = 15){
        
        global $DB;
        
        $return = array();
        $params = array();
        
        $params['reportid'] = $this->id;
        if ($action){
            $params['action'] = $action;
        }
        
        $records = $DB->get_records("block_bcdb_logs", $params, "id DESC", "*", 0, $limit);
        if ($records)
        {
            foreach($records as $record)
            {
                $record->user = (!is_null($record->userid)) ? $DB->get_record("user", array("id" => $record->userid)) : 'System';
                $return[] = $record;
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get the headers to use from the result data
     * @return boolean
     */
    public function getHeaders(){
        
        if (!$this->data){
            return false;
        }
        
        return array_keys((array)reset($this->data));
        
    }
    
    /**
     * Get the datatypes of the data in each column, for table sorting
     * @return boolean|array
     */
    public function getDataTypes(){
        
        if (!$this->data){
            return false;
        }
        
        $types = array();
        
        foreach($this->data as $row){
            foreach($row as $key => $val){
                
                if (!isset($types[$key])){
                    $types[$key] = '';
                }
                                               
                if (is_numeric($val) && $types[$key] != 'string-ins' ){
                    $types[$key] = 'float';
                }
                
                elseif (is_string($val)){
                    $types[$key] = 'string-ins';
                }
                
            }
        }
        
        return $types;
        
    }
    
    /**
     * Get the scheduled tasks on this report which we have permission to edit
     * @global \BCDB\type $DB
     * @return \BCDB\Schedule
     */
    public function getEditableScheduledTasks(){
        
        global $DB;
        
        $return = array();
        
        $records = $DB->get_records("block_bcdb_schedule", array("reportid" => $this->id), "scheduledtime ASC", "id");
        if ($records)
        {
            foreach($records as $record)
            {
                $schedule = new \BCDB\ScheduledTask($record->id);
                if ($schedule->isValid() && $schedule->canEdit())
                {
                    $return[] = $schedule;
                }
            }
        }
        
        return $return;
        
    }
        
    /**
     * Get all scheduled tasks
     * @global \BCDB\type $DB
     * @return type
     */
    public function getAllScheduledTasks(){
        
        global $DB;
        
        $return = array();
        
        $records = $DB->get_records("block_bcdb_schedule", array("reportid" => $this->id), "scheduledtime ASC", "id");
        if ($records)
        {
            foreach($records as $record)
            {
                $schedule = new \BCDB\ScheduledTask($record->id);
                if ($schedule->isValid())
                {
                    $return[] = $schedule;
                }
            }
        }
        
        return $return;        
    }
    
    /**
     * Get your private reports
     * @global \BCDB\type $DB
     * @global \BCDB\type $USER
     * @return type
     */
    public static function priv(){
        
        global $DB, $USER;
        
        $return = array();
        $records = $DB->get_records("block_bcdb_reports", array("createdby" => $USER->id, "category" => null, "del" => 0), "name ASC", "id");
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = self::load($record->id);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get public reports
     * @global \BCDB\type $DB
     * @param type $catID
     * @return type
     */
    public static function pub($catID = false){
        
        global $DB;
        
        $return = array();
        
        if ($catID !== false){
            
            $records = $DB->get_records("block_bcdb_reports", array("category" => $catID, "del" => 0), "name ASC", "id");
            if ($records)
            {
                foreach($records as $record)
                {
                    $return[] = self::load($record->id);
                }
            }

            return $return;
            
        } else {
        
            $config = new \BCDB\Config();

            // First build up an array of public categories
            $return = $config->getReportCategories(true);
            return $return;
        
        }
                
    }
    
    /**
     * Get any reports created by you, public or private
     * @global \BCDB\type $DB
     * @global \BCDB\type $USER
     * @return type
     */
    public static function my(){
        
        global $DB, $USER;
        
        $return = array();
        $records = $DB->get_records("block_bcdb_reports", array("createdby" => $USER->id, "del" => 0), "name ASC", "id");
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = self::load($record->id);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Load a report into its relevant object
     * @global \BCDB\type $DB
     * @param type $id
     * @return \BCDB\Report\SQLReport|\BCDB\Report\BuiltReport
     */
    public static function load($id = false){
        
        global $DB;
        
        if ($id){
            $record = $DB->get_record("block_bcdb_reports", array("id" => $id));
            if ($record){
                switch($record->type)
                {
                    case 'builder':
                        return new \BCDB\Report\BuiltReport($record);
                    break;
                    case 'sql':
                        return new \BCDB\Report\SQLReport($record);
                    break;
                    default:
                        print_error('error:invalidreport', 'block_bc_dashboard');
                    break;
                }
            }
        }
        
    }
    
    
    
    public static function loadFromXML($file){
        
        // Check file uploaded correctly
        if ($file['error'] > 0){
            print_error('error:importreport', 'block_bc_dashboard');
        }
        
        // Check mime type of file to make sure it is XML
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fInfo, $file['tmp_name']);
        finfo_close($fInfo);           
        
        // Has to be XML file, otherwise error and return
        if ($mime != 'application/xml' && $mime != 'text/plain'){
            print_error('error:importreport:mime', 'block_bc_dashboard', '', $mime);
        }
        
        // Open file
        $doc = \simplexml_load_file($file['tmp_name']);
        if (!$doc){
            print_error('error:importreport:load', 'block_bc_dashboard');
        }
                
        $type = $doc->report->type;
        if ($type == 'built'){
            $report = new \BCDB\Report\BuiltReport();
        } elseif ($type == 'sql'){
            $report = new \BCDB\Report\SQLReport();
        } else {
            print_error('error:invalidreport', 'block_bc_dashboard');
        }
        
        $report->setName($doc->report->name);
        $report->setDescription($doc->report->description);
        $report->setQuery($doc->report->query);
        
        $paramsArray = array();
        $params = $doc->report->parameters->param;
        foreach($params as $param)
        {
            $paramsArray[] = json_decode($param);
        }
        $report->setParams($paramsArray);
        
        $options = (array)$doc->report->options;
        $report->setOptions($options);
        
        // Type-specific stuff here, e.g. built report will have various other bits attached to it
        
        
        return $report;
        
    }
    
}
