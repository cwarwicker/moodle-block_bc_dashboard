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

class block_bc_dashboard  extends block_base
{ 
    
    private $string;
    
    public function init()
    {
        global $CFG;
        $this->string = get_string_manager()->load_component_strings('block_bc_dashboard', $CFG->lang, true);
        $this->title = $this->string['dashboard'];  
        $this->imgdir = $CFG->wwwroot . '/blocks/bc_dashboard/pix/';
    }
    
    public function get_content()
    {
        
        global $COURSE, $CFG, $bcdb;
        
        require_once $CFG->dirroot . '/blocks/bc_dashboard/lib.php';
        
        if (!isset($bcdb['context'])){
            $bcdb['context'] = context_course::instance($COURSE->id);
        }
        
        $this->content = new stdClass();

        if (!has_capability('block/bc_dashboard:view_reports', $bcdb['context'])){

            return $this->content;
        }
        
        $this->content->text = "<ul class='bcdb_list_none'>";
        $this->content->text .= "<li><img src='{$this->imgdir}report.png' style='width:16px;' /> <a href='{$CFG->wwwroot}/blocks/bc_dashboard/reporting'>".get_string('reports')."</a></li>";
        $this->content->text .= "</ul>";
        
        return $this->content;

    }
    
    /**
     * Run the cron
     */
    public function cron(){
        
        
    }
    
}
