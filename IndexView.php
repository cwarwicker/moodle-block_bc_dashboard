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

class IndexView extends \BCDB\View {
    
    public function __construct() {
        
        global $bcdb;
        
        parent::__construct();
        
        if ($bcdb['elbp'])
        {
            $this->set("ELBP", new \ELBP\ELBP());
        }
        
    }
    
    public function main(){
                
        global $bcdb;
        
        // Page title
        $this->set("pageTitle", get_string('dashboard', 'block_bc_dashboard'));
                
        // Sub Navigation links
        $nav = array();               
        $this->set("subNavigation", $nav);
        
        // Breadcrumbs
        $this->addBreadcrumb( array('title' => get_string('dashboard', 'block_bc_dashboard')) );
        
        // Side navigation
        $this->set("sideNavigation", self::buildSideNav());
        
        // Recent activity
        if ($bcdb['elbp']){
            $this->set("recentActivity", \ELBP\Log::parseListOfLogs($this->getRecentActivity()));
        }
        
    }
    
    public function action_view($args){
                
        global $CFG;
        
        $type = isset($args[0]) ? clean_param($args[0], PARAM_ALPHA) : 'all';
                
        // Page title
        $this->set("pageTitle", get_string('dashboard', 'block_bc_dashboard'));
                
        // Sub Navigation links
        $nav = array();               
        $this->set("subNavigation", $nav);
        
        // Breadcrumbs
        $this->addBreadcrumb( array('title' => get_string('viewstudents:'.$type, 'block_bc_dashboard')) );
        if ($type == 'course')
        {
            $courseID = @$args[1];
            $course = get_course($courseID);
            if ($course)
            {
                $this->addBreadcrumb( array('title' => $course->fullname, 'url' => $CFG->wwwroot . '/course/view.php?id='.$course->id) );
            }
        }
        
        $this->set("type", $type);
        
        // Side navigation
        $this->set("sideNavigation", self::buildSideNav());
        
                       
        // Now get the list of students
        $students = $this->getStudentList($args);        
        $this->set("students", $students);
        
        // Get the mass actions that can be applied
        $this->set("massActions", $this->getMassActions());
        
    }
    
    public function action_admin($args){
        
        // Page title
        $this->set("pageTitle", get_string('viewstudents:admin', 'block_bc_dashboard'));
        
        // Sub Navigation
        $this->set("subNavigation", array());
        
        // Breadcrumbs
        $this->addBreadcrumb( array('title' => get_string('viewstudents:admin', 'block_bc_dashboard')) );
        
        // Side navigation
        $this->set("sideNavigation", self::buildSideNav());
        
        // Pickers
        $userPicker = new \BCDB\SQLParameter('u', 'user', 'user_picker');
        $coursePicker = new \BCDB\SQLParameter('c', 'course', 'course_picker');
        $this->set("userPicker", $userPicker)->set("coursePicker", $coursePicker);
        
        // Get the mass actions that can be applied
        $this->set("massActions", $this->getMassActions());
        
    }
    
    /**
     * Get recent activity on your students
     * @global type $DB
     * @global \BCDB\Views\type $USER
     * @param type $limit
     * @return type
     */
    public function getRecentActivity($limit = 25)
    {
        
        global $DB, $USER;
        
        $ELBP = new \ELBP\ELBP();
        $ELBP->getUserPermissions($USER->id);
        
        // If elbp admin, show for all students, not just our own, also double the limit
        if (\elbp_has_capability('block/elbp:elbp_admin', $ELBP->getAccess())){
            $limit = $limit * 2;
            $recent = $DB->get_records_sql("SELECT * FROM {lbp_logs} WHERE module = 'ELBP' AND element != 'SETTINGS' ORDER BY time DESC", null, 0, $limit);
            return $recent;
        }
                
        $studentIDs = array();
        $students = $this->getAllStudents();
        if ($students)
        {
            foreach($students as $student)
            {
                $studentIDs[] = (int)$student->id;
            }
        }
        
        if ($studentIDs){
            $recent = $DB->get_records_sql("SELECT * FROM {lbp_logs} WHERE studentid IN (".implode(',', $studentIDs).") AND module = 'ELBP' AND element != 'SETTINGS' ORDER BY time DESC", null, 0, $limit);
            return $recent;
        }
        
        return array();
        
    }
    
    
    /**
     * Get the headers for attendance
     * @return type
     */
    public function getStudentHeaders_Attendance(){
                    
        $headers = "";
        $ELBP = new \ELBP\ELBP();
        $att = $ELBP->getPlugin("Attendance");

        if ($att)
        {
            $types = $att->getTypes();
            if ($types)
            {
                foreach($types as $type)
                {
                    $headers .= "<th>{$type}</th>";
                }
            }
            
        }
                    
        return $headers;
        
    }
    
    /**
     * Get the comments header
     * @return string
     */
    public function getStudentHeaders_Comments(){
        
        $headers = "";
        $ELBP = new \ELBP\ELBP();
        $plugin = $ELBP->getPlugin("Comments");
        if ($plugin)
        {
            $headers .= "<th>".get_string('unresolvedcomments', 'block_elbp')."</th>";                        
        }
                    
        return $headers;
        
    }
    
    /**
     * Get the tutorials header
     * @return string
     */
    public function getStudentHeaders_Tutorials(){
        
        $headers = "";
        
        $ELBP = new \ELBP\ELBP();
        $plugin = $ELBP->getPlugin("Tutorials");
        if ($plugin)
        {
            $headers .= "<th>".get_string('tutorials', 'block_elbp')."</th>";                        
        }
        
        return $headers;
        
    }
    
    /**
     * Get the target grade header
     * @return string
     */
    public function getStudentHeaders_TargetGrade(){
        
        $headers = "";
        $headers .= "<th>".get_string('targetgrade', 'block_gradetracker')."</th>";
        return $headers;
        
    }
    
    /**
     * Get the asp grade header
     * @return string
     */
    public function getStudentHeaders_AspirationalGrade(){
        
        $headers = "";
        $headers .= "<th>".get_string('aspirationalgrade', 'block_gradetracker')."</th>"; 
        return $headers;
        
    }
    
    /**
     * Get the student's cell for Comments
     * @param type $studentID
     * @return type
     */
    public function getStudentCells_Comments($studentID){
        
        $return = '';
        $plugin = $this->vars['ELBP']->getPlugin("Comments");
        
        if ($plugin)
        {
            
            $plugin->loadStudent($studentID);

            $userComments = $plugin->getUserComments();

            $total = count($userComments);
            $unresolved = 0;

            if ($userComments)
            {
                foreach($userComments as $cmt)
                {
                    if (!$cmt->isResolved())
                    {
                        $unresolved++;
                    }
                }
            }

            $return = "<td>{$unresolved} / {$total}</td>";
        
        }
        
        
        return $return;
                
    }
    
    /**
     * Get the student's cell for Tutorials
     * @param type $studentID
     * @return type
     */
    public function getStudentCells_Tutorials($studentID){
        
        $total = '';
        
        $plugin = $this->vars['ELBP']->getPlugin("Tutorials");
        if ($plugin)
        {
            $plugin->loadStudent($studentID);
            $tutorials = $plugin->getUserTutorials();
            $total = count($tutorials);
        }
                    
        return "<td>{$total}</td>";
                
    }
    
    /**
     * Get the cells for attendance
     * @return type
     */
    public function getStudentCells_Attendance($studentID){
                    
        $cells = "";
        $att = $this->vars['ELBP']->getPlugin("Attendance");

        if ($att)
        {
            $att->loadStudent($studentID);
            $types = $att->getTypes();
            if ($types)
            {
                foreach($types as $type)
                {
                    $period = $att->getSetting("student_summary_display_".$type);
                    $cells .= "<td>".$att->getRecord( array("type" => $type, "period" => $period) )."</td>";
                }
            }
            
        }
                    
        return $cells;
        
    }
    
    /**
     * Get student cell for target grade
     * @param type $studentID
     * @return type
     */
    public function getStudentCells_TargetGrade($studentID){
        
        $grades = array();
        
        $user = new \GT\User($studentID);
        $userGrades = $user->getAllUserGrades('target');
        
        if ($userGrades)
        {
            foreach($userGrades as $grade)
            {
                $grades[] = $grade['grade']->getName();
            }
        }
        
        return "<td>".implode(', ', $grades)."</td>";
        
    }
    
    /**
     * Get student cell for target grade
     * @param type $studentID
     * @return type
     */
    public function getStudentCells_AspirationalGrade($studentID){
        
        $grades = array();
        
        $user = new \GT\User($studentID);
        $userGrades = $user->getAllUserGrades('aspirational');
        
        if ($userGrades)
        {
            foreach($userGrades as $grade)
            {
                $grades[] = $grade['grade']->getName();
            }
        }
        
        return "<td>".implode(', ', $grades)."</td>";
        
    }
    
    /**
     * Get the list of students 
     * @param type $args
     * @return type
     */
    private function getStudentList($args){
        
        global $bcdb;
        
        $return = array();
        
        $type = (isset($args[0])) ? $args[0] : 'all';
        
        switch($type)
        {
            
            case 'course':
                $courseID = @$args[1];
                $return = $this->getAllStudentsOnCourse($courseID);
            break;
        
            case 'mentees':
                $return = $this->getAllMentees();
            break;
            
            case 'additionalsupport':
                $return = $this->getAllAdditionalSupport();
            break;
        
            case 'all':
                $return = $this->getAllStudents();
            break;
        
        }
                
        return $return;
        
    }
    
    public static function countAllStudents(){
        $obj = new \BCDB\Views\IndexView(true);
        return count($obj->getAllStudents());
    }
    
    public static function countAllMentees(){
        $obj = new \BCDB\Views\IndexView(true);
        return count($obj->getAllMentees());
    }
    
    public static function countAllAdditionalSupport(){
        $obj = new \BCDB\Views\IndexView(true);
        return count($obj->getAllAdditionalSupport());
    }
    
     public static function countCourseStudents($courseID){
        $obj = new \BCDB\Views\IndexView(true);
        return count($obj->getAllStudentsOnCourse($courseID));
    }
    
    /**
     * Get all the tutor's students
     * @return type
     */
    private function getAllStudents(){
        
        $students = array();
        
        // First get mentees
        $students = $students + $this->getAllMentees();
        
        // Then additional support
        $students = $students + $this->getAllAdditionalSupport();
        
        // Then all course students
        $courses = \bcdb_get_user_courses();
        if ($courses)
        {
            foreach($courses as $course)
            {
                $students = $students + $this->getAllStudentsOnCourse($course->id);
            }
        }
        
        $this->sortUsers($students);        
        
        return $students;
        
    }
    
    
    /**
     * Get all students assigned to the tutor as Additional SUpport
     * @global type $bcdb
     * @global type $USER
     * @return boolean
     */
    private function getAllAdditionalSupport(){
        
        global $bcdb, $USER;
        
        if (!$bcdb['elbp']) return array();
        
        $DBC = new \ELBP\DB();
        
        $students = $DBC->getStudentsOnAsl($USER->id);
        $this->sortUsers($students);        
        
        return $students;
        
    }
    
    /**
     * Get all students assigned to the tutor as mentees
     * @global \BCDB\Views\type $bcdb
     * @global \BCDB\Views\type $USER
     * @return boolean
     */
    private function getAllMentees(){
        
        global $bcdb, $USER;
        
        if (!$bcdb['elbp']) return array();
        
        $DBC = new \ELBP\DB();
        
        $students = $DBC->getMenteesOnTutor($USER->id);
        $this->sortUsers($students);        
        
        return $students;
        
    }
    
    /**
     * Get all students on a given course
     * @param type $courseID
     * @return boolean
     */
    private function getAllStudentsOnCourse($courseID){
        
        if (!$courseID) return array();
        
        $students = bcdb_get_users_on_course($courseID, array('student'));
        $this->sortUsers($students);        
        
        return $students;
        
    }
    
    /**
     * Sort an array of user objects
     * @param type $students
     */
    private function sortUsers(&$students){
       \bcdb_sort_users($students);
    }
              
    
    /**
     * Get mass actions that can be applied to a list of students
     * @param type $actions
     */
    public function getMassActions(){
        
        global $bcdb;
        
        $actions = array();
        
        if ($bcdb['elbp'])
        {
        
            $ELBP = new \ELBP\ELBP();
            $plugins = $ELBP->getPlugins();

            if ($plugins)
            {
                foreach($plugins as $plugin)
                {

                    $pluginActions = $plugin->getMassActions();
                    if ($pluginActions)
                    {
                        foreach($pluginActions as $action => $title)
                        {
                            $title = $plugin->getTitle() . ": " . $title;
                            $action = $plugin->getID() . ":" . $action;
                            $actions[$action] = $title;
                        }
                    }

                }
            }
        
        }
        
        return $actions;
        
    }
    
    
    /**
     * Build the array for the side navigation, so we can call this from other view files
     * @global \BCDB\Views\type $CFG
     * @return string
     */
    public static function buildSideNav(){
                
        global $CFG, $bcdb;
        
        // Side Nav
        $sideNav = array();
        
        // Get courses
        $courses = \bcdb_get_user_courses();
        $courseArray = array();
        if ($courses)
        {
            foreach($courses as $course)
            {
                $courseArray[] = array('title' => $course->fullname. ' ('.self::countCourseStudents($course->id).')', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/view/course/' . $course->id, 'class' => 'report');
            }
        }
                
        $sideNav[] = array( 'title' => get_string('allstudents', 'block_bc_dashboard') . ' ('.self::countAllStudents().')', 'icon' => 'fa-users', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/view/all' );
        
        // If the PLP is installed
        if ($bcdb['elbp'] == true)
        {
            $sideNav[] = array( 'title' => get_string('mentees', 'block_bc_dashboard'). ' ('.self::countAllMentees().')', 'icon' => 'fa-user-circle-o',  'url' => $CFG->wwwroot . '/blocks/bc_dashboard/view/mentees' );
            $sideNav[] = array( 'title' => get_string('additionalsupport', 'block_bc_dashboard'). ' ('.self::countAllAdditionalSupport().')', 'icon' => 'fa-user-circle', 'url' => $CFG->wwwroot . '/blocks/bc_dashboard/view/additionalsupport' );
        }
        
        $sideNav[] = array( 'title' => get_string('courses', 'block_bc_dashboard'), 'icon' => 'fa-book', 'children' => $courseArray );

        return $sideNav;
        
    }
    
}
