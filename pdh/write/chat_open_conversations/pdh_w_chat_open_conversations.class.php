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
  | pdh_w_chat_open_conversations
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_w_chat_open_conversations'))
{
  class pdh_w_chat_open_conversations extends pdh_w_generic
  {
    
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
    
    public function minConversation($strConversationKey, $intUserID){
    	$objQuery = $this->db->prepare("REPLACE INTO __chat_open_conversations :p")->set(array(
    			'user_id'			=> $intUserID,
    			'conversation_key'	=> $strConversationKey,
    			'open'				=> 1,
    			'minimized'			=> 1,
    	))->execute();
    
    	$this->pdh->enqueue_hook('chat_open_conversations_update');
    }
    
    public function maxConversation($strConversationKey, $intUserID){
    	$objQuery = $this->db->prepare("REPLACE INTO __chat_open_conversations :p")->set(array(
    			'user_id'			=> $intUserID,
    			'conversation_key'	=> $strConversationKey,
    			'open'				=> 1,
    			'minimized'			=> 0,
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