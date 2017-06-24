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
