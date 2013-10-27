<?php
/*
 * Project:     EQdkp chat_online
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2011-11-01 13:38:39 +0100 (Di, 01. Nov 2011) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy $
 * @copyright   2008-2011 Aderyn
 * @link        http://eqdkp-plus.com
 * @package     chat_online
 * @version     $Rev: 11419 $
 *
 * $Id: pdh_r_chat_online.class.php 11419 2011-11-01 12:38:39Z hoofy $
 */

if (!defined('EQDKP_INC'))
{
  die('Do not access this file directly.');
}

/*+----------------------------------------------------------------------------
  | pdh_r_chat_online
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_r_chat_online'))
{
  class pdh_r_chat_online extends pdh_r_generic
  {
    /**
     * __dependencies
     * Get module dependencies
     */
    public static function __shortcuts()
    {
      $shortcuts = array('pdc', 'db', 'pdh', 'config', 'bbcode', 'time', 'user', 'routing');
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
      'chat_online_update'
    );

    /**
     * reset
     * Reset chat_online read module by clearing cached data
     */
    public function reset()
    {
		$this->pdc->del('pdh_chat_online_table');
		$this->data = NULL;
    }

    /**
     * init
     * Initialize the chat_online read module by loading all information from db
     *
     * @returns boolean
     */
    public function init()
    {
      // try to get from cache first
      $this->data = $this->pdc->get('pdh_chat_online_table');
      
      if($this->data !== NULL)
      {
        return true;
      }

      // empty array as default
      $this->data = array();
      $this->combined = array();

      // read all chat_online entries from db
      $sql = 'SELECT s.session_user_id, s.session_current
                FROM eqdkp20_sessions s
      			WHERE s.session_user_id != -1
                GROUP BY s.session_user_id
                ORDER BY s.session_current DESC';
      $result = $this->db->query($sql);
      if ($result)
      {

        // add row by row to local copy
        while ($row = $result->fetchAssoc())
        {
          $this->data[(int)$row['session_user_id']] = array(
          		'online'	=> true,
          		'lastvisit' => (int)$row['session_current'],
          );     
        }
      }
      
		$userIDs = $this->pdh->get('user', 'id_list');
        foreach($userIDs as $user_id){
        	if (!isset($this->data[$user_id])){
        		$this->data[$user_id] = array(
        			'online'	=> false,
        			'lastvisit' => $this->pdh->get('user', 'last_visit', array($user_id))
        		);
        	}
        }
      // add data to cache
      $this->pdc->put('pdh_chat_online_table', $this->data, 60*3);

      return true;
    }
    
    public function get_online_user_count(){
    	$count = 0;
    	unset($this->data[$this->user->id]);
    	foreach($this->data as $user_id => $val){
    		if ($val['online']) {$count++;} else {break;}
    	}
    	return $count;
    }

    public function get_userlist(){
    	return $this->data;
    }
    
    public function get_html_userlist(){
    	$html = '<ul>';
    	unset($this->data[$this->user->id]);
    	//Group Conversations
    	$arrOpenConversations = $this->pdh->get("chat_open_conversations", "openUserConversations", array($this->user->id));
    	
    	$html .= '<li><div onclick="window.location=\''.$this->routing->build("chat").'\'" class="hand">';
    		
    	$html .= '<div class="chat_user_avatar"><i class="fa fa-group fa-lg floatLeft"></i></div>';
    	$html .= '<div class="chat_username">Gildenchat</div>';

    	$html .= '</div></li>';
    	
    	foreach($arrOpenConversations as $key => $val){
    		$arrUser = $this->pdh->get("chat_conversations", "user", array($key));
    		if (count($arrUser) <= 2) continue;
    		$strTitle = $this->pdh->get("chat_conversations", "title", array($key));
    		$html .= '<li><div onclick="EQdkpChat.openNewChat(\''.$key.'\', \''.$strTitle.'\', new Array(\''.implode("','",$arrUser).'\'));" class="hand">';
    		
    		$html .= '<div class="chat_user_avatar"><i class="fa fa-group fa-lg floatLeft"></i></div>';
    		$html .= '<div class="chat_username">'.$strTitle.'</div>';

    		$html .= '</div><div class="chat_last_online"><i class="fa fa-archive hand" onclick="EQdkpChat.archiveGroupConversation(this, \''.$key.'\')"></i></div>';

    		$html .= '</li>';
    	}

    	$html .= '<li class="chatOnlineSeperator">&nbsp;</li>';
    	
    	foreach($this->data as $user_id => $val){
    		$html .= '<li>';
    		$html .= '<div onclick="EQdkpChat.openNewChat(\''.$this->generate_key(array($this->user->id, $user_id)).'\', \''.$this->pdh->get('user', 'name', array($user_id)).'\', new Array(\''.$user_id.'\'));" class="hand">';
    		
    		$html .= '<div class="chat_user_avatar">'.$this->pdh->geth('user', 'avatarimglink', array($user_id)).'</div>';
    		$html .= '<div class="chat_username">'.$this->pdh->get('user', 'name', array($user_id)).'</div>';
    		if ($val['online']){
    			$html .= '<div class="chat_last_online"><i class="eqdkp-icon-online"></i></div>';
    		} else {
    			$html .= '<div class="chat_last_online">'.$this->time->nice_date($val['lastvisit'], 60*60*24*7).'</div>';
    		}
    		$html .= '</div></li>';
    	}
    	
    	$html .= '</ul>';
    	return $html;
    }
    

    private function generate_key($arrUsers){
    	asort($arrUsers);
    	return md5(implode(",", $arrUsers));
    }

  } //end class
} //end if class not exists

?>