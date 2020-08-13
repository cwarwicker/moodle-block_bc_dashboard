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

namespace block_bc_dashboard\Controllers;

defined('MOODLE_INTERNAL') or die();

/**
 * Description of DashboardController
 *
 * @author cwarwicker
 */
class IndexController extends \block_bc_dashboard\Controller {

    protected $component = 'index';

    public function main() {

        // Check permissions to view reporting
        if (!$this->hasCapability('block/bc_dashboard:view_bc_dashboard')) {
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }

    }

    public function action_view($args = false) {

        global $CFG, $USER, $bcdb;

        $submission = array(
            'mass_action' => optional_param('mass_action', false, PARAM_TEXT),
            'submit_assign' => optional_param('submit_assign', false, PARAM_TEXT),
        );

        $settings = array(
            'students' => df_optional_param_array_recursive('students', false, PARAM_INT),
            'findstudent' => df_optional_param_array_recursive('findstudent', false, PARAM_TEXT),
        );

        $type = (isset($args[0])) ? $args[0] : 'all';
        $id = (isset($args[1])) ? $args[1] : false;

        // If we're trying to look at the list of students on a specific course, they either need:
        // Capability on that course
        // Capability on frontpage and assigned to that course as a teacher
        if ($type == 'course' && $id) {

            if (!$this->hasCapability('block/bc_dashboard:view_bc_dashboard', $id)) {
                \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
            }

        } else {

            // Otherwise, we just want to see if they have this permission on any of their contexts
            if (!$this->hasCapability('block/bc_dashboard:view_bc_dashboard')) {
                \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
            }

        }

        // Mass Actions
        if ($submission['mass_action'] && $settings['students']) {
            $this->runMassActions($type);
        } else if ($submission['submit_assign']) {

            // Add students

            if ($type == 'mentees') {
                $OBJ = new \block_elbp\PersonalTutor();
            } else if ($type == 'additionalsupport') {
                $OBJ = new \block_elbp\ASL();
            } else {
                return false;
            }

            $OBJ->loadTutorID($USER->id);

            // Loop through students
            $OBJ->setAssignBy("username");
            $OBJ->assignIndividualMentees($settings['findstudent']);

            $this->view->set("messages", array('general' => $OBJ->getOutputMsg()));

        }

    }

    public function runMassActions($type = null) {

        global $CFG, $USER, $bcdb;

        $submission = array(
            'confirmed' => optional_param('confirmed', false, PARAM_TEXT),
        );

        $settings = array(
            'students' => df_optional_param_array_recursive('students', false, PARAM_INT),
            'mass_action' => optional_param('mass_action', false, PARAM_TEXT),
            'subject' => optional_param('subject', false, PARAM_TEXT),
            'message' => optional_param('message', false, PARAM_TEXT),
        );

        // Sort students into an array first
        $usersArray = array();

        foreach ($settings['students'] as $id) {
            $user = \bcdb_get_user($id);
            if ($user) {
                $usersArray[$user->id] = $user;
            }
        }

        \bcdb_sort_users($usersArray);

        // Send a message
        if ($settings['mass_action'] == 'message') {

            // Confirmed
            if ($submission['confirmed'] && !empty($settings['message'])) {

                require_sesskey();

                $Alert = new \block_elbp\EmailAlert();
                $subject = (!empty($settings['subject'])) ? $settings['subject'] : get_string('nosubject', 'block_bc_dashboard');
                $content = $settings['message'];
                $htmlContent = nl2br($content);

                $successMsg = array();
                $errMsg = array();

                foreach ($usersArray as $user) {

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

            } else {

                // Not confirmed
                $output = "";
                $hidden = "";

                foreach ($usersArray as $user) {
                    $output .= bcdb_get_user_name($user->id) . ", ";
                    $hidden .= "<input type='hidden' name='students[]' value='{$user->id}' />";
                }

                $output = substr($output, 0, -2);
                $output .= "<form action='' method='post'>{$hidden}";
                $output .= "<input type='hidden' name='sesskey' value='".sesskey()."' />";
                $output .= "<br><input type='text' name='subject' placeholder='".get_string('subject', 'block_bc_dashboard')."' /><br>";
                $output .= "<br><textarea name='message' style='width:80%;height:200px;'></textarea><br><br>";
                $output .= "<button class='btn btn-primary' type='submit' name='confirmed' value='1'>".get_string('sendmessage', 'block_bc_dashboard')."</button>";
                $output .= "<input type='hidden' name='mass_action' value='message' /> &nbsp;&nbsp; ";
                $output .= "<a href='' class='btn btn-danger'>".get_string('cancel')."</a></form>";

                bcdb_confirmation_page( get_string('messagestudents', 'block_bc_dashboard') , $output);

            }

        } else if ($settings['mass_action'] == 'remove' && $settings['students']) {

            // Remove student

            // Confirmed
            if ($submission['confirmed']) {

                require_sesskey();

                $successMsg = array();
                $errorMsg = array();

                if ($type == 'mentees') {

                    $PT = new \block_elbp\PersonalTutor();
                    $PT->loadTutorID($USER->id);

                } else if ($type == 'additionalsupport') {

                    $PT = new \block_elbp\ASL();
                    $PT->loadTutorID($USER->id);

                }

                foreach ($usersArray as $user) {

                    if ($PT->removeMentee($user->id)) {
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

            } else {

                // Not confirmed

                $output = "";
                $hidden = "";

                $output .= get_string('removestudents:sure', 'block_bc_dashboard') . "<br><br>";

                foreach ($usersArray as $user) {
                    $output .= bcdb_get_user_name($user->id) . ", ";
                    $hidden .= "<input type='hidden' name='students[]' value='{$user->id}' />";
                }

                $output = substr($output, 0, -2);
                $output .= "<form action='' method='post'>{$hidden}<br>";
                $output .= "<button class='btn btn-primary' type='submit' name='confirmed' value='1'>".get_string('confirm')."</button>";
                $output .= "<input type='hidden' name='mass_action' value='remove' /> &nbsp;&nbsp; ";
                $output .= "<a href='' class='btn btn-danger'>".get_string('cancel')."</a></form>";

                bcdb_confirmation_page( get_string('removestudents', 'block_bc_dashboard') , $output);

            }

        } else {

            // Else, see if its a ELBP plugin mass action

            if (strpos($settings['mass_action'], ":") && $bcdb['elbp'] == true) {

                $explode = explode(":", $settings['mass_action']);
                $pluginID = $explode[0];
                $action = $explode[1];

                if (is_numeric($pluginID) && !empty($action)) {

                    // Get plugin
                    $ELBP = new \block_elbp\ELBP();
                    $plugin = $ELBP->getPluginByID($pluginID);
                    if ($plugin) {
                        $result = $plugin->massAction($action, $usersArray);
                        if ($result && $result['result']) {
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


    public function action_admin() {

        global $bcdb;

        $settings = array(
            'students' => df_optional_param_array_recursive('students', false, PARAM_INT),
            'mass_action' => optional_param('mass_action', false, PARAM_TEXT),
        );

        // ELBP
        if (!$bcdb['elbp']) {
            exit;
        }

        $ELBP = new \block_elbp\ELBP();

        // permissions
        $access = $ELBP->getCoursePermissions(SITEID);
        if (!$access['god'] && !$access['elbpadmin']) {
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }

        // Mass Actions
        if ($settings['mass_action'] && $settings['students']) {
            $this->runMassActions();
        }

    }



}
