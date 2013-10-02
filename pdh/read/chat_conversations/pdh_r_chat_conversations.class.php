<?php
/*
 * Project:     EQdkp chat_conversations
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2011-11-01 13:38:39 +0100 (Di, 01. Nov 2011) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy $
 * @copyright   2008-2011 Aderyn
 * @link        http://eqdkp-plus.com
 * @package     chat_conversations
 * @version     $Rev: 11419 $
 *
 * $Id: pdh_r_chat_conversations.class.php 11419 2011-11-01 12:38:39Z hoofy $
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
     * __dependencies
     * Get module dependencies
     */
    public static function __shortcuts()
    {
      $shortcuts = array('pdc', 'db', 'pdh', 'config', 'bbcode', 'time', 'user');
      return array_merge(parent::$shortcuts, $shortcuts);
    }

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