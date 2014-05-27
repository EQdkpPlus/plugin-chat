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
 * $Id: pdh_w_chat_conversations.class.php 11419 2011-11-01 12:38:39Z hoofy $
 */

if (!defined('EQDKP_INC'))
{
  die('Do not access this file directly.');
}

/*+----------------------------------------------------------------------------
  | pdh_w_chat_conversations
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_w_chat_conversations'))
{
  class pdh_w_chat_conversations extends pdh_w_generic
  {
    
    public function add($strConversationKey, $arrUser, $strTitle=''){
    	$objQuery = $this->db->prepare("INSERT INTO __chat_conversations :p")->set(array(
    			'user'				=> serialize($arrUser),
    			'conversation_key'	=> $strConversationKey,
    			'title'				=> $strTitle,
    	))->execute();
    	
    	$this->pdh->enqueue_hook('chat_conversations_update');
    }
    
    public function changeTitle($strConversationKey, $strTitle){
    	$objQuery = $this->db->prepare("UPDATE __chat_conversations :p WHERE conversation_key=?")->set(array(
    			'title'	=> $strTitle,
    	))->execute($strConversationKey);
    	
    	$this->pdh->enqueue_hook('chat_conversations_update');
    }

  } //end class
} //end if class not exists

?>