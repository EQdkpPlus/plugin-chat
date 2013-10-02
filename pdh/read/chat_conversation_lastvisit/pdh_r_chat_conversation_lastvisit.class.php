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
 * $Id: pdh_r_chat_conversation_lastvisit.class.php 11419 2011-11-01 12:38:39Z hoofy $
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
     * __dependencies
     * Get module dependencies
     */
    public static function __shortcuts()
    {
      $shortcuts = array('pdc', 'db', 'pdh', 'config', 'bbcode', 'time');
      return array_merge(parent::$shortcuts, $shortcuts);
    }

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