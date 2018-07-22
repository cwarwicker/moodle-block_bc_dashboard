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

/**
 * Description of DashboardController
 *
 * @author cwarwicker
 */
class IndexController extends \BCDB\Controller {
    
    protected $component = 'index';
    
    public function main(){
        
        // Check permissions to view reporting
        if (!$this->hasCapability('block/bc_dashboard:view_bc_dashboard')){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
    }
    
    public function action_view($args = false){
        
        global $CFG, $USER, $bcdb;

        $type = (isset($args[0])) ? $args[0] : 'all';
        $id = (isset($args[1])) ? $args[1] : false;
                        
        
        // If we're trying to look at the list of students on a specific course, they either need:
        // Capability on that course
        // Capability on frontpage and assigned to that course as a teacher
        if ($type == 'course' && $id){
            
            if (!$this->hasCapability('block/bc_dashboard:view_bc_dashboard', $id)){
                \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
            }
            
        } else {
            
            // Otherwise, we just want to see if they have this permission on any of their contexts
            if (!$this->hasCapability('block/bc_dashboard:view_bc_dashboard')){
                \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
            }
                        
        }
        
        
            
                            
        // Mass Actions
        if (isset($_POST['mass_action']) && isset($_POST['students']))
        {
            $this->runMassActions($type);            
        }
        
        
        // Add students
        elseif (isset($_POST['submit_assign']))
        {
            
            if ($type == 'mentees'){
                $OBJ = new \ELBP\PersonalTutor();
            } elseif ($type == 'additionalsupport'){
                $OBJ = new \ELBP\ASL();
            } else {
                return false;
            }
            
            $OBJ->loadTutorID($USER->id);

            // Loop through students
            $OBJ->setAssignBy("username");
            $OBJ->assignIndividualMentees($_POST['findstudent']);

            $this->view->set("messages", array('general' => $OBJ->getOutputMsg()));
                        
        }
        
        
    }
    
    public function runMassActions($type = null){
        
        global $CFG, $USER, $bcdb;

        // Sort students into an array first
        $usersArray = array();
        
        foreach($_POST['students'] as $id){
            $user = \bcdb_get_user($id);
            if ($user){
                $usersArray[$user->id] = $user;
            }
        }
        
        \bcdb_sort_users($usersArray);
        
        
        // Send a message
        if ($_POST['mass_action'] == 'message')
        {

            // Confirmed
            if (isset($_POST['confirmed']) && !empty($_POST['message']))
            {

                $Alert = new \ELBP\EmailAlert();
                $subject = (!empty($_POST['subject'])) ? $_POST['subject'] : get_string('nosubject', 'block_bc_dashboard');
                $content = $_POST['message'];
                $htmlContent = nl2br($content);

                $successMsg = array();
                $errMsg = array();

                foreach($usersArray as $user){

                    $usersArray[$user->id] = $user;

                    if ($Alert->send($user, $subject, $content, $htmlContent, null, $USER) ) {
                        $successMsg[] = '<i class="fa fa-check-square"></i> ' . get_string('messagesentto', 'block_bc_dashboard') . ": " . fullname($user) . " ({$user->username})";
                    } else {
                        $errMsg[] = '<i class="fa fa-cross-square"></i> ' . get_string('failedsendmessageto', 'block_bc_dashboard') . ": " . fullname($user) . " ({$user->username})";
                    }

                }

                $messages = array(
                    'success' => implode("<br>", $successMsg),
                    'errors' => implode("<br>", $errMsg)
                );

                $this->view->set("messages", $messages);
                $this->view->set("students", $usersArray);

            }

            // Not confirmed
            else
            {

                $output = "";
                $hidden = "";

                foreach($usersArray as $user){
                    $output .= bcdb_get_user_name($user->id) . ", ";
                    $hidden .= "<input type='hidden' name='students[]' value='{$user->id}' />";
                }

                $output = substr($output, 0, -2);
                $output .= "<form action='' method='post'>{$hidden}";
                $output .= "<br><input type='text' name='subject' placeholder='".get_string('subject', 'block_bc_dashboard')."' /><br>";
                $output .= "<br><textarea name='message' style='width:80%;height:200px;'></textarea><br><br>";
                $output .= "<button class='btn btn-primary' type='submit' name='confirmed'>".get_string('sendmessage', 'block_bc_dashboard')."</button>";
                $output .= "<input type='hidden' name='mass_action' value='message' /> &nbsp;&nbsp; ";
                $output .= "<a href='' class='btn btn-danger'>".get_string('cancel')."</a></form>";

                bcdb_confirmation_page( get_string('messagestudents', 'block_bc_dashboard') , $output);

            }

        }

        // Remove student
        elseif ($_POST['mass_action'] == 'remove' && isset($_POST['students']))
        {

            // Confirmed
            if (isset($_POST['confirmed']))
            {

                $successMsg = array();
                $errorMsg = array();

                // Mentees
                if ($type == 'mentees')
                {

                    $PT = new \ELBP\PersonalTutor();
                    $PT->loadTutorID($USER->id);

                }
                // Addiiotnal SUpport tutor
                elseif ($type == 'additionalsupport')
                {

                    $PT = new \ELBP\ASL();
                    $PT->loadTutorID($USER->id);

                }


                foreach($usersArray as $user){

                    if ($PT->removeMentee($user->id)){
                        $successMsg[] = '<i class="fa fa-check-square"></i> ' . get_string('removedstudent', 'block_bc_dashboard') . ": " . bcdb_get_user_name($user->id, false);
                    } else {
                        $errorMsg[] = '<i class="fa fa-check-square"></i> ' . get_string('removestudent:error', 'block_bc_dashboard') . ": " . bcdb_get_user_name($user->id, false) . " - {$PT->getOutputMsg()}";
                    }

                }

                $messages = array(
                    'success' => implode("<br>", $successMsg),
                    'errors' => implode("<br>", $errorMsg)
                );

                $this->view->set("messages", $messages);

            }
            // Not confirmed
            else
            {

                $output = "";
                $hidden = "";

                $output .= get_string('removestudents:sure', 'block_bc_dashboard') . "<br><br>";

                foreach($usersArray as $user){
                    $output .= bcdb_get_user_name($user->id) . ", ";
                    $hidden .= "<input type='hidden' name='students[]' value='{$user->id}' />";
                }

                $output = substr($output, 0, -2);
                $output .= "<form action='' method='post'>{$hidden}<br>";
                $output .= "<button class='btn btn-primary' type='submit' name='confirmed'>".get_string('confirm')."</button>";
                $output .= "<input type='hidden' name='mass_action' value='remove' /> &nbsp;&nbsp; ";
                $output .= "<a href='' class='btn btn-danger'>".get_string('cancel')."</a></form>";

                bcdb_confirmation_page( get_string('removestudents', 'block_bc_dashboard') , $output);

            }

        }


        // Else, see if its a ELBP plugin mass action
        else
        {

            if (strpos($_POST['mass_action'], ":") && $bcdb['elbp'] == true)
            {

                $explode = explode(":", $_POST['mass_action']);
                $pluginID = $explode[0];
                $action = $explode[1];

                if (is_numeric($pluginID) && !empty($action))
                {

                    // Get plugin
                    $ELBP = new \ELBP\ELBP();
                    $plugin = $ELBP->getPluginByID($pluginID);
                    if ($plugin)
                    {
                        $result = $plugin->massAction($action, $usersArray);
                        if ($result && $result['result'])
                        {
                            $this->view->set("messages", array(
                                'success' => $result['success']
                            ));
                            $this->view->set("students", $result['students']);
                        }
                    }

                }

            }

        }
        
    }
    
    
    public function action_admin(){
        
        global $bcdb;
        
        // ELBP
        if (!$bcdb['elbp']){
            exit;
        }
        
        $ELBP = new \ELBP\ELBP();
        
        // permissions
        $access = $ELBP->getCoursePermissions(SITEID);
        if (!$access['god'] && !$access['elbpadmin']){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
        // Mass Actions
        if (isset($_POST['mass_action']) && isset($_POST['students']))
        {
            $this->runMassActions();            
        }
        
    }
    
    
    
}
