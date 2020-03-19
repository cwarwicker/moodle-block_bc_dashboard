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
namespace BCDB\Report;

/**
 * Description of Element
 *
 * @author cwarwicker
 */
abstract class Element {

    // These variables are stored in the database
    protected $id;
    protected $name;
    protected $plugin;
    protected $subplugin;
    protected $enabled;

    // These variables are static and defined on the element
    protected $level;
    protected $type;
    protected $datatype = 'numeric';
    protected $options; // These are the parameters you can CHOOSE from

    // These variables are changed
    protected $displayName;
    protected $object;
    protected $params; // These are the parameters you have CHOSEN
    protected $sql;
    protected $alias;
    protected $ran = false;

    const DATE_FORMAT = 'd-m-Y';

    public function __construct($params = null) {

        $this->sql = array(
            'select' => '',
            'join' => array(),
            'where' => array(),
            'params' => array()
        );

        $this->params = $params;

    }

    public function isEnabled(){
        return ($this->enabled == 1);
    }

    public function getID(){
        return $this->id;
    }

    public function setID($id){
        $this->id = $id;
        return $this;
    }

    public function getName(){
        return $this->name;
    }

    public function getPlugin(){
        return $this->plugin;
    }

    public function getSubPlugin(){
        return $this->subplugin;
    }

    public function setName($name){
        $this->name = $name;
        return $this;
    }

    public function setPlugin($plugin){
        $this->plugin = $plugin;
        return $this;
    }

    public function setSubPlugin($plugin){
        $this->subplugin = $plugin;
        return $this;
    }

    public function setDisplayName($name){
        $this->displayName = $name;
        return $this;
    }

    public function setEnabled($val){
        $this->enabled = $val;
    }

    public function getDisplayName(){
        return (!is_null($this->displayName)) ? $this->displayName : $this->getStringName();
    }

    public function getStringName(){
        return get_string('bc_dashboard:'.$this->name, $this->plugin);
    }

    public function getLevel(){
        return $this->level;
    }

    public function getDataType(){
        return $this->datatype;
    }

    public function getType(){
        return $this->type;
    }

    public function getOptions(){
        return $this->options;
    }

    public function getEnabled(){
        return $this->enabled;
    }

    public function getSQL(){
        return $this->parseSQL($this->sql);
    }

    private function parseSQL($sqlArray){

        $sqlArray['select'] = trim($sqlArray['select']);

        if (strlen($sqlArray['select']) == 0){
            unset($sqlArray['select']);
            return $sqlArray;
        }

        // Set alias on selected item
        $sqlArray['select'] .= ' as ' . $this->getAliasName();

        return $sqlArray;

    }

    public function getParams(){
        return $this->params;
    }

    public function getParam($id){
        return (isset($this->params[$id])) ? $this->params[$id] : false;
    }

    public function setParams($params){
        $this->params = $params;
        return $this;
    }

    public function setParam($key, $param){
        $this->params[$key] = $param;
        return $this;
    }

    public function getAlias(){
        return $this->alias;
    }

    public function setAlias($alias){
        $this->alias = $alias;
        return $this;
    }

    public function getAliasName($hybrid = false){

        $alias = "{$this->alias}_value";
        if (strlen($hybrid)) $alias .= ":{$hybrid}";

        return $alias;
    }



    public function run(){
        if (!$this->ran()){
            $this->get();
            $this->ran = true;
        }
        return $this->getSQL();
    }

    public function ran(){
        return ($this->ran);
    }

    /**
     * Get the value of this element for a given user object
     * @param type $obj
     */
    public function val($obj){

        $field = $this->getAliasName();
        return $obj[$field];

    }

    // If the setting of one parameter affects others, this can be used to refresh them in individual elements
    public function refreshOptions(){}

    abstract public function get();
    abstract public function call(&$results);

    /**
     * This is to be overriden by actual aggregate methods
     * @param type $results
     */
    public function aggregate($results){

        $field = $this->getAliasName();
        if ($this->datatype == 'string'){
            return array($field => '-');
        } else {
            return array($field => 0);
        }

    }


    /**
     * Load an element object from its id
     * @global \BCDB\Report\type $CFG
     * @global \BCDB\Report\type $DB
     * @param type $id
     * @return \BCDB\Report\classname|boolean
     */
    public static function load($id){

        global $CFG, $DB;

        $record = $DB->get_record("block_bcdb_report_elements", array("id" => $id));
        if ($record)
        {

            // Require file
            require_once $CFG->dirroot . $record->filepath;

            // Load class
            $element = new $record->classname();
            $element->setID($record->id);
            $element->setName($record->name);
            $element->setPlugin($record->plugin);
            $element->setSubPlugin($record->subplugin);
            $element->setEnabled($record->enabled);

            return $element;

        }

        return false;

    }


    /**
     * Retrieve all the elements from the database
     * @global \BCDB\Report\type $DB
     */
    public static function retrieve($enabledOnly = true, $flatList = false){

        global $DB;

        $return = array();
        $params = ($enabledOnly) ? array("enabled" => 1) : array();

        $records = $DB->get_records("block_bcdb_report_elements", $params, "plugin, subplugin, name");

        if ($records)
        {
            foreach($records as $record)
            {

                // Create an array for this plugin
                if (!isset($return[$record->plugin]) && !$flatList){
                    $return[$record->plugin] = array();
                    $arr = &$return[$record->plugin];
                }

                // If there is a subplugin, create an array for that as well
                if (isset($record->subplugin) && !isset($return[$record->plugin][$record->subplugin]) && !$flatList){
                    $return[$record->plugin][$record->subplugin] = array();
                    $arr = &$return[$record->plugin][$record->subplugin];
                }

                if ($flatList){
                    $arr = &$return;
                }

                $obj = \BCDB\Report\Element::load($record->id);
                $obj->_id = $obj->getID();
                $obj->_name = $obj->getDisplayName();
                $obj->_enabled = $obj->getEnabled();
                $arr[$record->id] = $obj;

            }
        }

        return $return;

    }

    /**
     * Scan for elements in moodle plugins
     * @global type $CFG
     */
    public static function scan(){

        $elementsArray = array();

        // Get all plugin types
        $plugintypes = \core_component::get_plugin_types();

        // Loop through plugin types and find plugins for each
        foreach ($plugintypes as $type => $typedir) {

            $plugins = \get_plugin_list($type);
            if ($plugins)
            {
                foreach($plugins as $pluginName => $dir)
                {

                    unset($elements);

                    // Look for a /db/bc_dashboard.php file
                    if (file_exists($dir . '/db/bc_dashboard.php')){

                        // Include the file
                        include_once $dir . '/db/bc_dashboard.php';

                        // Load the elements array
                        if (isset($elements) && $elements)
                        {

                            foreach($elements as $elementName => $elementArr)
                            {

                                // Combine the type of plugin and the plugin name, e.g. "block_gradetracker"
                                $plugin = $type . '_' . $pluginName;

                                // Insert the element or update & return it if it already exists
                                $element = self::updateElement($elementName, $plugin, $elementArr);

                                // Store the IDs of the ones we have saved to we can delete any others
                                if ($element)
                                {
                                    $elementsArray[] = $element->id;
                                }

                            }

                        }

                    }

                }
            }

        }

        // Delete any which weren't found but are still in the database
        self::deleteMissingElements($elementsArray);

    }

    /**
     * This one updates a DB record, using the object provided
     * @global \BCDB\Report\type $DB
     * @param type $obj
     * @return type
     */
    public static function update($obj){

        global $DB;
        return $DB->update_record("block_bcdb_report_elements", $obj);

    }


    /**
     * This one is used when scanning for new elements and updates/inserts based on name and plugin
     * @global \BCDB\Report\type $DB
     * @param type $name
     * @param type $plugin
     * @param type $arr
     * @return type
     */
    public static function updateElement($name, $plugin, $arr){

        global $DB;

        $check = $DB->get_record("block_bcdb_report_elements", array("name" => $name, "plugin" => $plugin));

        // If it exists, update and return
        if ($check)
        {

            $check->filepath = $arr['file'];
            $check->classname = $arr['class'];
            $check->subplugin = (isset($arr['sub'])) ? $arr['sub'] : null;
            if (isset($arr['enabled'])){
                $check->enabled = $arr['enabled'];
            }
            $result = $DB->update_record("block_bcdb_report_elements", $check);

            return ($result) ? $check : false;

        }

        // Else, insert and return
        else
        {

            $ins = new \stdClass();
            $ins->name = $name;
            $ins->plugin = $plugin;
            $ins->filepath = $arr['file'];
            $ins->classname = $arr['class'];
            $ins->subplugin = (isset($arr['sub'])) ? $arr['sub'] : null;
            if (isset($arr['enabled'])){
                $ins->enabled = $arr['enabled'];
            }
            $ins->id = $DB->insert_record("block_bcdb_report_elements", $ins);

            return ($ins->id) ? $ins : false;

        }

    }

    /**
     * Delete elements from the database that can no longer be found in the plugins
     * @global \BCDB\Report\type $DB
     * @param type $elementIDs
     * @return type
     */
    public static function deleteMissingElements($elementIDs){

        global $DB;

        $placeholders = bcdb_create_sql_placeholders($elementIDs);

        // Delete records not included in the array of IDs
        return $DB->delete_records_select("block_bcdb_report_elements", "id NOT IN ({$placeholders})", $elementIDs);

    }




}
