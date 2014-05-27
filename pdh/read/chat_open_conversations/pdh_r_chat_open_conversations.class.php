<?php
/*
 * Project:     EQdkp chat_open_conversations
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2011-11-01 13:38:39 +0100 (Di, 01. Nov 2011) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy $
 * @copyright   2008-2011 Aderyn
 * @link        http://eqdkp-plus.com
 * @package     chat_open_conversations
 * @version     $Rev: 11419 $
 *
 * $Id: pdh_r_chat_open_conversations.class.php 11419 2011-11-01 12:38:39Z hoofy $
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
          $this->data[$row['conversation_key']] = array(
            'user_id'			=> (int)$row['user_id'],
			'conversation_key'	=> $row['conversation_key'],
			'open'				=> (int)$row['open'],
          );

          $this->user_data[(int)$row['user_id']][$row['conversation_key']] =  $this->data[$row['conversation_key']];
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
		if ($this->user[$userid][$strKey]['open']) return true;
		return false;
	}


  } //end class
} //end if class not exists

?>