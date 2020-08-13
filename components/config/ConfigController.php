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
class ConfigController extends \block_bc_dashboard\Controller {

    protected $component = 'config';

    public function main() {

        $submission = array(
            'submit_config' => optional_param('submit_config', false, PARAM_TEXT),
        );

        if (!has_capability('block/bc_dashboard:config', $this->context)) {
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }

        $messages = array();

        // Form submitted and they have the capability to do it?
        if ($submission['submit_config']) {

            require_sesskey();

            $Config = new \block_bc_dashboard\Config();
            $Config->loadPostData();

            if (!$Config->hasErrors()) {
                if ($Config->save()) {
                    $messages['success'] = get_string('configsaved', 'block_bc_dashboard');;
                } else {
                    $messages['errors'] = get_string('error:unknownsaveerror', 'block_bc_dashboard');
                }
            } else {
                $messages['errors'] = $Config->getErrors();
            }

        }

        $this->view->set("messages", $messages);

    }

}
