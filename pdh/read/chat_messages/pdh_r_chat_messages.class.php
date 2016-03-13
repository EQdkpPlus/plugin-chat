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
  | pdh_r_chat_messages
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_r_chat_messages'))
{
  class pdh_r_chat_messages extends pdh_r_generic
  {

    /**
     * Data array loaded by initialize
     */
    private $data, $user_data;

    /**
     * Hook array
     */
    public $hooks = array(
      'chat_messages_update'
    );

    /**
     * reset
     * Reset chat_messages read module by clearing cached data
     */
    public function reset()
    {

    }

    /**
     * init
     * Initialize the chat_messages read module by loading all information from db
     *
     * @returns boolean
     */
    public function init()
    {
      return true;
    }

    /**
     * get_id_list
     * Return the list of chat_messages ids
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
    
    public function get_id($intMessageID){
    	$objQuery = $this->db->prepare("SELECT * FROM `__chat_messages` WHERE id=?")->execute($intMessageID);
    	if($objQuery){
    		$arrResult = $objQuery->fetchAssoc();
    		return $arrResult;
    	}
    	return false;
    }

	
	public function get_openUserConversations($intUserID){
		if (isset($this->user_data[$intUserID])){
			return $this->user_data[$intUserID];
		}
		return array();
	}
	
	public function get_latestOrUnread($strConversationKey){
		$arrUser = $this->pdh->get("chat_conversations", "user", array($strConversationKey));
		$arrOut = array();
		$this->bbcode->SetSmiliePath($this->server_path.'images/smilies');
		
		if (count($arrUser) > 2){
			$lastvisit = $this->pdh->get("chat_conversation_lastvisit", "lastVisit", array($this->user->id, $strConversationKey));
			$sql = 'SELECT * FROM `__chat_messages` WHERE date > ? AND conversation_key=? ORDER BY id DESC;';
			$result = $this->db->prepare($sql)->execute($lastvisit, $strConversationKey);
			if ($result && $result->numRows)
			{
				while ($row = $result->fetchAssoc()){
					$arrOut[] = array(
							'id'		=> $row['id'],
							'user_id'	=> (int)$row['user_id'],
							'username'	=> $this->pdh->get('user', 'name', array((int)$row['user_id'])),
							'text'		=> nl2br($this->bbcode->MyEmoticons($row['text'])),
							'reed'		=> 0,
							'avatar'	=> $this->pdh->geth('user', 'avatarimglink', array((int)$row['user_id'])),
							'profile'	=> $this->routing->build('user', $this->pdh->get('user', 'name', array((int)$row['user_id'])), 'u'.$row['user_id']),
							'date'		=> $this->time->user_date((int)$row['date'], true),
							'timestamp'	=> (int)$row['date'],
					);
				}
			} else {
				//All are read, get 20 latest messages
				$sql = 'SELECT
               *
              FROM `__chat_messages`
				WHERE conversation_key=?
              ORDER BY id DESC';
				$result = $this->db->prepare($sql)->limit(20)->execute($strConversationKey);
				if ($result && $result->numRows)
				{
					// add row by row to local copy
					while ($row = $result->fetchAssoc()){
						$arrOut[] = array(
								'id'		=> $row['id'],
								'user_id'	=> (int)$row['user_id'],
								'username'	=> $this->pdh->get('user', 'name', array((int)$row['user_id'])),
								'text'		=> nl2br($this->bbcode->MyEmoticons($row['text'])),
								'reed'		=> 1,
								'avatar'	=> $this->pdh->geth('user', 'avatarimglink', array((int)$row['user_id'])),
								'profile'	=> $this->routing->build('user', $this->pdh->get('user', 'name', array((int)$row['user_id'])), 'u'.$row['user_id']),
								'date'		=> $this->time->user_date((int)$row['date'], true),
								'timestamp'	=> (int)$row['date'],
						);
					}
				}
			}		
		} else {
			$sql = 'SELECT
               *
              FROM `__chat_messages`
				WHERE id >= (SELECT id FROM __chat_messages WHERE user_id !=? AND reed = 0 AND conversation_key=? ORDER BY id ASC LIMIT 1 ) AND conversation_key=?
              ORDER BY id DESC;';
			$result = $this->db->prepare($sql)->execute($this->user->id, $strConversationKey, $strConversationKey);
			if ($result && $result->numRows)
			{
				while ($row = $result->fetchAssoc()){					
					$arrOut[] = array(
						'id'		=> $row['id'],
						'user_id'	=> (int)$row['user_id'],
						'username'	=> $this->pdh->get('user', 'name', array((int)$row['user_id'])),
						'text'		=> nl2br($this->bbcode->MyEmoticons($row['text'])),
						'reed'		=> ((int)$row['user_id'] == $this->user->id) ? 1 : (int)$row['reed'],
						'avatar'	=> $this->pdh->geth('user', 'avatarimglink', array((int)$row['user_id'])),
						'profile'	=> $this->routing->build('user', $this->pdh->get('user', 'name', array((int)$row['user_id'])), 'u'.$row['user_id']),
						'date'		=> $this->time->user_date((int)$row['date'], true),
						'timestamp'	=> (int)$row['date'],
					);
				}
			} else {
				$sql = 'SELECT
	               *
	              FROM `__chat_messages`
				  WHERE conversation_key=?
	              ORDER BY id DESC';
				$result = $this->db->prepare($sql)->limit(20)->execute($strConversationKey);
				if ($result && $result->numRows)
				{
					// add row by row to local copy
					while ($row = $result->fetchAssoc()){
						$arrOut[] = array(
								'id'		=> $row['id'],
								'user_id'	=> (int)$row['user_id'],
								'username'	=> $this->pdh->get('user', 'name', array((int)$row['user_id'])),
								'text'		=> nl2br($this->bbcode->MyEmoticons($row['text'])),
								'reed'		=> ((int)$row['user_id'] == $this->user->id) ? 1 : (int)$row['reed'],
								'avatar'	=> $this->pdh->geth('user', 'avatarimglink', array((int)$row['user_id'])),
								'profile'	=> $this->routing->build('user', $this->pdh->get('user', 'name', array((int)$row['user_id'])), 'u'.$row['user_id']),
								'date'		=> $this->time->user_date((int)$row['date'], true),
								'timestamp'	=> (int)$row['date'],
						);
					}
				}
			}	
		}

		return $arrOut;
	}


  } //end class
} //end if class not exists

?>