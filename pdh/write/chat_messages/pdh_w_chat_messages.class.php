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
 * $Id: pdh_w_chat_messages.class.php 11419 2011-11-01 12:38:39Z hoofy $
 */

if (!defined('EQDKP_INC'))
{
  die('Do not access this file directly.');
}

/*+----------------------------------------------------------------------------
  | pdh_w_chat_messages
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_w_chat_messages'))
{
  class pdh_w_chat_messages extends pdh_w_generic
  {
    
  	
  	
  	
  	
    public function addMessage($strConversationKey, $strText){
    	$strText = substr($strText, 0, -1);
    	$objQuery = $this->db->prepare("INSERT INTO __chat_messages :p")->set(array(
    			'user_id'			=> $this->user->id,
    			'conversation_key'	=> $strConversationKey,
    			'text'				=> $strText,
    			'date'				=> $this->time->time,
    			'reed'				=> 0,
    	))->execute();
    }
    
    public function markRead($strConversationKey){
    	$objQuery = $this->db->prepare("UPDATE __chat_messages :p WHERE conversation_key=? AND user_id != ?")->set(array(
    			'reed'				=> 1
    	))->execute($strConversationKey, $this->user->id);
    }
    
    public function deleteMessage($intMessageID){
    	$objQuery = $this->db->prepare("DELETE FROM __chat_messages WHERE id=?")->execute($intMessageID);
    }

  } //end class
} //end if class not exists

?>