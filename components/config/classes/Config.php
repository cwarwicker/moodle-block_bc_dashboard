<?php
namespace BCDB;

/**
 * Description of Config
 *
 * @author cwarwicker
 */
class Config {
    
    private $reportCats = array();
    
    private $errors = array();
    
    public function getErrors(){
        return $this->errors;
    }
    
    public function getReportCategories($reports = false, $includeUncategorised = true){
        return $this->getRecursiveReportCategories($reports, null, $includeUncategorised);
    }
    
    protected function getRecursiveReportCategories($reports = false, $parent = null, $includeUncategorised = true){

        global $DB;
                
        $return = array();
        
        // Get top level categories
        $records = $DB->get_records("block_bcdb_report_categories", array("parent" => $parent));
        if ($records)
        {
            foreach($records as $record)
            {
                
                // Load reports
                if ($reports)
                {
                    $record->reports = \BCDB\Report::pub($record->id);
                }
                
                // Check for child cats
                $record->children = $this->getRecursiveReportCategories($reports, $record->id);
                $return[] = $record;
                
            }
        }
        
        // Then add any uncategorised ones onto the end (probably were in a category which was then deleted)
        if (is_null($parent) && $includeUncategorised){
            $misc = new \stdClass();
            $misc->id = 0;
            $misc->name = 'Uncategorised';
            $misc->reports = \BCDB\Report::pub(0);
            $return[] = $misc;
        }
        
        return $return;

    }
    
    public function getFlatReportCategories(){
        
        global $DB;
        return $DB->get_records("block_bcdb_report_categories");      
        
    }
    
    
    public function buildCategoryList($cats = null, &$return = array(), $parentNames = array()){
        
        // Get cats on first run through
        if (is_null($cats)){
            $cats = $this->getReportCategories();
        }
        
        // Loop cats
        if ($cats)
        {
            foreach($cats as $cat)
            {
                $return[$cat->id] = ($parentNames) ? implode(" / ", $parentNames) . " / " . $cat->name : $cat->name;
                if (isset($cat->children) && $cat->children)
                {
                    $parentNames[] = $cat->name; 
                    $this->buildCategoryList($cat->children, $return, $parentNames);
                }
                
                $parentNames = array();
                
            }
        }
                        
        return $return;
        
    }
    
    /**
     * Load POST data into the Config object
     */
    public function loadPostData(){
      
        // Report categories
        $cats = array();
        
        if (isset($_POST['report_cat']))
        {
            foreach($_POST['report_cat'] as $key => $cat)
            {
                $obj = new \stdClass();
                $obj->id = (ctype_digit($cat['id'])) ? $cat['id'] : null;
                $obj->name = $cat['name'];
                $obj->dynamicID = $key;
                $obj->dynamicParent = (isset($cat['parent']) && strlen($cat['parent']) > 0) ? $cat['parent'] : null;
                $cats[] = $obj;
            }
        }
        
        $this->reportCats = $cats;
        
        
        // Course categories
        $this->courseCats = (isset($_POST['course_cats'])) ? $_POST['course_cats'] : array();  
        
        
        // Reporting elements
        $this->reportingElements = (isset($_POST['elements_enabled'])) ? $_POST['elements_enabled'] : array();
        
                
    }
    
    /**
     * Check for errors in form data
     * @return boolean
     */
    public function hasErrors(){
        return false;
    }
    
    public function save(){
        
        global $DB;
                
        // Report categories
        $dArray = array();
        
        if ($this->reportCats)
        {
            
            // Update/Insert all the records
            foreach($this->reportCats as $cat)
            {
                
                $obj = new \stdClass();
                $obj->id = $cat->id;
                $obj->name = $cat->name;
                
                $check = $DB->get_record("block_bcdb_report_categories", array("id" => $obj->id));
                
                // Update
                if ($check)
                {
                    $DB->update_record("block_bcdb_report_categories", $obj);
                }
                
                // Insert
                else
                {
                    $cat->id = $DB->insert_record("block_bcdb_report_categories", $obj);
                }
                
                // Set dynamic array element
                $dArray[$cat->dynamicID] = $cat->id;
                
            }
            
            
            // Now to the parent relationships
            foreach($this->reportCats as $cat)
            {
                
                // Does it have a parent?
                if (!is_null($cat->dynamicParent))
                {
                    $cat->parent = (isset($dArray[$cat->dynamicParent])) ? $dArray[$cat->dynamicParent] : null;
                    unset($cat->dynamicID);
                    unset($cat->dynamicParent);
                    $DB->update_record("block_bcdb_report_categories", $cat);
                }
                
            }
            
        }
        
        
        // Remove any report categories not submitted
        $allCats = $DB->get_records("block_bcdb_report_categories");
        if ($allCats)
        {
            foreach($allCats as $cat)
            {
                if (!in_array($cat->id, $dArray))
                {
                    
                    // Delete the category
                    $DB->delete_records("block_bcdb_report_categories", array("id" => $cat->id));
                    
                    // Update any reports in that category
                    $DB->execute("UPDATE {block_bcdb_reports} SET category = 0 WHERE category = ?", array($cat->id));
                    
                }
            }
        }
        
                
        // Course Categories
        $courseCats = implode(",", $this->courseCats);
        \BCDB\Setting::updateSetting("course_cats", $courseCats);
        
        
        // Reporting elements
        $elements = \BCDB\Report\Element::retrieve(false, true);
        if ($elements)
        {
            foreach($elements as $id => $element)
            {
                $obj = new \stdClass();
                $obj->id = $element->getID();
                $obj->enabled = (array_key_exists($id, $this->reportingElements)) ? 1 : 0;
                \BCDB\Report\Element::update($obj);
            }
        }
        
        
        
        
        return true;
        
        
    }
    
    /**
     * Get just the top level categories selected, not their unselected sub categories below
     * @return type
     */
    public function getTopLevelCourseCategories(){
       
        $return = array();
        
        $courseCats = explode(",", \BCDB\Setting::getSetting('course_cats'));
        if ($courseCats)
        {
            foreach($courseCats as $catID)
            {
                
                if (is_object($catID)){
                    $obj = $catID;
                } else {
                    $obj = \coursecat::get($catID)->get_db_record();
                }
                
                if (!array_key_exists($obj->id, $return)){
                                
                    $obj->displayname = $obj->name;
                    $return[$obj->id] = $obj;
                
                }
                
            }
        }
        
        return $return;
        
    }
    
    public function getCourseCategories($parent = false, &$return = array()){
        
        global $DB;
        
        if ($parent){
            $courseCats = $DB->get_records("course_categories", array("parent" => $parent->id), "name");
        } else {
            $courseCats = explode(",", \BCDB\Setting::getSetting('course_cats'));
        }
        
        if ($courseCats)
        {
            foreach($courseCats as $catID)
            {
                
                if (is_object($catID)){
                    $obj = $catID;
                } else {
                    $obj = \coursecat::get($catID)->get_db_record();
                }
                
                if (!array_key_exists($obj->id, $return)){
                                
                    $obj->displayname = ($parent) ? $parent->displayname . ' / ' . $obj->name : $obj->name;
                    $return[$obj->id] = $obj;

                    // Now look for any child categories beneath this
                    $this->getCourseCategories($obj, $return);
                
                }
                
            }
        }
        
        return $return;
        
    }
    
    
}
