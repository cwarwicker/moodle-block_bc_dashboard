<?php
/**
 * Main index page for the dashboard. Everything is routed through here
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

set_time_limit(0);

require_once '../../config.php';
require_once $CFG->dirroot . '/blocks/bc_dashboard/lib.php';
require_login();

$PAGE->set_context($bcdb['context']);

ob_start();

// Nice url
$URL = ( isset($_GET['Qs']) ) ? trim($_GET['Qs']) : '';

// Call router hook - this will work out where we want to go and what we want to do, based on the nice url
bcdb_callHook($URL);

// Flush page output and exit
bcdb_stop();