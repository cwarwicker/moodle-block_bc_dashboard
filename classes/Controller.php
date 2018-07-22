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

abstract class Controller {
    
    protected $action = 'main';
    protected $params = null;
    protected $view = null;
    protected $path;
    protected $renderOutput = true;
    protected $component = null;
        
    protected $context;

    public $tmp = false; // Since rendering is done on the destruction of the controller, if we are loading a controller but arne't going to use it (e.g. when working out route), don't call actions or render


    public function __construct($tmp = false) {
                        
        global $bcdb;
        
        $this->tmp = $tmp;
        
        $View = null;
        $reflector = new \ReflectionClass($this);
        $file = $reflector->getFileName();
        preg_match("/(\\\\|\/)([a-z]+)Controller\.php/i", $file, $matches);
        
        // If it matched the controller name properly
        if ($matches && count($matches) == 3)
        {
            
            $path = str_replace($matches[0], "", $file);
            $this->path = $path . DIRECTORY_SEPARATOR;
            
            $class = 'BCDB\\Views\\'.$matches[2].'View';
            $file = str_replace($matches[0], DIRECTORY_SEPARATOR.$matches[2].'View.php', $file);
                        
            if (file_exists($file)){
                require_once $file;
                $View = new $class();
            }
                        
        }
        
        $this->view = $View;
        $this->context = $bcdb['context'];
        
        $this->initialCheckPermissions();
                
    }
    
    
    protected function initialCheckPermissions(){}
    
    
    public function setAction($value){
        $this->action = $value;
        return $this;
    }
    
    public function getAction(){
        return $this->action;
    }
    
    public function setParams($value){
        $this->params = $value;
        return $this;
    }
    
    public function getParams(){
        return $this->params;
    }
    
    public function setView($obj){
        $this->view = $obj;
        return $this;
    }
    
    public function getPath(){
        return $this->path;
    }
    
    public function getComponent(){
        return $this->component;
    }
        
    public function loadAction(){
                
        if (!is_null($this->action) && method_exists($this, $this->action)){
            call_user_func( array($this, $this->action), $this->params );
        }
        
    }
    
    public function noRender(){
        $this->renderOutput = false;
        return $this;
    }
    
    protected function hasCapability($capability, $courseID = false){
        
        return \bcdb_has_capability($capability, $courseID);
        
    }
    
    /**
     * Destroy the object
     */
    public function __destruct() {
                
        if (!$this->tmp)
        {
        
            // Call the action
            $this->loadAction();

            // Render the view
            if ($this->view instanceof \BCDB\View && $this->renderOutput){
                $this->view->setController($this);
                $this->view->render();
            }
        
        }
        
    }
    
}
