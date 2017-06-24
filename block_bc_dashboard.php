<?php
/**
 * Block file.
 * 
 * @copyright 2013 Bedford College
 * @package Bedford College Dashboard (BCDB)
 * @version 1.0
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
