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

defined('MOODLE_INTERNAL') or die();

// Default Routing & configuration
$bcdb = array();
$bcdb['routes'] = array(
    '/view\/admin/' => 'admin'
);
$bcdb['context'] = \context_course::instance(SITEID);
$bcdb['elbp'] = false;
$bcdb['gradetracker'] = false;


// Load all classes
require_once($CFG->dirroot . '/blocks/bc_dashboard/classes/Log.php');
require_once($CFG->dirroot . '/blocks/bc_dashboard/classes/Setting.php');
require_once($CFG->dirroot . '/blocks/bc_dashboard/classes/Controller.php');
require_once($CFG->dirroot . '/blocks/bc_dashboard/classes/View.php');


require_once($CFG->dirroot . '/blocks/bc_dashboard/IndexController.php');

require_once($CFG->dirroot . '/blocks/bc_dashboard/components/config/classes/Config.php');
require_once($CFG->dirroot . '/blocks/bc_dashboard/components/reporting/classes/Report.php');
require_once($CFG->dirroot . '/blocks/bc_dashboard/components/reporting/classes/ScheduledTask.php');

require_once($CFG->dirroot . '/blocks/bc_dashboard/components/reporting/builder/classes/BuiltReport.php');
require_once($CFG->dirroot . '/blocks/bc_dashboard/components/reporting/builder/classes/Element.php');

require_once($CFG->dirroot . '/blocks/bc_dashboard/components/reporting/sql/classes/SQLReport.php');
require_once($CFG->dirroot . '/blocks/bc_dashboard/components/reporting/sql/classes/SQLParameter.php');



// If the ELBP is installed, load that
if (block_instance('elbp')) {
    $bcdb['elbp'] = true;
    require_once($CFG->dirroot . '/blocks/elbp/lib.php');
}

// if the GT is installed, load that
if (block_instance('gradetracker')) {
    $bcdb['gradetracker'] = true;
    require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');
}

/**
 * Flush out any output and exit the script
 */
function bcdb_stop() {
    flush();
    exit;
}

/**
 * Call the routing hook to work out where we are going, based on the nice url
 * @param string $url The nice url from the query string
 */
function bcdb_callHook($url = null) {

    global $CFG;

    /**
     *
     * Examples:
     *
     * / - IndexController - main()
     * /settings - SettingsController - main()
     * /dashboard - DashboardController - main()
     * /dashboard/students - DashboardController - action_students()
     * /dashboard/students/mentees/ - DashboardController - action_students('mentees')
     * /dashboard/students/course/14 - DashboardController - action_students('course', 14)
     * /reporting - ReportingController - main()
     * /reporting/schedule - ReportingController - action_schedule()
     * /reporting/sql/index - SQLController (child of Reporting Controller) - main()
     * /reporting/sql/new - SQLController - action_new()
     * /reporting/sql/edit/6 - SQLController - action_edit(6)
     * /reporting/sql/run/6 - SQLController - action_run(6)
     * /reporting/sql/export/6 - SQLController - action_export(6)
     * /reporting/builder - BuilderController - main()
     * /reporting/builder/new - BuilderController - action_new()
     * /reporting/builder/edit/2 - BuilderController - action_edit(2)
     * /config - ConfigController - main()
     *
     * e.g. /reporting/sql/edit/6
     *
     * - Look for /reporting directory with ReportingController - OK
     * - Look /reporting/sql directory with SQLController - OK
     * - Look on SQLController for method 'edit' - OK
     * - Pass in any extra values as parameters
     *
     * e.g. /reporting/schedule
     *
     * - Look for /reporting directory with ReportingController - OK
     * - Look for/reporting/schedule directory with ScheduleController - NO
     * - Ok, look on ReportingController for action_schedule() - OK
     *
     */

    // Setup vars
    $Control = false;
    $controller = false;
    $previous = array();

    // If URL not defined, use the defaults from the config
    if (is_null($url) || empty($url)) {
        $Control = new \block_bc_dashboard\Controllers\IndexController();
    } else {

        // Otherwise route the URL and find out what plugin class & method we are trying to use
        $url = bcdb_routeUrl($url);

        $tmpArray = explode("/", $url);
        $urlArray = array();
        foreach ($tmpArray as $tmp) {
            if (!empty($tmp)) {
                $urlArray[] = $tmp;
            }
        }

        $cnt = count($urlArray);

        // Reuse tmpArray in the loop below
        $tmpArray = array();

        // Loop through the url elements and look for the correct controller and action to use
        for ($i = 0; $i < $cnt; $i++) {

            $el = $urlArray[$i];
            $implodePath = ($previous) ? implode('/', $previous) . '/' : '';
            $implodeClass = ($previous) ? implode('/', $previous) . '\\' : '';

            $Info = array();
            $Info['controller_path'] = $CFG->dirroot . '/blocks/bc_dashboard/components/' . $implodePath . $el . '/' . ucfirst($el) . 'Controller.php';
            $Info['controller_class'] = "\\block_bc_dashboard\\Controllers\\" . $implodeClass . ucfirst($el) . 'Controller';
            $Info['method'] = (($i + 1) < $cnt) ? 'action_'.$urlArray[$i + 1] : null;

            // Does this controller exist, with this method?
            if (file_exists($Info['controller_path'])) {

                // Require the file to see if it contains the class and method we are looking for
                require_once($Info['controller_path']);

                // Does the class exist and the method?
                if (class_exists($Info['controller_class'])) {

                    $controller = new $Info['controller_class'](true);

                    // Does the method exist on this controller?
                    if (method_exists($controller, $Info['method'])) {

                        $Control = $controller;
                        $Control->setAction($Info['method']);

                        // Get the rest of the url, as this must be the parameters
                        $params = array_slice($urlArray, ($i + 2));
                        $Control->setParams($params);

                        break;

                    }

                }

            }

            $previous[] = $el;

        }

        // If that didn't find anything, look for a method on the index
        if (!$Control) {

            $index = new \block_bc_dashboard\Controllers\IndexController(true);
            $method = 'action_'.$urlArray[0];
            if (method_exists($index, $method)) {
                $Control = $index;
                $Control->setAction($method);
                $params = array_slice($urlArray, 1);
                $Control->setParams($params);
            }

        }

        // If Control still not set, send a 404 error
        if (!$Control) {

            // If we found a Controller, but not a method, default to main() method on that controller
            if ($controller) {
                $Control = $controller;
            } else {
                $Control = new \block_bc_dashboard\Controllers\IndexController();
                $Control->setAction('404');
            }
        }

    }

    // Unset the tmp variable to allow us to use this controller
    $Control->tmp = false;

    $ajax = optional_param('ajax', false, PARAM_TEXT);
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $ajax) {
        $Control->noRender();
    }

}

/**
 * If there this url is an alias, use the proper url that it refers to
 * @param type $url
 */
function bcdb_routeUrl($url) {

    global $bcdb;

    if ($bcdb['routes']) {
        foreach ($bcdb['routes'] as $pattern => $route) {
            if ( preg_match($pattern, $url) ) {
                return preg_replace($pattern, $route, $url);
            }
        }
    }

    return $url;

}


function bcdb_errors($errors, $id = '') {

    $output = '';

    if ($errors) {

        $output .= "<div id='{$id}' class='col-lg-12 alert alert-danger'>";
        $output .= "<strong>".get_string('error')."</strong><br>";
        $output .= "<ul>";

        if (!is_array($errors)) {
            $errors = array($errors);
        }

        foreach ($errors as $error) {
            $output .= "<li>{$error}</li>";
        }
        $output .= "</ul>";

        $output .= "</div>";

    }

    return $output;

}



function bcdb_success($successes) {

    $output = '';

    if ($successes) {

        $output .= "<div class='col-lg-12 alert alert-success'>";

        if (!is_array($successes)) {
            $successes = array($successes);
        }

        foreach ($successes as $suc) {
            $output .= "<span>{$suc}</span><br>";
        }

        $output .= "</div>";

    }

    return $output;

}

function bcdb_has_capability($capability, $courseID = false) {

    global $bcdb;

    if (!$bcdb['elbp']) {
        exit;
    }

    $ELBP = new \block_elbp\ELBP();

    // If we're passed a course through, we want to check if they have the capability on that course OR that they have it frontpage and are enrolled on that course
    if ($courseID) {

        $context = \context_course::instance($courseID);

        // First check context
        if (\has_capability($capability, $context)) {
            return true;
        }

        $access = $ELBP->getCoursePermissions($courseID);

        // Now check if they have the permission on the frontpage and are enrolled on this course
        if (\has_capability($capability, $bcdb['context']) && $access['teacher']) {
            return true;
        }

    } else {

        // First check front page
        if (\has_capability($capability, $bcdb['context'])) {
            return true;
        }

        // Then try looping through their courses and see if they have the capability on any of them
        $courses = \bcdb_get_user_courses();
        if ($courses) {
            foreach ($courses as $course) {
                if (\has_capability($capability, \context_course::instance($course->id))) {
                    return true;
                }
            }
        }

    }

    return false;

}


function bcdb_html($txt) {
    return htmlentities($txt, ENT_QUOTES);
}


/**
 *
 * @global \block_elbp\type $DB
 * @param type $courseID
 * @return type
 */
function bcdb_get_child_courses($courseID) {

    global $DB;

    $sql = "SELECT DISTINCT c.*
            FROM {course} c
            INNER JOIN {enrol} e ON c.id = e.customint1
            WHERE e.enrol = 'meta' AND e.courseid = ?
            ORDER BY c.fullname ASC";

    return $DB->get_records_sql($sql, array($courseID));

}


function bcdb_get_gradetracker_quals() {

    $GTEXE = \block_gradetracker\Execution::getInstance();
    $GTEXE->min();

    $quals = \block_gradetracker\Qualification::getAllQualifications(true);
    $listOfQuals = array();
    if ($quals) {
        foreach ($quals as $qual) {
            $name = gt_html_undo($qual->getDisplayName());

            $obj = new \stdClass();
            $obj->id = $qual->getID();
            $obj->label = $name;
            $obj->value = $name;
            $listOfQuals[] = $obj;
        }
    }

    return $listOfQuals;

}


/**
 * Print something to a test file. Useful for debugging AJAX requests.
 * @global type $CFG
 * @param type $value
 */
function bcdb_pn($value, $wipe = false) {
    global $CFG;

    $mode = ($wipe) ? 'w' : 'a';

    $file = fopen($CFG->dirroot . '/blocks/bc_dashboard/tmp.txt', $mode);
    if ($file) {
        fwrite($file, print_r($value, true));
        fwrite($file, "\n");
        fclose($file);
    }
}



/**
 * Create a data directory
 * @global type $CFG
 * @param type $dir
 * @return boolean
 */
function bcdb_create_data_dir($dir) {

    global $CFG;

    // Check for BCDB directory
    if (!is_dir( $CFG->dataroot . '/BCDB' )) {
        if (is_writeable($CFG->dataroot)) {
            if (!mkdir($CFG->dataroot . '/BCDB', $CFG->directorypermissions, true)) {
                return false;
            }
        } else {
            return false;
        }
    }

    // Now try and make the actual dir we want
    if (!is_dir( $CFG->dataroot . '/BCDB/' . $dir )) {
        if (is_writeable($CFG->dataroot . '/BCDB/')) {
            if (!mkdir($CFG->dataroot . '/BCDB/' . $dir, $CFG->directorypermissions, true)) {
                return false;
            }
        } else {
            return false;
        }
    }

    // If we got this far must be ok
    return true;

}


function bcdb_create_download_code($path) {

    global $DB;

    $record = $DB->get_record("block_bcdb_download_codes", array('path' => $path));
    if ($record) {
        return $record->code;
    }

    $code = \bcdb_rand_str(15);
    $searching = true;
    while ($searching) {
        $record = $DB->get_record("block_bcdb_download_codes", array("code" => $code));
        if (!$record) {
            $ins = new stdClass();
            $ins->code = $code;
            $ins->path = $path;
            $DB->insert_record("block_bcdb_download_codes", $ins);
            return $code;
        }
    }

}

function bcdb_get_download_path($code) {

    global $DB;

    $record = $DB->get_record("block_bcdb_download_codes", array('code' => $code));
    return ($record) ? $record->path : false;

}

function bcdb_rand_str($length) {
    return substr(md5(rand()), 0, $length);
}

function bcdb_make_file_name($name) {

    $name = preg_replace("/ /", "_", $name);
    $name = preg_replace("/[^a-z 0-9_-]/i", "", $name);
    return $name;

}


function bcdb_create_side_nav($sideNavigation, &$output = "", $pID = '') {

    // Top level
    if ($pID === '') {
        $output .= "<ul class='nav navbar-nav side-nav'>";
    } else {
        $output .= "<ul id='sidenav-{$pID}' class='collapse'>";
    }

    if ( $sideNavigation) {
        foreach ($sideNavigation as $key => $sideNav) {

            $url = (isset($sideNav['url'])) ? $sideNav['url'] : '#';
            $class = (isset($sideNav['class'])) ? $sideNav['class'] : '';
            $explode = (isset($sideNav['icon'])) ? explode('|', $sideNav['icon']) : array();
            $icons = array(
                (isset($explode[0])) ? $explode[0] : '',
                (isset($explode[1])) ? $explode[1] : ''
            );

            $parent = ($pID === '') ? $key : $pID . '-' . $key;
            $dataAttr = (isset($sideNav['children'])) ? 'data-toggle="collapse" data-target="#sidenav-'.$parent.'"' : '';

            $levels = count(explode("-", $parent));
            $indent = '';
            for ($i = 1; $i < $levels; $i++) {
                $indent .= '&nbsp;&nbsp;';
            }

            $icon = (isset($icons[0]) && strlen($icons[0])) ? "<i class='fa fa-fw {$icons[0]}'></i>" : "";

            $output .= "<li>";
            $output .= "<a href='{$url}' class='{$class}' onclick='toggleNavIcon(\"{$icons[0]}\", \"{$icons[1]}\", this);' {$dataAttr} >{$indent}{$icon} {$sideNav['title']}</a>";
            if (isset($sideNav['children'])) {
                \bcdb_create_side_nav($sideNav['children'], $output, $parent);
            }
            $output .= "</li>";
        }
    }

    $output .= "</ul>";

    return $output;

}



/**
 * Create a string of question mark placeholders for SQL queries from an array of parameters
 * Mostly used for when binding to an IN(?,?,?,?,?,etc...)
 * @param type $params
 * @return type
 */
function bcdb_create_sql_placeholders($params) {

    return implode(',', array_fill(0, count($params), '?'));

}

/**
 * Display a value, depending on its type
 * @param type $val
 * @return type
 */
function bcdb_val($val) {

    $type = gettype($val);

    if ($type == 'array') {
        return implode(", ", $val);
    } else {
        return (string)$val;
    }

}

function bcdb_get_course($courseID) {

    global $DB;
    return $DB->get_record("course", array("id" => $courseID));

}

function bcdb_to_array($arrayOfObjects) {

    $arrayOfObjects = (array)$arrayOfObjects;
    if ($arrayOfObjects) {
        foreach ($arrayOfObjects as &$object) {
            if (gettype($object) == 'object') {
                $object = (array)$object;
            }
        }
    }

    return $arrayOfObjects;

}

function bcdb_decrement_letter($l) {
    return chr(ord($l) - 1);
}

function bcdb_get_user_courses($userID = false) {

    global $USER, $bcdb;

    if (!$userID) {
        $userID = $USER->id;
    }

    $courses = false;

    // If ELBP is installed, use that function
    if ($bcdb['elbp']) {
        $DBC = new \block_elbp\DB();
        $courses = $DBC->getTeachersCourses($userID);
    } else if ($bcdb['gradetracker']) {
        // otherwise, if GT is installed, use that
        $user = new \block_gradetracker\User($userID);
        $courses = $user->getCourses("STAFF");
    }

    return $courses;

}

/**
 * Get a list of users on a course
 * @param int $courseID
 * @param array $roles
 */
function bcdb_get_users_on_course($courseID, $roles = false) {

    global $DB;

    $params = array(CONTEXT_COURSE, $courseID);

    $and = '';
    if ($roles) {
        $and = 'AND (';
        foreach ($roles as $role) {
            $and .= 'r.roleid = ? OR ';
            $params[] = \bcdb_get_role($role);
        }
        $and = substr($and, 0, -4);
        $and .= ')';
    }

    $sql = "SELECT
                DISTINCT u.*
            FROM
                {user} u
            INNER JOIN
                {role_assignments} r ON r.userid = u.id
            INNER JOIN
                {context} cx ON cx.id = r.contextid
            INNER JOIN
                {course} c ON c.id = cx.instanceid
            WHERE
                cx.contextlevel = ?
            AND
                c.id = ?

            {$and}

            ORDER BY
                u.lastname ASC, u.firstname ASC, u.username ASC";

    $users = $DB->get_records_sql($sql, $params);
    return $users;

}

/**
 * Return the id of a role by its shortname
 * @param type $shortname
 */
function bcdb_get_role($shortname) {
    global $DB;
    $record = $DB->get_record("role", array("shortname" => $shortname));
    return ($record) ? $record->id : false;
}

/**
 * Get a user's fullname and username, from their ID
 * @global \block_elbp\type $DB
 * @param type $id
 * @param type $username
 * @return type
 */
function bcdb_get_user_name($id, $username = true) {

    global $DB;

    $record = $DB->get_record("user", array("id" => $id));
    return ($record) ? fullname($record) . (($username) ? ' (' . $record->username . ')' : '')
        : false;

}

function bcdb_get_user($id) {
    global $DB;
    return $DB->get_record("user", array("id" => $id));
}

function bcdb_get_user_by_username($username) {
    global $DB;
    return $DB->get_record("user", array("username" => trim($username)));
}

/**
 * Confirmation page
 * @global type $CFG
 * @global \block_elbp\type $DB
 * @param type $title
 * @param type $output
 * @param type $url
 * @param type $timeout
 */
function bcdb_confirmation_page($title, $output, $url = null, $timeout = null, $viewObj = null) {

    global $CFG, $DB;

    require_once($CFG->dirroot . '/blocks/bc_dashboard/IndexView.php');

    ob_end_clean();

    $View = (!is_null($viewObj)) ? $viewObj : new block_bc_dashboard\Views\IndexView();
    $View->setController(new block_bc_dashboard\Controllers\IndexController(true));
    $View->force($CFG->dirroot . '/blocks/bc_dashboard/tpl/confirm.html');
    $View->set("title", $title);
    $View->set("output", $output);
    $View->set("url", $url);
    $View->set("timeout", $timeout);
    $View->render();

    bcdb_stop();

}

function bcdb_sort(&$array, $field) {
    uasort($array, function($a, $b) use ($field) {
        return strnatcasecmp($a->$field, $b->$field);
    });
}

function bcdb_sort_users(&$array) {

    uasort($array, function($a, $b) {
        return ( strcmp( $a->lastname, $b->lastname ) == 0 ) ?
            strcmp( $a->firstname, $b->firstname ) :
            strcmp( $a->lastname, $b->lastname );
    });

}


/**
 * Unrecoverable error - print error box and exit
 * @return string
 */
function bcdb_fatalError($msg) {

    global $CFG, $bcdb;

    ob_end_clean();

    $vars = array(
        "title" => get_string('fatalerror', 'block_bc_dashboard') . '!',
        "msg" => $msg
    );

    // If we're doing an error page
    $qs = optional_param('Qs', false, PARAM_RAW);
    if ($qs && strpos($qs, "error") === 0) {
        $explode = explode("/", $qs);
        $vars['msg'] = $explode[1];
    }

    $output = "";

    if ($CFG->debug >= 32767) {

        $debugtrace = debug_backtrace();
        if ($debugtrace) {
            foreach ($debugtrace as $trace) {
                $file = (isset($trace['file'])) ? $trace['file'] : '?';
                $line = (isset($trace['line'])) ? $trace['line'] : '?';
                $output .= "<div class='notifytiny' style='text-align:center !important;'>{$file}:{$line}</div>";
            }
        }

    }

    $vars['trace'] = $output;

    require_once($CFG->dirroot . '/blocks/bc_dashboard/IndexView.php');
    $View = new \block_bc_dashboard\Views\IndexView();
    $View->set("title", $vars['title'])->set("msg", $vars['msg'])->set("trace", $vars['trace']);
    $View->force( $CFG->dirroot . '/blocks/bc_dashboard/tpl/error.html' );
    $View->renderForcedPageOnly();

    bcdb_stop();

}


function bcdb_get_full_category_name($id, &$names = array()) {

    global $DB;

    $record = $DB->get_record("block_bcdb_report_categories", array("id" => $id));
    if (!$record) {
        return get_string('uncategorised', 'block_bc_dashboard');
    }

    $names[] = $record->name;

    // Look for parent
    if (!is_null($record->parent)) {
        return \bcdb_get_full_category_name($record->parent, $names);
    } else {

        // Must have now reached the end
        // Reverse the array
        $names = array_reverse($names);
        return implode(" / ", $names);

    }

}

/**
 * https://stackoverflow.com/questions/3109978/display-numbers-with-ordinal-suffix-in-php
 * @param type $number
 * @return type
 */
function bcdb_ordinal($number) {
    $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
        return $number. 'th';
    } else {
        return $number. $ends[$number % 10];
    }
}


/**
 * Get the file extension from a file name
 * @param type $filename
 * @return type
 */
function bcdb_get_file_extension($filename) {
    $filename = strtolower($filename);
    $exts = explode(".", $filename);
    $n = count($exts) - 1;
    $ext = $exts[$n];
    return $ext;
}
