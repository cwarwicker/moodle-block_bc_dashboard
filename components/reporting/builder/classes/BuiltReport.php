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
namespace BCDB\Report;

/**
 * Description of BuiltReport
 *
 * @author cwarwicker
 */
class BuiltReport extends \BCDB\Report {

    protected $type = 'builder';

    private $elements = array();
    private $startingPoint;

    public function __construct($record = false) {

        parent::__construct($record);

        if ($this->isValid())
        {
            // Set the starting point option into its own variable - WHY???
            $this->startingPoint = $this->getOption('startingpoint');

            // Get the basic params and set actual elements into the array
            if ($this->params)
            {
                foreach($this->params as $num => $param)
                {
                    $element = \BCDB\Report\Element::load($param->id);
                    if ($element)
                    {
                        $element->setDisplayName($param->displayname);
                        $element->setParams($param->params);
                        $element->refreshOptions();
                        $this->addElement($element);
                    }
                }
            }
        }

    }

    public function addElement(\BCDB\Report\Element $element){
        $this->elements[] = $element;
        $element->setAlias('t'.count($this->elements));
    }

    public function getElements(){
        return $this->elements;
    }

    public function setElements(array $elements){
        $this->elements = $elements;
    }

    public function setStartingPoint($start){
        $this->startingPoint = $start;
    }

    public function getStartingPoint(){
        return $this->startingPoint;
    }

    public function getStartingPointName(){

        $cat = \core_course_category::get($this->startingPoint);
        return ($cat) ? $cat->get_formatted_name() : '';

    }

    public function getElementNames(){

        $array = array();
        if ($this->elements)
        {
            foreach($this->elements as $element)
            {
                $array[] = $element->getDisplayName();
            }
        }

        return $array;

    }

    public function execute(){

        $this->data = array();

        // Run for the starting point category
        if ($this->startingPoint > 0){
            $this->data[$this->startingPoint] = array();
            $this->data[$this->startingPoint]['cats'] = array();
            $this->data[$this->startingPoint]['courses'] = array();
            $this->get($this->startingPoint);
        }

        // Otherwise find all the top level categories in the configuration setting and use all of those
        else
        {

            $Config = new \BCDB\Config();
            $cats = $Config->getTopLevelCourseCategories();
            if ($cats)
            {
                foreach($cats as $cat)
                {
                    $this->data[$cat->id] = array();
                    $this->data[$cat->id]['cats'] = array();
                    $this->data[$cat->id]['courses'] = array();
                    $this->get($cat->id);
                }
            }

        }

    }

    // Unused abstract method
    public function run(){}



    /**
     * This is called by ->execute() on the starting category
     * @param type $catID
     * @param type $courseID
     * @param type $results
     */
    private function get($catID, $courseID = null, &$results = null){

        if (is_null($results)){
            $results = &$this->data[$catID];
        }

        // If we passed through a course ID, now we find the users on the course
        if (!is_null($courseID))
        {
            $results = $this->results($courseID);
        }

        // If we only passed through a category ID, find the courses on the category (and sub cats)
        else
        {

            $cat = \core_course_category::get($catID);
            $results['name'] = $cat->name;
            $results['type'] = 'cat';

            $courses = $this->getCourses($catID);
            if ($courses)
            {
                foreach($courses as $course)
                {

                    if (!isset($results['courses'][$course->id])){
                        $results['courses'][$course->id] = array();
                    }

                    $this->get($catID, $course->id, $results['courses'][$course->id]);

                }
            }

            // Sort courses


            // Sub Cats
            $subCats = $this->getSubCats($catID);
            if ($subCats)
            {
                foreach($subCats as $subCat)
                {
                    if (!isset($results['cats'][$subCat->id])){
                        $results['cats'][$subCat->id] = array();
                    }
                    $this->get($subCat->id, null, $results['cats'][$subCat->id]);
                }
            }


            // Now do the totalling up of everything underneath this
            $totalsArray = array($catID => array());
            $this->getTotals($results, $totalsArray, $catID);


        }

    }

    /**
     * Add up the course totals
     * @param type $totalsArray
     * @return type
     */
    private function getTotals(&$results, &$totalsArray, $catID){

        // First do each course in this category
        if (isset($results['courses']) && $results['courses'])
        {
            foreach($results['courses'] as $courseID => &$course)
            {

                // Total the users up on this course
                $course['totals'] = $this->total($course['users']);

                // Then add the array of users to the overall array for the categories we are currently totalling
                foreach($totalsArray as &$total)
                {
                    $total = $total + $course['users'];
                }

            }
        }


        // Sub cats
        if (isset($results['cats']) && $results['cats'])
        {
            foreach($results['cats'] as $subCatID => $cat)
            {
                $this->getTotals($results['cats'][$subCatID], $totalsArray, $subCatID);
            }
        }

        // Now we have finished evrything beneath this level, so total up this category and add it to the results array
        if (isset($totalsArray[$catID])){
            $results['usercnt'] = count($totalsArray[$catID]);
            $results['totals'] = $this->total($totalsArray[$catID]);
        }

    }


    private function total($users){

        $return = array();

        if ($this->elements)
        {
            foreach($this->elements as $element)
            {
                $aggregate = $element->aggregate($users);
                if ($aggregate)
                {
                    $key = key($aggregate);
                    $return[$key] = $aggregate[$key];
                }
            }
        }

        return $return;

    }


    /**
     * Get courses in a category
     * @global \BCDB\Report\type $DB
     * @param type $catID
     * @return type
     */
    private function getCourses($catID){

        global $DB;

        // Get parent courses
        $courses = $DB->get_records("course", array("category" => $catID), "fullname ASC");

        // If we only want child courses, now get all the child courses off them instead
        if ($this->getOption("course_type") == "child")
        {

            // Loop through courses and get all the child courses
            $return = array();

            if ($courses)
            {
                foreach($courses as $course)
                {
                    $children = \bcdb_get_child_courses($course->id);
                    $return = $return + $children;
                }
            }

            // Sort them
            \bcdb_sort($return, 'fullname');

            return $return;

        }
        else
        {
            return $courses;
        }

    }

    /**
     * Get course sub categories
     * @global type $DB
     * @param type $catID
     * @return type
     */
    private function getSubCats($catID){

        global $DB;
        return $DB->get_records("course_categories", array("parent" => $catID));

    }

    private function results($courseID){

        global $DB;

        $course = \bcdb_get_course($courseID);
        if (!$course){
            return false;
        }

        $return = array(
            'name' => $course->fullname,
            'type' => 'course',
            'users' => array()
        );

        $query = $this->buildSQL($courseID);
        $sql = $query['sql'];
        $sqlParams = $query['params'];

        // Run the SQL
        $return['users'] = $DB->get_records_sql($sql, $sqlParams);
        if ($return['users']){
            $return['users'] = \bcdb_to_array($return['users']);
        }

        $return['usercnt'] = count($return['users']);

        // Now go through any which need to call functions instead of adding to the SQL
        $this->functions($return);

        return $return;

    }


    private function functions(&$results){

        if ($this->elements)
        {
            foreach($this->elements as $element)
            {
                if ($element->getType() == 'function' || $element->getType() == 'hybrid')
                {

                    // Call the function for each of the users in the results records
                    $element->call($results);

                }
            }
        }

    }


    private function buildSQL($courseID){

        $return = array('sql' => '', 'params' => array());

        $sqlArray = array(
            'select' => array('distinct u.id', 'u.firstname', 'u.lastname', 'u.username'),
            'from' => '{user} u',
            'join' => array('inner join {role_assignments} ra ON ra.userid = u.id', 'inner join {context} ctxt ON ctxt.id = ra.contextid'),
            'where' => array('u.deleted = 0', 'ra.roleid = 5', 'ctxt.instanceid = ?'),
            'group' => 'u.id',
            'order' => 'u.lastname, u.firstname, u.username'
        );

        $paramsArray = array();

        // Loop through elements
        if ($this->elements)
        {
            foreach($this->elements as $element)
            {
                // Run the element to get
                if ($element->getType() == 'sql' || $element->getType() == 'hybrid')
                {

                    // Run the definition and apply the table alias to the sql strings
                    $elementSQLArray = $element->run();

                    // Merge the sql for the element into the overall sql
                    $sqlArray = array_merge_recursive($sqlArray, $elementSQLArray);

                    // Get the sql params
                    if (isset($elementSQLArray['params']) && $elementSQLArray['params'])
                    {
                        foreach($elementSQLArray['params'] as $sqlParam)
                        {
                            $paramsArray[] = $sqlParam;
                        }
                    }

                }
            }
        }

        // Now add the course ID param to the end of the params array
        $paramsArray[] = $courseID;

        // Now any filters
        if ($this->filters)
        {
            foreach($this->filters as $type => $typeFilters)
            {
                foreach($typeFilters as $filter)
                {

                    // User filters
                    if ($type == 'user')
                    {

                        // Build the where clause

                        $clause = "u.".clean_param($filter->field, PARAM_ALPHANUMEXT)." ";

                        if ($filter->cmp == 'equals') $clause .= '= ';
                        elseif ($filter->cmp == 'notequals') $clause .= '!= ';
                        else continue;

                        $clause .= '?';

                        $sqlArray['where'][] = $clause;
                        $paramsArray[] = $filter->val;

                    }

                }
            }
        }

        // Convert array to actual SQL statement
        $return['sql'] = $this->convertToSQL($sqlArray);
        $return['params'] = $paramsArray;

        return $return;

    }

    /**
     * Convert an array of select, join, where, group, into an sql string
     * @param type $sqlArray
     * @return string
     */
    private function convertToSQL($sqlArray){

        // Remove empty elements
        $sqlArray['select'] = array_filter($sqlArray['select']);
        $sqlArray['join'] = array_filter($sqlArray['join']);
        $sqlArray['where'] = array_filter($sqlArray['where']);

        $sql = "";

        // Select
        $sql .= "SELECT " . implode(", ", $sqlArray['select']) . " ";

        // From
        $sql .= "FROM " . $sqlArray['from'] . " ";

        // Joins
        foreach($sqlArray['join'] as $join)
        {
            $sql .= $join . " ";
        }

        // Where
        $sql .= "WHERE " . implode(" AND ", $sqlArray['where']) . " ";

        // Group
        $sql .= "GROUP BY " . $sqlArray['group'] . " ";

        // Order
        $sql .= "ORDER BY " . $sqlArray['order'] . " ";

        return $sql;

    }

    /**
     * Check if current user has permissions to edit this report
     * @global type $USER
     * @return type
     */
    public function canEdit(){

        global $USER, $bcdb;
        return ( $this->canView() && has_capability('block/bc_dashboard:crud_built_report', $bcdb['context']) && ( $this->createdby == $USER->id || has_capability('block/bc_dashboard:edit_any_built_report', $bcdb['context']) ) );

    }

    public function getHeaders($method = false) {

        $headers = array();

        if ($method == 'html'){
            $headers[] = get_string('name');
        } else {
            $headers[] = get_string('category');
            $headers[] = get_string('course');
            $headers[] = get_string('firstname');
            $headers[] = get_string('lastname');
            $headers[] = get_string('username');
        }

        $headers[] = get_string('numberofstudents', 'block_bc_dashboard');

        // Elements
        if ($this->elements)
        {
            foreach($this->elements as $element)
            {
                $headers[$element->getAliasName()] = $element->getDisplayName();
            }
        }

        return $headers;

    }


    public function export($method = 'excel'){

        global $CFG;

        require_once $CFG->dirroot . '/lib/filelib.php';

        // Clear any output
        ob_end_clean();

        // Try and create a directory to store the saved files in
        if (!\bcdb_create_data_dir("built_reports")){
            echo json_encode( array('errors' => array( get_string('error:createdir', 'block_bc_dashboard') ) ) );
            exit;
        }

        // Try and create a directory to store the saved files in
        if (!\bcdb_create_data_dir("built_reports/{$this->id}")){
            echo json_encode( array('errors' => array( get_string('error:createdir', 'block_bc_dashboard') ) ) );
            exit;
        }

        switch($method)
        {

            case 'excel':
            default:

                $this->runExportExcel();

                // Create download link
                $code = \bcdb_create_download_code($this->savedFilePath);

                // Send file
                \BCDB\Log::add(\BCDB\Log::LOG_EXPORT_REPORT, $this->id);
                echo json_encode( array('download' => $code) );
                exit;

            break;

        }

    }

    private function getExcelStyle($type){

        $styles = array(

            'header' => array(
                    'fill' => array(
                        'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => '041363')
                    ),
                    'font' => array(
                        'bold' => true,
                        'color' => array('rgb' => 'ffffff')
                    )
                ),

            'cat' => array(
                    'fill' => array(
                        'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => '87005E')
                    ),
                    'font' => array(
                        'bold' => true,
                        'color' => array('rgb' => 'ffffff')
                    )
                ),

            'course' => array(
                    'fill' => array(
                        'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => 'FFED34')
                    ),
                    'font' => array(
                        'bold' => true,
                        'color' => array('rgb' => '000000')
                    )
                ),


            'subcat' => array(
                    'fill' => array(
                        'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => '006E13')
                    ),
                    'font' => array(
                        'bold' => true,
                        'color' => array('rgb' => 'ffffff')
                    )
                ),


            'subcourse' => array(
                    'fill' => array(
                        'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => '904F00')
                    ),
                    'font' => array(
                        'bold' => true,
                        'color' => array('rgb' => '000000')
                    )
                ),

        );

        return (isset($styles[$type])) ? $styles[$type] : false;

    }

    /**
     * Run the export for a category's data and all the sub data below it
     * @param type $catID
     * @param type $data
     * @param type $objPHPExcel
     * @param type $rowNum
     * @param type $parentName
     */
    private function runRecursiveExcelCategory($catID, $data, &$objPHPExcel, &$rowNum, $parentName = ''){

        $category = \core_course_category::get($catID);

        $catName = $parentName . $category->name;

        // Now do the category totals
        $objPHPExcel->getActiveSheet()->setCellValue("A{$rowNum}", $catName);
        $objPHPExcel->getActiveSheet()->setCellValue("B{$rowNum}", '-');
        $objPHPExcel->getActiveSheet()->setCellValue("C{$rowNum}", '-');
        $objPHPExcel->getActiveSheet()->setCellValue("D{$rowNum}", '-');
        $objPHPExcel->getActiveSheet()->setCellValue("E{$rowNum}", '-');
        $objPHPExcel->getActiveSheet()->setCellValue("F{$rowNum}", $data['usercnt']);

        $letter = 'G';

        if ($this->elements)
        {
            foreach($this->elements as $element)
            {
                $val = (isset($data['totals'][$element->getAliasName()])) ? $element->val($data['totals']) : 0;
                $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $val);
                $letter++;
            }
        }

        // Style the category row
        $style = ($parentName == '') ? 'cat' : 'subcat';
        $objPHPExcel->getActiveSheet()->getStyle("A{$rowNum}:".bcdb_decrement_letter($letter)."{$rowNum}")->applyFromArray( $this->getExcelStyle($style) );

        // Increment the row
        $rowNum++;



        // Now onto the courses
        // First do the courses on this category
        if (isset($data['courses']) && $data['courses'])
        {
            foreach($data['courses'] as $courseID => $courseRow)
            {

                // Course Info
                $course = get_course($courseID);

                // Course Totals Row
                $objPHPExcel->getActiveSheet()->setCellValue("A{$rowNum}", $catName);
                $objPHPExcel->getActiveSheet()->setCellValue("B{$rowNum}", $course->fullname);
                $objPHPExcel->getActiveSheet()->setCellValue("C{$rowNum}", '-');
                $objPHPExcel->getActiveSheet()->setCellValue("D{$rowNum}", '-');
                $objPHPExcel->getActiveSheet()->setCellValue("E{$rowNum}", '-');
                $objPHPExcel->getActiveSheet()->setCellValue("F{$rowNum}", $courseRow['usercnt']);

                $letter = 'G';

                if ($this->elements)
                {
                    foreach($this->elements as $element)
                    {
                        $val = (isset($courseRow['totals'][$element->getAliasName()])) ? $element->val($courseRow['totals']) : 0;
                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $val);
                        $letter++;
                    }
                }

                // Style the course row
                $style = ($parentName == '') ? 'course' : 'subcourse';
                $objPHPExcel->getActiveSheet()->getStyle("A{$rowNum}:".bcdb_decrement_letter($letter)."{$rowNum}")->applyFromArray( $this->getExcelStyle('course') );


                // Increment row
                $rowNum++;


                // Now the users
                if ($courseRow['users'])
                {
                    foreach($courseRow['users'] as $userID => $userRow)
                    {

                        $objPHPExcel->getActiveSheet()->setCellValue("A{$rowNum}", $catName);
                        $objPHPExcel->getActiveSheet()->setCellValue("B{$rowNum}", $course->fullname);
                        $objPHPExcel->getActiveSheet()->setCellValue("C{$rowNum}", $userRow['firstname']);
                        $objPHPExcel->getActiveSheet()->setCellValue("D{$rowNum}", $userRow['lastname']);
                        $objPHPExcel->getActiveSheet()->setCellValue("E{$rowNum}", $userRow['username']);
                        $objPHPExcel->getActiveSheet()->setCellValue("F{$rowNum}", '-');

                        $letter = 'G';

                        if ($this->elements)
                        {
                            foreach($this->elements as $element)
                            {
                                $val = (isset($userRow[$element->getAliasName()])) ? $element->val($userRow) : 0;
                                $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $val);
                                $letter++;
                            }
                        }

                        // Increment row
                        $rowNum++;

                    }

                }

            }

        }


        // Now do any sub cats
        if (isset($data['cats']) && $data['cats'])
        {
            foreach($data['cats'] as $subCatID => $subCatRow)
            {
                $this->runRecursiveExcelCategory($subCatID, $subCatRow, $objPHPExcel, $rowNum, $catName . ' / ');
            }
        }


    }


    /**
     * Run the Excel export
     * @global type $CFG
     * @global \BCDB\Report\type $USER
     * @return type
     */
    public function runExportExcel(){

        global $CFG, $USER;

        require_once $CFG->dirroot . '/lib/phpexcel/PHPExcel.php';

        // Filter report name to work properly as a excel file name
        $reportName = preg_replace("/[^a-z0-9 _]/i", "", $this->name);
        if (strlen($reportName) == 0){
            $reportName = 'RPT';
        }

        $objPHPExcel = new \PHPExcel();

        // Set file properties
        if ($USER->id){
            $objPHPExcel->getProperties()->setCreator(fullname($USER))
                        ->setLastModifiedBy(fullname($USER));
        }

        $objPHPExcel->getProperties()->setTitle($reportName)
                                     ->setSubject($reportName)
                                     ->setDescription($reportName . " generated by BCDB Reporting Dashboard");

        $objPHPExcel->setActiveSheetIndex(0);

        // Convert report data into rows
        if ($this->data)
        {
            foreach($this->data as $catID => $data)
            {

                // New worksheet for each top level category
                $category = \core_course_category::get($catID);
                $catName = preg_replace("/[^a-z0-9 _]/i", "", $category->name);
                $catName = substr($catName, 0, 31);
                $catName = (strlen($catName)) ? $catName : '-';
                $objPHPExcel->getActiveSheet()->setTitle($catName);

                // Headers
                $rowNum = 1;
                $headerLetter = 'A';

                foreach($this->getHeaders() as $header)
                {
                    $objPHPExcel->getActiveSheet()->setCellValue("{$headerLetter}{$rowNum}", $header);
                    $headerLetter++;
                }

                // Style the header row
                $objPHPExcel->getActiveSheet()->getStyle("A{$rowNum}:".bcdb_decrement_letter($headerLetter)."{$rowNum}")->applyFromArray( $this->getExcelStyle('header') );

                // Increment the row
                $rowNum++;


                // Recursively run this category and all its sub categories
                $this->runRecursiveExcelCategory($catID, $data, $objPHPExcel, $rowNum);


                // Freeze the top row
                $objPHPExcel->getActiveSheet()->freezePane('A2');

                // Increment for next category
                $sheetID = $objPHPExcel->getActiveSheetIndex() + 1;
                $objPHPExcel->createSheet($sheetID);
                $objPHPExcel->setActiveSheetIndex( $sheetID );


            }
        }

        // Reset the active sheet back to the first one
        $objPHPExcel->setActiveSheetIndex(0);

        // Create the file
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $filename = $USER->id . '-' . \bcdb_make_file_name($this->name) . '.xlsx';
        $file = $CFG->dataroot . '/BCDB/built_reports/' . $this->id . '/' . $filename;
        $objWriter->save( $file );
        $this->savedFilePath = 'built_reports/' . $this->id . '/' . $filename;

        return $objWriter;

    }






    private function convertDataToRows($startingCatID){

        $return = array();
        $data = $this->data;

        // Starting at a category
        // Get its courses
        if ($data)
        {

            $cat = \core_course_category::get($startingCatID);
            if ($cat)
            {

                // Category totals row
                $return[] = array('type' => 'cat', 'nm_value' => $cat->name) + $this->filterElementsOnly($data['totals']);

                foreach($data['courses'] as $cID => $course)
                {

                    $courseObj = \get_course($cID);
                    if ($courseObj)
                    {

                        // Course totals row
                        $return[] = array('type' => 'course', 'nm_value' => $courseObj->fullname) + $this->filterElementsOnly($course['totals']);

                        // User rows
                        if ($course['users'])
                        {
                            foreach($course['users'] as $uID => $user)
                            {
                                $return[] = array('type' => 'user', 'nm_value' => "{$user['firstname']} {$user['lastname']} ({$user['username']})") + $this->filterElementsOnly($user);
                            }
                        }

                    }

                }

            }

        }

        return $return;

    }

    private function filterElementsOnly($array){

        $return = array();

        foreach($this->elements as $el)
        {
            if (array_key_exists($el->getAliasName(), $array))
            {
                $return[$el->getAliasName()] = $array[$el->getAliasName()];
            }
        }

        return $return;

    }

    public function hasErrors(){

        // Check for errors in the loaded form data

        // Name must be filled out
        if ($this->name == ''){
            $this->errors[] = get_string('error:report:name', 'block_bc_dashboard');
        }

        // Check all the elements to make sure the options have been filled out
        if ($this->elements){

            foreach($this->elements as $element){

                // Check each option to see if it's filled out with a valid value
                if ($element->getOptions()){

                    foreach($element->getOptions() as $num => $option){

                        $possibleValues = $option[2];
                        $value = $element->getParam($num);

                        // If value is missing
                        if ($value === '' || $value === false){
                            $this->errors[] = sprintf( get_string('error:builderreport:elementoption', 'block_bc_dashboard'), $element->getDisplayName(), $num + 1 );
                        }

                    }

                }

            }

        }

        return ($this->errors);

    }

    public function loadFormData($data) {

        global $bcdb;

        // Report info
        if (isset($data['report_id']) && $data['report_id'] > 0){
            $this->id = $data['report_id'];
        }

        $this->name = trim($data['report_name']);
        $this->description = trim($data['report_desc']);

        // Options
        $this->startingPoint = $data['report_options']['startingpoint'];

        if (isset($data['report_options']['cat']) && ctype_digit($data['report_options']['cat']) && has_capability('block/bc_dashboard:assign_report_categories', $bcdb['context'])){
            $this->category = $data['report_options']['cat'];
            unset($data['report_options']['cat']);
        }

        // Elements
        $params = array();

        if (isset($data['elements']) && $data['elements'])
        {
            foreach($data['elements'] as $num => $element)
            {

                $obj = \BCDB\Report\Element::load($element['id']);
                if ($obj)
                {

                    // Display name for the column
                    $obj->setDisplayName($element['displayname']);

                    // Options
                    if (isset($element['options']) && $element['options'])
                    {
                        foreach($element['options'] as $oNum => $option)
                        {
                            $obj->setParam($oNum, $option);
                        }
                    }

                    // Refresh the options for when we go back to the page again
                    $obj->refreshOptions();

                    // Add the element to the object so it can be displayed back in the form
                    $this->addElement($obj);

                    // Then add to a params array to be stored in the DB minimally
                    $params[$num] = array(
                        'id' => $element['id'],
                        'displayname' => $element['displayname'],
                        'params' => @$element['options']
                    );

                }

            }
        }

        // Parameters
        $this->params = $params;

        // Filters
        $filters = array();
        if (isset($data['filters']) && $data['filters'])
        {
            foreach($data['filters'] as $type => $typeFilters)
            {
                foreach($typeFilters as $filter)
                {
                    $filters[$type][] = $filter;
                }
            }
        }

        $this->filters = $filters;

        // Options
        if ($data['report_options']){
            foreach($data['report_options'] as $name => $val){
                $this->options[$name] = $val;
            }
        }



    }


    /**
     * Check if current user has permissions to delete this report
     * Need to:
     * - Be able to view it
     * - Have CRUD builder report permissions
     * - Either own this report or have the delete_any_built_report permission
     * @global \BCDB\Report\type $USER
     * @global type $bcdb
     * @return type
     */
    public function canDelete(){

        global $USER, $bcdb;
        return ( $this->canView() && has_capability('block/bc_dashboard:crud_built_report', $bcdb['context']) && ( $this->createdby == $USER->id || has_capability('block/bc_dashboard:delete_any_built_report', $bcdb['context']) ) );

    }


}
