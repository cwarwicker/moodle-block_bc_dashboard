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

class Setting {

    public static function getSetting($setting, $obj = false){
        
        global $DB;
        $check = $DB->get_record("block_bcdb_settings", array("setting" => $setting));
        return ($check) ? (($obj) ? $check : $check->value) : false;
        
    }
    
    public static function updateSetting($setting, $value){
        
        global $DB;
        
        $check = self::getSetting($setting, true);
        if ($check)
        {
            $check->value = $value;
            return $DB->update_record("block_bcdb_settings", $check);
        }
        else
        {
            $obj = new \stdClass();
            $obj->setting = $setting;
            $obj->value = $value;
            return $DB->insert_record("block_bcdb_settings", $obj);
        }
        
    }
    
    
}
