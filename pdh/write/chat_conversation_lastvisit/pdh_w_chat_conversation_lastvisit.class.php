<?php
/*
 * Project:     EQdkp chat_conversation_lastvisit
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2011-11-01 13:38:39 +0100 (Di, 01. Nov 2011) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy $
 * @copyright   2008-2011 Aderyn
 * @link        http://eqdkp-plus.com
 * @package     chat_conversation_lastvisit
 * @version     $Rev: 11419 $
 *
 * $Id: pdh_w_chat_conversation_lastvisit.class.php 11419 2011-11-01 12:38:39Z hoofy $
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
    /**
     * __dependencies
     * Get module dependencies
     */
    public static function __shortcuts()
    {
      $shortcuts = array('db', 'pdh', 'time', 'user');
      return array_merge(parent::$shortcuts, $shortcuts);
    }
    
    
    public function setLastVisit($strConversationKey){
    	$objQuery = $this->db->prepare("REPLACE INTO __chat_conversation_lastvisit :p")->set(array(
    			'date'				=> $this->time->time,
    			'user_id'			=> $this->user->id,
    			'conversation_key'	=> $strConversationKey,
    	))->execute();
    }

  } //end class
} //end if class not exists

?>