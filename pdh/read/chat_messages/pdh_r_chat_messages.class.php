<?php
/*
 * Project:     EQdkp chat_messages
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2011-11-01 13:38:39 +0100 (Di, 01. Nov 2011) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy $
 * @copyright   2008-2011 Aderyn
 * @link        http://eqdkp-plus.com
 * @package     chat_messages
 * @version     $Rev: 11419 $
 *
 * $Id: pdh_r_chat_messages.class.php 11419 2011-11-01 12:38:39Z hoofy $
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
		$this->pdc->del('pdh_chat_messages_table');
		$this->pdc->del('pdh_chat_messages_userdata');
		$this->data = NULL;
		$this->user_data = NULL;
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
      
      
      
      // try to get from cache first
      $this->data = $this->pdc->get('pdh_chat_messages_table');
      $this->user_data = $this->pdc->get('pdh_chat_messages_userdata');
      
      if($this->data !== NULL)
      {
        return true;
      }

      // empty array as default
      $this->data = array();
      $this->user_data = array();

      // read all chat_messages entries from db
      $sql = 'SELECT
               *
              FROM `__chat_messages`
              ORDER BY id ASC;';
      $result = $this->db->query($sql);
      if ($result)
      {

        // add row by row to local copy
        while ($row = $result->fetchAssoc())
        {
          $this->data[$row['conversation_key']] = array(
            'user_id'			=> (int)$row['user_id'],
			'conversation_key'	=> $row['conversation_key'],
			'open'				=> (int)$row['open'],
          );

          $this->user_data[(int)$row['user_id']][$row['conversation_key']] =  $this->data[$row['conversation_key']];
        }

      }

      // add data to cache
      $this->pdc->put('pdh_chat_messages_table', $this->data, null);
      $this->pdc->put('pdh_chat_messages_userdata', $this->user_data, null);

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