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
  | pdh_r_chat_open_conversations
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_r_chat_open_conversations'))
{
  class pdh_r_chat_open_conversations extends pdh_r_generic
  {

    /**
     * Data array loaded by initialize
     */
    private $data, $user_data;

    /**
     * Hook array
     */
    public $hooks = array(
      'chat_open_conversations_update'
    );

    /**
     * reset
     * Reset chat_open_conversations read module by clearing cached data
     */
    public function reset()
    {
		$this->pdc->del('pdh_chat_open_conversations_table');
		$this->pdc->del('pdh_chat_open_conversations_userdata');
		$this->data = NULL;
		$this->user_data = NULL;
    }

    /**
     * init
     * Initialize the chat_open_conversations read module by loading all information from db
     *
     * @returns boolean
     */
    public function init()
    {
      // try to get from cache first
      $this->data = $this->pdc->get('pdh_chat_open_conversations_table');
      $this->user_data = $this->pdc->get('pdh_chat_open_conversations_userdata');
      
      if($this->data !== NULL)
      {
        return true;
      }

      // empty array as default
      $this->data = array();
      $this->user_data = array();

      // read all chat_open_conversations entries from db
      $sql = 'SELECT
               *
              FROM `__chat_open_conversations`
              ORDER BY open DESC;';
      $result = $this->db->query($sql);
      if ($result)
      {

        // add row by row to local copy
        while ($row = $result->fetchAssoc())
        {
          $this->data[(int)$row['user_id'].'.'.$row['conversation_key']] = array(
            'user_id'			=> (int)$row['user_id'],
			'conversation_key'	=> $row['conversation_key'],
			'open'				=> (int)$row['open'],
          	'minimized'			=> (int)$row['minimized'],
          );

          $this->user_data[(int)$row['user_id']][$row['conversation_key']] =  array(
            'user_id'			=> (int)$row['user_id'],
			'conversation_key'	=> $row['conversation_key'],
			'open'				=> (int)$row['open'],
          	'minimized'			=> (int)$row['minimized'],
          );
        }

      }

      // add data to cache
      $this->pdc->put('pdh_chat_open_conversations_table', $this->data, null);
      $this->pdc->put('pdh_chat_open_conversations_userdata', $this->user_data, null);

      return true;
    }

    /**
     * get_id_list
     * Return the list of chat_open_conversations ids
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

	
	public function get_openUserConversations($intUserID){
		if (isset($this->user_data[$intUserID])){
			return $this->user_data[$intUserID];
		}
		return array();
	}
	
	public function get_is_open($userid, $strKey){
		if ($this->user_data[$userid][$strKey]['open']) return true;
		return false;
	}
	
	public function get_openConversationsWithkey($strKey){
		$arrOut = array();
		foreach($this->data as $var){
			if($var['conversation_key'] === $strKey && $var['open']){
				$arrOut[] = $var['user_id'];
			}
		}
		return $arrOut;
	}


  } //end class
} //end if class not exists

?>