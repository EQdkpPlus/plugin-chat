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

// EQdkp required files/vars
class chat_pageobject extends pageobject
{
  
  private $data = array();

  /**
   * Constructor
   */
  public function __construct()
  {
    // plugin installed?
    if (!$this->pm->check('chat', PLUGIN_INSTALLED))
      message_die($this->user->lang('gr_plugin_not_installed'));
    
    $this->user->check_auth('u_chat_view');

    $handler = array();
    parent::__construct('u_chat_view', $handler);

    $this->process();
  }
  
  public function display()
  {
  	$this->bbcode->SetSmiliePath($this->server_path.'images/smilies');
  	$firstKey = "guildchat";
 	
  	$rows = 0;
  	if ($firstKey !== false){
  		$arrHTML = array();
  		$lastElement = false;
		$objQuery = $this->db->prepare("SELECT * FROM __chat_messages WHERE conversation_key=? ORDER BY date DESC")->limit(20)->execute($firstKey);
		if ($objQuery){
			$rows = $objQuery->numRows;
			
			$arrUser = $this->pdh->get("chat_conversations", "user", array($firstKey));

			$lastvisit = false;
			if (is_array($arrUser) && count($arrUser) > 2){
				$lastvisit = $this->pdh->get("chat_conversation_lastvisit", "lastVisit", array($this->user->id, $firstKey));
			}
				
			while($row = $objQuery->fetchAssoc()){
				if ($lastElement === false) $lastElement = $row;
				$reed = ($lastvisit === false) ? (((int)$row['user_id'] != $this->user->id && (int)$row['reed'] == 0) ? false : true) : (((int)$row['date'] > $lastvisit) ? false : true);
				
				$strAvatar = $this->pdh->geth('user', 'avatarimglink', array((int)$row['user_id']));
				$strUsername = $this->pdh->get('user', 'name', array((int)$row['user_id']));
				$mine = ((int)$row['user_id'] === $this->user->id) ? ' mine' : '';
				$arrHTML[] = '<div class="chatPost'.((!$reed) ? ' chatNewPost' : '').$mine.'" data-post-id="'.(int)$row['id'].'">
  								<div class="chatAvatar" title="'.$strUsername.'"><a href="'.$this->routing->build('user', $strUsername, 'u'.$row['user_id']).'">'.$strAvatar.'</a></div>
  								<div class="chatMsgContainer">
  									<div class="chatUsername">'.$strUsername.'</div>
  									<div class="chatTime">'.$this->time->user_date((int)$row['date'], true).'</div>
  									<div class="chatMessage">'.(($this->user->check_auth('u_chat_mod_pub', false)) ? '<span class="chatDeleteContainer"><i class="hand fa fa-times-circle icon-grey" title="'.$this->user->lang('delete').'" onclick="EQdkpChat.deleteMessage('.(int)$row['id'].')"></i></span>' : '').nl2br($this->bbcode->MyEmoticons($row['text'])).'</div><div class="clear"></div>
  								</div>
  							</div>';
			}
		}
		
		$arrHTML = array_reverse($arrHTML);
  		$this->tpl->assign_vars(array(
  				"CHAT_CONTENT"		=> implode("", $arrHTML),
  				"CHAT_LAST_MESSAGE" => $lastElement['date'],
  				"CHAT_LASTBYME"		=> ((int)$lastElement['user_id'] == $this->user->id) ? 1 : 0,
  				
  		));
  	}
  	
  	
  	
  	//Online User
  	
  	// read all chat_online entries from db
  	$sql = 'SELECT s.session_user_id, s.session_current
                FROM __sessions s
      			WHERE s.session_user_id != -1
  				AND s.session_current > ?
                ORDER BY s.session_current DESC';
  	$result = $this->db->prepare($sql)->execute($this->time->time-600);
  	$intOnlineCount = 0;
  	$arrDone = array();
  	if ($result)
  	{
  		// add row by row to local copy
  		while ($row = $result->fetchAssoc())
  		{
  			$user_id = (int)$row['session_user_id'];
  			if(in_array($user_id, $arrDone)) continue;
  			$arrDone[] = $user_id;
  			
  			$intOnlineCount++;
  			
  			$html = '<li>';
  			if ($user_id != $this->user->id){
  				$html .= '<div onclick="EQdkpChat.openNewChat(\''.$this->generate_key(array($this->user->id, $user_id)).'\', \''.$this->pdh->get('user', 'name', array($user_id)).'\', new Array(\''.$user_id.'\'));" class="hand">';
  			} else {
  				$html .= '<div>';
  			}
  				
  			$html .= '<div class="chat_user_avatar">'.$this->pdh->geth('user', 'avatarimglink', array($user_id)).'</div>';
  			$html .= '<div class="chat_username">'.$this->pdh->get('user', 'name', array($user_id)).'</div>';
  			$html .= '</div>
  					<div class="clear"></div>
  					</li>';
  			$this->tpl->assign_block_vars("chat_online_row", array(
  				'USER' => $html,
  			));
  		}
  	}
  	
  	$this->tpl->assign_vars(array(
  		'CHAT_MORE_POSTS'	=> ($rows == 20) ? 'true' : 'false',
  		'CHAT_KEY'			=> $firstKey,
  		'CHAT_ONLINE_COUNT' => $intOnlineCount,
  	));
	
    // -- EQDKP ---------------------------------------------------------------
    $this->core->set_vars(array (
      'page_title'    => $this->user->lang('chat_guildchat'),
      'template_path' => $this->pm->get_data('chat', 'template_path'),
      'template_file' => 'chat.html',
    		'page_path'			=> [
    				['title'=>$this->user->lang('chat'), 'url'=>' '],
    		],
      'display'       => true
    ));

  }
  
  private function generate_key($arrUsers){
  	asort($arrUsers);
  	return md5(implode(",", $arrUsers));
  }
}
?>