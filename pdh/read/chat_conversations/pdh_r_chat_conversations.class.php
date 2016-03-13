<?php
/*	Project:	EQdkp-Plus
 *	Package:	Chat Plugin
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2016 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('EQDKP_INC'))
{
  die('Do not access this file directly.');
}

/*+----------------------------------------------------------------------------
  | pdh_r_chat_conversations
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_r_chat_conversations'))
{
  class pdh_r_chat_conversations extends pdh_r_generic
  {

    /**
     * Data array loaded by initialize
     */
    private $data;

    /**
     * Hook array
     */
    public $hooks = array(
      'chat_conversations_update'
    );

    /**
     * reset
     * Reset chat_conversations read module by clearing cached data
     */
    public function reset()
    {
		$this->pdc->del('pdh_chat_conversations_table');
		$this->data = NULL;
    }

    /**
     * init
     * Initialize the chat_conversations read module by loading all information from db
     *
     * @returns boolean
     */
    public function init()
    {
      // try to get from cache first
      $this->data = $this->pdc->get('pdh_chat_conversations_table');
      
      if($this->data !== NULL)
      {
        return true;
      }

      // empty array as default
      $this->data = array();
      $this->user_data = array();

      // read all chat_conversations entries from db
      $sql = 'SELECT
               *
              FROM `__chat_conversations`';
      $result = $this->db->query($sql);
      if ($result)
      {

        // add row by row to local copy
        while ($row = $result->fetchAssoc())
        {
          $this->data[$row['conversation_key']] = array(
            'user'				=> unserialize($row['user']),
			'title'				=> $row['title'],
          	'id'				=> $row['id'],
          );

        }

      }

      // add data to cache
      $this->pdc->put('pdh_chat_conversations_table', $this->data, null);

      return true;
    }

    /**
     * get_id_list
     * Return the list of chat_conversations ids
     *
     * @returns array(int)
     */
    public function get_id_list()
    {
      if (is_array($this->data))
      {
        return array_keys($this->data);
      }
      return array();
    }
    
    public function get_conversation($strKey){
    	if (isset($this->data[$strKey])) return $this->data[$strKey];
    	
    	return false;
    }
    
    public function get_user($strKey){
    	if (isset($this->data[$strKey])) return $this->data[$strKey]['user'];
    	return false;
    }
    
    public function get_title($strKey){
    	$arrData = $this->data[$strKey];
    	
    	if ($arrData['title'] != "") return $arrData['title'];
    	
    	$arrTitle = array();
    	foreach($arrData['user'] as $user_id){
    		if ($user_id == $this->user->id) continue;
    		$arrTitle[] = $this->pdh->get('user', 'name', array($user_id));
    	}
    	return implode(", ", $arrTitle);
    }
    
    public function get_id($strKey){
    	if (isset($this->data[$strKey])) return $this->data[$strKey]['id'];
    	return false;
    }
    
    public function get_key($intId){
    	$result = search_in_array($intId, $this->data, false, "id");
    	if (is_array($result) && count($result)) {
    		$arrKey = array_keys($result);
    		return $arrKey[0];
    	}
    	return false;
    }

  } //end class
} //end if class not exists

?>