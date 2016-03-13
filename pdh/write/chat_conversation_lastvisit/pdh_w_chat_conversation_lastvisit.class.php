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
  | pdh_w_chat_conversation_lastvisit
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_w_chat_conversation_lastvisit'))
{
  class pdh_w_chat_conversation_lastvisit extends pdh_w_generic
  {
    
    public function setLastVisit($strConversationKey, $date=false){
    	$objQuery = $this->db->prepare("REPLACE INTO __chat_conversation_lastvisit :p")->set(array(
    			'date'				=> ($date) ? $date : $this->time->time,
    			'user_id'			=> $this->user->id,
    			'conversation_key'	=> $strConversationKey,
    	))->execute();
    	
    	$this->pdh->enqueue_hook('chat_conversation_lastvisit_update');
    }

  } //end class
} //end if class not exists

?>