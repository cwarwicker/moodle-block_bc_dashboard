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

namespace BCDB\Controllers;

/**
 * Description of DashboardController
 *
 * @author cwarwicker
 */
class ConfigController extends \BCDB\Controller {
    
    protected $component = 'config';

    public function main(){
        
        if (!has_capability('block/bc_dashboard:config', $this->context)){
            \bcdb_fatalError( get_string('invalidaccess', 'block_bc_dashboard') );
        }
        
        
        $messages = array();
                                        
        // Form submitted and they have the capability to do it?
        if (isset($_POST['submit_config'])){
            
            $Config = new \BCDB\Config();
            $Config->loadPostData();
            
            if (!$Config->hasErrors()){
                if ($Config->save()){
                    $messages['success'] = get_string('configsaved', 'block_bc_dashboard');;
                } else {
                    $messages['errors'] = get_string('error:unknownsaveerror', 'block_bc_dashboard');
                }
            } else {
                $messages['errors'] = $Config->getErrors();
            }            
            
        }
                
        $this->view->set("messages", $messages);
        
    }
    
}
