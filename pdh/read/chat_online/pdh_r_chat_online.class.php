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
  | pdh_r_chat_online
  +--------------------------------------------------------------------------*/
if (!class_exists('pdh_r_chat_online'))
{
  class pdh_r_chat_online extends pdh_r_generic
  {

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
                FROM __sessions s
      			WHERE s.session_user_id != -1
      			AND s.session_current > ?
                ORDER BY s.session_current DESC';
      $result = $this->db->prepare($sql)->execute($this->time->time-600);
      if ($result)
      {

        // add row by row to local copy
        while ($row = $result->fetchAssoc())
        {
          if(isset( $this->data[(int)$row['session_user_id']])) continue;
          
          $this->data[(int)$row['session_user_id']] = array(
          		'online'	=> true,
          		'lastvisit' => (int)$row['session_current'],
          );     
        }
      }
      
		$userIDs = $this->pdh->get('user', 'id_list');
		$userIDs = $this->pdh->sort($userIDs, 'user', 'name', 'asc');
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
    	$html .= '<div class="chat_username">'.$this->user->lang('chat_guildchat').'</div>';

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
    
    public function get_online_user(){
    	$arrOut = array();
    	foreach($this->data as $user_id => $val){
    		if ($val['online']){
    			$arrOut[] = $this->pdh->get('user', 'name', array($user_id));
    		}
    	}
    	return $arrOut;
    }
    

    private function generate_key($arrUsers){
    	asort($arrUsers);
    	return md5(implode(",", $arrUsers));
    }

  } //end class
} //end if class not exists

?>