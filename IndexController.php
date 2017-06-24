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

/**
 * Description of DashboardController
 *
 * @author cwarwicker
 */
class IndexController extends \BCDB\Controller {
    
    protected $component = 'index';
    
    public function main(){
        
        // Check permissions to view reporting
        if (!has_capability('block/bc_dashboard:view_bc_dashboard', $this->context)){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
    }
    
    public function action_view($args = false){
        
        global $CFG, $USER, $bcdb;
        
        // Check permissions to view reporting
        if (!has_capability('block/bc_dashboard:view_bc_dashboard', $this->context)){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
        $type = (isset($args[0])) ? $args[0] : 'all';
                
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
