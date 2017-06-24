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

namespace BCDB;

require_once $CFG->dirroot . '/lib/coursecatlib.php';

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
                    $cats = \coursecat::make_categories_list();
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
