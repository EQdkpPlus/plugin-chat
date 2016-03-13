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