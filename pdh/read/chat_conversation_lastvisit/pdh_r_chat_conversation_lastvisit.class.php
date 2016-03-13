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
  | pdh_r_chat_conversation_lastvisit
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_r_chat_conversation_lastvisit'))
{
  class pdh_r_chat_conversation_lastvisit extends pdh_r_generic
  {

    /**
     * Hook array
     */
    public $hooks = array(
      'chat_conversation_lastvisit_update'
    );

    /**
     * reset
     * Reset chat_conversation_lastvisit read module by clearing cached data
     */
    public function reset()
    {
    }

    /**
     * init
     * Initialize the chat_conversation_lastvisit read module by loading all information from db
     *
     * @returns boolean
     */
    public function init()
    {
      return true;
    }

	public function get_lastVisit($userid, $strKey){
		$objQuery = $this->db->prepare("SELECT date FROM __chat_conversation_lastvisit WHERE user_id=? AND conversation_key=?")->execute($userid, $strKey);
		if ($objQuery && $objQuery->numRows){
			$row = $objQuery->fetchAssoc();
			return intval($row['date']);
		}
		return 0;
	}


  } //end class
} //end if class not exists

?>