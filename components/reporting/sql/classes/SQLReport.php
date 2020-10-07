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
namespace block_bc_dashboard\Report;

defined('MOODLE_INTERNAL') or die();

/**
 * Description of SQLReport
 *
 * @author cwarwicker
 */
class SQLReport extends \block_bc_dashboard\Report {

    const REGEX_PARAMS = "/(?!\B\"|'[^\"|']*)\?(?![^\"|']*\"|'\B)/";
    const REPORT_TYPES = array("standard", "chart/bar", "chart/line", "chart/area");
    const DISALLOWED_WORDS = array('ALTER', 'CALL', 'COMMIT', 'CREATE', 'DELETE', 'DROP', 'GRANT', 'INSERT', 'INTO', 'LOCK', 'MERGE', 'RENAME',
        'REVOKE', 'ROLLBACK', 'SAVEPOINT', 'SET', 'TRANSACTION', 'TRUNCATE', 'UPDATE');

    protected $type = 'sql';

    public static function getParamsInQuery($sql) {

        preg_match_all(self::REGEX_PARAMS, $sql, $matches, PREG_OFFSET_CAPTURE);
        return $matches[0];

    }

    public function fields() {
        return self::getSQLFields($this->query);
    }

    /**
     * Get the selected fields from an SQL query string
     * @param type $sql
     * @return type
     */
    public static function getSQLFields($sql) {

        try {

            $parser = new \PHPSQLParser($sql, true);
            $fields = array();

            if (isset($parser->parsed['SELECT']) && $parser->parsed['SELECT']) {

                foreach ($parser->parsed['SELECT'] as $select) {

                    // Functions must use an alias or they will be ignored
                    if ($select['expr_type'] == 'function' || $select['expr_type'] == 'aggregate_function') {
                        if ($select['alias']) {
                            $fields[] = $select['alias']['no_quotes'];
                        }
                    } else if ($select['alias']) {
                        $fields[] = $select['alias']['no_quotes'];
                    } else if (isset($select['no_quotes'])) {
                        $fields[] = (strpos($select['no_quotes'], '.') !== false) ? explode('.', $select['no_quotes'])[1] : $select['no_quotes'];
                    } else {
                        $fields[] = (strpos($select['base_expr'], '.') !== false) ? explode('.', $select['base_expr'])[1] : $select['base_expr'];
                    }
                }
            }

            return $fields;

        } catch (\Exception $ex) {
            return array('error' => $ex->getMessage());
        }

    }

    public function isGraphical() {
        return (in_array($this->getOption('type'), array('chart/bar', 'chart/line', 'chart/area')));
    }

    public function loadFormData() {

        global $bcdb;

        $data = array(
            'report_id' => optional_param('report_id', false, PARAM_INT),
            'report_name' => optional_param('report_name', false, PARAM_TEXT),
            'report_desc' => optional_param('report_desc', false, PARAM_TEXT),
            'report_options' => df_optional_param_array_recursive('report_options', false, PARAM_TEXT),
            'report_params' => df_optional_param_array_recursive('report_params', array(), PARAM_TEXT),
            'report_query' => optional_param('report_query', false, PARAM_RAW),
            'report_privacy' => optional_param('report_privacy', false, PARAM_TEXT),
        );

        if (isset($data['report_id']) && $data['report_id'] > 0) {
            $this->id = $data['report_id'];
        }

        $this->name = trim($data['report_name']);
        $this->description = trim($data['report_desc']);
        $this->options = $data['report_options'];
        $this->params = (isset($data['report_params'])) ? $data['report_params'] : null;
        $this->query = trim($data['report_query']);

        if (isset($data['report_privacy']) && ctype_digit($data['report_privacy']) && has_capability('block/bc_dashboard:assign_report_categories', $bcdb['context'])) {
            $this->category = $data['report_privacy'];
        }

        // If not graphical, remove axies from options
        if (!$this->isGraphical()) {
            if (isset($this->options['xaxis'])) {
                unset($this->options['xaxis']);
            }
            if (isset($this->options['yaxis'])) {
                unset($this->options['yaxis']);
            }
        }

    }

    public function hasErrors() {

        // Check for errors in the loaded form data
        require_sesskey();

        // Name must be filled out
        if ($this->name == '') {
            $this->errors[] = get_string('error:report:name', 'block_bc_dashboard');
        }

        // Query must be filled out
        if ($this->query == '') {
            $this->errors[] = get_string('error:report:query', 'block_bc_dashboard');
        }

        // Parameter count must match number of parameters sent in form, with their types and defaults
        $pCnt = count( self::getParamsInQuery($this->query) );
        if ($pCnt <> count($this->params)) {
            $this->errors[] = sprintf( get_string('error:report:sqlparams', 'block_bc_dashboard'), $pCnt, count($this->params) );
        }

        // Make sure we are only doing SELECT queries.
        if( preg_match('/\b('.implode('|', self::DISALLOWED_WORDS).')\b/i', $this->query) ){
            $this->errors[] = get_string('error:report:disallowedwords', 'block_bc_dashboard') . implode(', ', self::DISALLOWED_WORDS);
        }

        return ($this->errors);

    }


    public function applyParams($params) {

        foreach ($this->params as $key => $param) {
            $val = (isset($params[$key])) ? $params[$key] : $param->default;
            $param->value = $val;
        }

    }

    /**
     * Alias of calling export('csv')
     * @return type
     */
    public function runExportExcel() {
        return $this->export();
    }


    public function export($method = 'csv') {

        global $CFG, $USER;

        require_once($CFG->dirroot . '/lib/filelib.php');

        // Try and create a directory to store the saved files in
        if (!\bcdb_create_data_dir("sql_reports")) {
            echo json_encode( array('errors' => array( get_string('error:createdir', 'block_bc_dashboard') ) ) );
            exit;
        }

        // Try and create a directory to store the saved files in
        if (!\bcdb_create_data_dir("sql_reports/{$this->id}")) {
            echo json_encode( array('errors' => array( get_string('error:createdir', 'block_bc_dashboard') ) ) );
            exit;
        }

        $filename = $USER->id . '-' . \bcdb_make_file_name($this->name);

        switch ($method) {

            case 'csv':
            default:

                // Create a CSV file in moodledata
                $filename .= '.csv';
                $file = @fopen( $CFG->dataroot . '/BCDB/sql_reports/' . $this->id . '/' . $filename, 'w+' );
                if (!$file) {
                    echo json_encode( array('errors' => array( get_string('error:createfile', 'block_bc_dashboard') ) ) );
                    exit;
                }

                // Header row
                $headers = $this->getHeaders();
                if (!$headers) {
                    echo json_encode( array('data' => '') );
                    exit;
                }

                fputcsv($file, $headers);

                // Data
                if ($this->data) {
                    foreach ($this->data as $row) {
                        fputcsv($file, (array)$row);
                    }
                }

                // Close file
                fclose($file);

                // Create download link
                $path = 'sql_reports/' . $this->id . '/' . $filename;
                $code = \bcdb_create_download_code($path);

                // Save file path variable for use in automated reports
                $this->savedFilePath = $path;

                // Send file
                \block_bc_dashboard\Log::add(\block_bc_dashboard\Log::LOG_EXPORT_REPORT, $this->id);
                echo json_encode( array('download' => $code) );

                break;

        }

    }

    /**
     * Alias of "run" method
     * @return type
     */
    public function execute() {
        return $this->run();
    }

    public function run() {

        // Set parameter values into an array
        $sqlParams = array();
        if ($this->params) {
            foreach ($this->params as $param) {

                // Date
                if ($param->type == 'date') {
                    $date = \DateTime::createFromFormat('d-m-Y H:i:s', $param->value . ' 00:00:00');
                    $param->value = $date->format("U");
                } else if ($param->type == 'datetime') {
                    $date = \DateTime::createFromFormat('d-m-Y H:i:s', $param->value . ':00');
                    $param->value = $date->format("U");
                }

                $sqlParams[] = $param->value;

            }
        }

        // We are not going to do any error checking, because it shoudln't have saved if there were any errors anyway

        // Datasource
        $source = $this->getOption('source');

        switch ($source) {

            case 'moodle':
            default:

                global $DB;

                $this->data = array();
                $rowNum = 1;

                $result = $DB->get_records_sql($this->query, $sqlParams, 0, (int)$this->getOption('limit'));
                if ($result) {
                    foreach ($result as $row) {
                        $this->data[$rowNum] = $row;
                        $rowNum++;
                    }
                }

                return $this->data;

                break;

        }

    }

    /**
     * Check if current user has permissions to edit this report
     * @global type $USER
     * @return type
     */
    public function canEdit() {

        global $USER, $bcdb;
        return ( $this->canView() && has_capability('block/bc_dashboard:crud_sql_report', $bcdb['context']) && ( $this->createdby == $USER->id || has_capability('block/bc_dashboard:edit_any_sql_report', $bcdb['context']) ) );

    }

    /**
     * Check if current user has permissions to delete this report
     * Need to:
     * - Be able to view it
     * - Have CRUD SQL report permissions
     * - Either own this report or have the delete_any_sql_report permission
     * @global \block_bc_dashboard\Report\type $USER
     * @global type $bcdb
     * @return type
     */
    public function canDelete() {

        global $USER, $bcdb;
        return ( $this->canView() && has_capability('block/bc_dashboard:crud_sql_report', $bcdb['context']) && ( $this->createdby == $USER->id || has_capability('block/bc_dashboard:delete_any_sql_report', $bcdb['context']) ) );

    }

}
