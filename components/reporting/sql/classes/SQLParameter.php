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

namespace BCDB;

class SQLParameter {
    
    const DATE_FORMAT = 'd-m-Y';
    
    private $num, $type, $default, $options;
    
    public function __construct($num, $name, $type, $default = null, $options = array()) {
        $this->num = $num;
        $this->name = $name;
        $this->type = $type;
        $this->default = $default;
        $this->options = $options;        
    }
    
    public function forceElementName($name){
        $this->elementName = $name;
        return $this;
    }
    
    public function forceElementID($id){
        $this->elementID = $id;
        return $this;
    }
  
    public function getElementValueName($num){
        
        global $bcdb;
        
        if (!isset($this->elementValue[$num])){
            return false;
        }
        
        if ($this->type == 'course_picker'){
            
            $course = \get_course($this->elementValue[$num]);
            return ($course) ? addslashes($course->fullname) : false;
            
        } elseif ($this->type == 'user_picker'){
            
            $user = \bcdb_get_user_name($this->elementValue[$num]);
            return ($user) ? addslashes($user) : false;
            
        } elseif ($this->type == 'block_gradetracker/qual_picker' && $bcdb['gradetracker']){
            
            $qual = new \GT\Qualification();
            return ($qual->isValid()) ? $qual->getDisplayName() : false;
            
        }
        
        return false;
        
    }
    
    public function getElementTypeAttribute(){
        
        if ($this->type == 'course_picker'){
            return 'course';
        } elseif ($this->type == 'user_picker'){
            return 'user';
        } elseif ($this->type == 'block_gradetracker/qual_picker'){
            return 'qual';
        }
        
        return '';
        
    }
    
    public function toFormElement(){
        
        $output = "";
        
        $class = "sql_param";
        $id = (isset($this->elementID)) ? "param_element_{$this->elementID}_{$this->num}" : "param_element_{$this->num}";
        $name = (isset($this->elementName)) ? "{$this->elementName}[{$this->num}]" : "param[{$this->num}]";
        $val = (isset($this->default) && $this->default != '') ? \bcdb_html($this->default) : '' ;
        
        $options = ($this->options) ? str_getcsv($this->options) : false;
        
        switch($this->type)
        {
            
            case 'select':
                $output .= "<select id='{$id}' name='{$name}' class='{$class} form-control' param='{$this->name}'>";
                    $output .= "<option value=''></option>";
                    if ($options)
                    {
                        foreach($options as $option)
                        {
                            $option = trim($option);
                            $sel = ($option == $val) ? 'selected' : '';
                            $output .= "<option value='{$option}' {$sel} >{$option}</option>";
                        }
                    }
                $output .= "</select>";
            break;
            
            case 'date':
                $output .= "<input id='{$id}' name='{$name}' value='{$val}' class='{$class} datepicker form-control' param='{$this->name}' />";
            break;
        
            case 'datetime':
                $output .= "<input id='{$id}' name='{$name}' value='{$val}' class='{$class} datetimepicker form-control' param='{$this->name}' />";
            break;
        
            case 'category_picker':
                $output .= "<select id='{$id}' name='{$name}' class='{$class} form-control' param='{$this->name}'>";
                    $output .= "<option value=''>".get_string('choosecoursecat', 'block_bc_dashboard')."</option>";
                    $cats = \core_course_category::make_categories_list();
                    foreach($cats as $catID => $catName)
                    {
                        $output .= "<option value='{$catID}'>{$catName}</option>";
                    }
                $output .= "</select>";
            break;
            
            case 'course_picker':
                $output .= "<input type='text' id='{$id}_search' class='{$class} coursepicker form-control' useID='{$id}' placeholder='".get_string('choosecourse', 'block_bc_dashboard')."' param='{$this->name}' />";
                $output .= "<input type='hidden' id='{$id}' name='{$name}' value='{$val}' class='form-control' />";
            break;
        
            case 'user_picker':
                $output .= "<input type='text' id='{$id}_search' class='{$class} userpicker form-control' useID='{$id}' placeholder='".get_string('chooseuser', 'block_bc_dashboard')."' param='{$this->name}' />";
                $output .= "<input type='hidden' id='{$id}' name='{$name}' value='{$val}' class='form-control' />";
            break;
        
            case 'block_gradetracker/qual_picker':
                $output .= "<input type='text' id='{$id}_search' class='{$class} block_gradetracker_qual_picker form-control' useID='{$id}'  placeholder='".get_string('choosequal', 'block_gradetracker')."' param='{$this->name}' />";
                $output .= "<input type='hidden' id='{$id}' name='{$name}' value='{$val}' class='form-control' />";
            break;
            
            case 'text':
            default:
                
                $output .= "<input type='text' id='{$id}' name='{$name}' value='{$val}' class='{$class} form-control' param='{$this->name}' />";
                
            break;
            
        }
        
        return $output;
        
    }
    
    public static function getAvailableFormats(){
        
        // Default formats always available
        $formats = array('text', 'select', 'date', 'datetime', 'category_picker', 'course_picker', 'user_picker');
        
        // Is the gradetracker block installed?
        $block = block_instance('gradetracker');
        if ($block){
            $formats[] = 'block_gradetracker/qual_picker';            
        }
        
        
        return $formats;
        
    }
        
    public static function load($params){
        return new \BCDB\SQLParameter($params->num, $params->name, $params->type, (isset($params->default)) ? $params->default : null, (isset($params->options)) ? $params->options : array());
    }
    
}
