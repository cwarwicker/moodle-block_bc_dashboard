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

require_once('../../config.php');
require_login();

require_once('lib.php');

$action = optional_param('action', false, PARAM_TEXT);
if (!$action) {
    exit;
}

$PAGE->set_context( $bcdb['context'] );

// Permissions to access any of it
if (!bcdb_has_capability('block/bc_dashboard:view_bc_dashboard')) {
    exit;
}

switch ($action) {

    case 'search_course':

        $settings = array(
            'term' => optional_param('term', false, PARAM_TEXT),
        );

        $results = array();
        $search = trim($settings['term']);
        $params = array();

        $params[] = "%{$search}%";
        $params[] = "%{$search}%";

        // If we search with a hash at the beginning, we can also search for a course id, e.g. #4
        if (substr($search, 0, 1) == '#') {
            $or = "OR id = ?";
            $params[] = substr($search, 1);
        } else {
            $or = "";
        }

        $records = $DB->get_records_select("course",
            "shortname LIKE ? OR fullname LIKE ? {$or}",
            $params,
            "fullname, shortname",
            "id, shortname, fullname");

        if ($records) {
            foreach ($records as $record) {
                $obj = new stdClass();
                $obj->id = $record->id;
                $obj->label = $record->fullname;
                $obj->value = $record->fullname;
                $results[] = $obj;
            }
        }

        echo json_encode($results);
        exit;


        break;


    case 'search_user':

        $settings = array(
            'term' => optional_param('term', false, PARAM_TEXT),
        );

        $results = array();

        $search = trim($settings['term']);

        $params = array();
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";

        // If we search with a hash at the beginning, we can also search for a user id, e.g. #4
        if (substr($search, 0, 1) == '#') {
            $or = "OR id = ?";
            $params[] = substr($search, 1);
        } else {
            $or = "";
        }

        $records = $DB->get_records_select("user", " (username LIKE ? OR {$DB->sql_concat_join("' '", array('firstname', 'lastname'))} LIKE ? {$or}) AND deleted = 0", $params, "lastname, firstname, id");

        if ($records) {
            foreach ($records as $record) {
                $obj = new stdClass();
                $obj->id = $record->id;
                $obj->label = \fullname($record) . " ({$record->username})";
                $obj->value = \fullname($record) . " ({$record->username})";
                $results[] = $obj;
            }
        }

        echo json_encode($results);
        exit;

        break;


    case 'scan_elements':

        require_once($CFG->dirroot . '/blocks/bc_dashboard/components/reporting/builder/classes/Element.php');

        // Scan for new
        \block_bc_dashboard\Report\Element::scan();

        // Return ones in DB
        $elements = \block_bc_dashboard\Report\Element::retrieve(false);

        echo json_encode( $elements );
        exit;

        break;


    case 'add_report_element':

        require_once($CFG->dirroot . '/blocks/bc_dashboard/components/reporting/builder/classes/Element.php');

        $settings = array(
            'id' => optional_param('id', false, PARAM_INT),
        );

        $element = \block_bc_dashboard\Report\Element::load( $settings['id'] );
        if (!$element) {
            exit;
        }

        $return = array();
        $return['id'] = $settings['id'];
        $return['name'] = $element->getStringName();
        $return['options'] = $element->getOptions();

        echo json_encode($return);
        exit;


        break;


    case 'refresh_element_options':

        $settings = array(
            'id' => optional_param('id', false, PARAM_INT),
            'param' => optional_param('param', false, PARAM_TEXT),
            'val' => optional_param('val', false, PARAM_TEXT),
        );

        $element = \block_bc_dashboard\Report\Element::load( $settings['id'] );
        if (!$element) {
            exit;
        }

        $paramNum = $settings['param'];
        $value = $settings['val'];

        $element->setParam($paramNum, $value);

        echo json_encode($element->refreshOptions());
        exit;

        break;

    case 'load_student_link':

        $settings = array(
            'id' => optional_param('id', false, PARAM_INT),
        );

        // ELBP
        if (!$bcdb['elbp']) {
            exit;
        }

        $ELBP = new \block_elbp\ELBP();

        // permissions
        $access = $ELBP->getCoursePermissions(SITEID);
        if (!$access['god'] && !$access['elbpadmin']) {
            exit;
        }

        $students = array();
        if (isset($settings['id'])) {
            $record = $DB->get_record("user", array("id" => $settings['id']));
            if ($record) {
                $students[] = $record;
            }
        }

        require_once($CFG->dirroot . '/blocks/bc_dashboard/IndexView.php');
        $View = new \block_bc_dashboard\Views\IndexView();
        $View->set("students", $students)->set("massActions", $View->getMassActions());
        $View->force( $CFG->dirroot . '/blocks/bc_dashboard/tpl/view.table.html' );
        $View->renderForcedPageOnly();
        exit;


        break;

    case 'load_course_links':

        $settings = array(
            'id' => optional_param('id', false, PARAM_INT),
        );

        // ELBP
        if (!$bcdb['elbp']) {
            exit;
        }

        $ELBP = new \block_elbp\ELBP();

        // permissions
        $access = $ELBP->getCoursePermissions(SITEID);
        if (!$access['god'] && !$access['elbpadmin']) {
            exit;
        }

        $students = array();

        if (isset($settings['id'])) {
            $students = bcdb_get_users_on_course($settings['id'], array('student'));
            \bcdb_sort_users($students);
        }

        require_once($CFG->dirroot . '/blocks/bc_dashboard/IndexView.php');
        $View = new \block_bc_dashboard\Views\IndexView();
        $View->set("students", $students)->set("massActions", $View->getMassActions());
        $View->force( $CFG->dirroot . '/blocks/bc_dashboard/tpl/view.table.html' );
        $View->renderForcedPageOnly();
        exit;

        break;


}
