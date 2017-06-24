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
        $this->set("allCourseCats", \coursecat::make_categories_list());
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
