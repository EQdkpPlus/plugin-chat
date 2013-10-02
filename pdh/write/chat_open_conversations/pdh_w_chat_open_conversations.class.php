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
 * $Id: pdh_w_chat_open_conversations.class.php 11419 2011-11-01 12:38:39Z hoofy $
 */

if (!defined('EQDKP_INC'))
{
  die('Do not access this file directly.');
}

/*+----------------------------------------------------------------------------
  | pdh_w_chat_open_conversations
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_w_chat_open_conversations'))
{
  class pdh_w_chat_open_conversations extends pdh_w_generic
  {
    /**
     * __dependencies
     * Get module dependencies
     */
    public static function __shortcuts()
    {
      $shortcuts = array('db', 'pdh', 'time', 'user');
      return array_merge(parent::$shortcuts, $shortcuts);
    }
    
    public function openConversation($strConversationKey, $intUserID, $intOpen = 1, $arrUser=array()){
    	$objQuery = $this->db->prepare("REPLACE INTO __chat_open_conversations :p")->set(array(
			'user_id'			=> $intUserID,
			'conversation_key'	=> $strConversationKey,
			'open'				=> $intOpen,
		))->execute();
    	
    	$arrUser = array_merge(array($this->user->id), $arrUser);
    	$arrUser = array_unique($arrUser);
    	
    	if (!$this->pdh->get('chat_conversations', 'conversation', array($strConversationKey))){
    		
    		$this->pdh->put('chat_conversations', 'add', array($strConversationKey, $arrUser));
    	}
    	
    	$this->pdh->enqueue_hook('chat_open_conversations_update');
    	return count($arrUser);
    }
    
    public function closeConversation($strConversationKey, $intUserID){
    	$objQuery = $this->db->prepare("REPLACE INTO __chat_open_conversations :p")->set(array(
    			'user_id'			=> $intUserID,
    			'conversation_key'	=> $strConversationKey,
    			'open'				=> 0,
    	))->execute();

    	$this->pdh->enqueue_hook('chat_open_conversations_update');
    }
    
    public function archiveConversation($strConversationKey, $intUserID){
    	$this->db->prepare("DELETE FROM __chat_open_conversations WHERE conversation_key=? AND user_id=?")->execute($strConversationKey, $intUserID);
    	$this->pdh->enqueue_hook('chat_open_conversations_update');
    }


  } //end class
} //end if class not exists

?>