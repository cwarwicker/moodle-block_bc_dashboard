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

namespace BCDB\Views;

require_once 'classes/Config.php';

/**
 * Description of ConfigView
 *
 * @author cwarwicker
 */
class ConfigView extends \BCDB\View {
    
    public function main(){
        
        global $CFG;
        
        // Page title & links
        $this->set("pageTitle", get_string('configuration', 'block_bc_dashboard'));
        $this->addBreadcrumb( array('title' => get_string('configuration', 'block_bc_dashboard')) );
        $this->set("subNavigation", array( array( 'title' => get_string('save', 'block_bc_dashboard'), 'icon' => 'save', 'url' => '#', 'form' => 'config_form') ));

        
        // Variables
        $config = new \BCDB\Config();
        $flatArray = $config->getFlatReportCategories();
        $this->set("reportCats", $this->buildCategoryTreeHTML($config->getReportCategories(false, false), $flatArray));
        $this->set("courseCats", explode(",", \BCDB\Setting::getSetting("course_cats")));
        $this->set("allCourseCats", \core_course_category::make_categories_list());
        $this->set("allElements", \BCDB\Report\Element::retrieve(false));
            
    }
    
    
    protected function buildCategoryTreeHTML($categories, $flatArray, &$return = false){
        
        // Set default strings
        if (!$return){
            $return = array(
                'items' => '',
                'inputs' => ''
            );
        }
                
        $return['items'] .= "<ul>";
                
        if ($categories)
        {
            foreach($categories as $cat)
            {
                
                $class = implode(" ", $this->buildParentClassAttribute($cat, $flatArray));
                
                $return['inputs'] .= "<input type='hidden' id='rid_{$cat->id}' class='{$class}' name='report_cat[{$cat->id}][id]' value='{$cat->id}' />";
                $return['inputs'] .= "<input type='hidden' id='rnm_{$cat->id}' class='{$class}' name='report_cat[{$cat->id}][name]' value='".\bcdb_html($cat->name)."' />";
                if (isset($cat->parent) && !is_null($cat->parent)){
                    $return['inputs'] .= "<input type='hidden' id='rpar_{$cat->id}' class='{$class}' name='report_cat[{$cat->id}][parent]' value='{$cat->parent}' />";
                }
                
                $return['items'] .= "<li reportid='{$cat->id}'>";
                
                    $return['items'] .= \bcdb_html($cat->name);
                    
                    if (isset($cat->children) && $cat->children)
                    {
                        $this->buildCategoryTreeHTML($cat->children, $flatArray, $return);
                    }
                    
                $return['items'] .= "</li>";
            }
        }
                
        $return['items'] .= "</ul>";
        
        return $return;
        
    }
    
    private function buildParentClassAttribute($cat, $flatArray, &$classes = array()){
                
        // Parent?
        if (isset($cat->parent) && !is_null($cat->parent)){

            $classes[] = $cat->parent;
            
            // Now check parent
            $parent = $flatArray[$cat->parent];
            $this->buildParentClassAttribute($parent, $flatArray, $classes);
            
        }
        
        return $classes;
        
    }
    
}
