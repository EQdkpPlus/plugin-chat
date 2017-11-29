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
class chathistory_pageobject extends pageobject
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
    
    if(!$this->user->is_signedin()){
    	$this->user->check_auth('u_chat_something');
    }

    $handler = array();
    parent::__construct('u_chat_view', $handler);

    $this->process();
  }
  
  public function display()
  {
  	$this->bbcode->SetSmiliePath($this->server_path.'images/smilies');
  	
  	//Get all conversations, sorted by last message date
  	$arrConversations = $this->pdh->get("chat_conversations", "id_list");
  	$arrMyConversations = array();
  	foreach($arrConversations as $key){
  		$arrUser = $this->pdh->get("chat_conversations", "user", array($key));
  		if (in_array($this->user->id, $arrUser)) $arrMyConversations[] = $key;
  	}
  	
  	$firstKey = false;
  	
  	if (count($arrMyConversations)){
	  	//Get latest Messages
	  	$objQuery = $this->db->prepare("SELECT m1.*
		FROM __chat_messages m1 LEFT JOIN __chat_messages m2
		 ON (m1.conversation_key  = m2.conversation_key  AND m1.id < m2.id)
		WHERE m2.id IS NULL AND m1.conversation_key :in ORDER BY date DESC")->in($arrMyConversations)->execute();
		
	  	if ($objQuery){
	  		//Get last visits	  		
	  		$objLatestVisits = $this->db->prepare("SELECT * FROM __chat_conversation_lastvisit WHERE conversation_key :in AND user_id=?")->in($arrMyConversations)->execute($this->user->id);
	  		$arrLatestVisits = array();
	  		if($objLatestVisits){
	  			while($row = $objLatestVisits->fetchAssoc()){
	  				$arrLatestVisits[$row['conversation_key']] = (int)$row['date'];
	  			}
	  		}
	  		
	  		while($row = $objQuery->fetchAssoc()){
	  			if ($firstKey === false) $firstKey = $row['conversation_key'];
	  			
	  			$arrUser = $this->pdh->get("chat_conversations", "user", array($row['conversation_key']));
	  			if (count($arrUser) <= 2){
	  				foreach($arrUser as $user_id){
	  					if ($this->user->id == $user_id) continue;
	  					$strAvatar = $this->pdh->geth('user', 'avatarimglink', array((int)$user_id));
	  				}
	  			} else {
	  				$strAvatar = '<i class="fa fa-group fa-4x"></i>';
	  			}
	  			
	  			//Get Unread data
	  			$blnUnread = false;
	  			if (count($arrUser) <= 2){
	  				if ((int)$row['user_id'] != $this->user->id && (int)$row['reed'] == 0) {
	  					$blnUnread = true;
	  				}
	  			} else {			
	  				if ((int)$row['date'] > $arrLatestVisits[$row['conversation_key']]) {
	  					$blnUnread = true;
	  				}
	  			}
	  			
	  			$this->tpl->assign_block_vars("chat_conversation_row", array(
	  				'TITLE'		=> $this->pdh->get("chat_conversations", "title", array($row['conversation_key'])),
	  				'KEY'		=> $row['conversation_key'],
	  				'avatar'	=> $strAvatar,
  					'user_id'	=> (int)$row['user_id'],
  					'text'		=> nl2br($this->bbcode->MyEmoticons($row['text'])),
  					'reed'		=> ((int)$row['user_id'] == $this->user->id) ? 1 : (int)$row['reed'],
  					'date'		=> $this->time->user_date((int)$row['date'], true),
  					'timestamp'	=> (int)$row['date'],
	  				'LAST_BY_ME'=> ((int)$row['user_id'] == $this->user->id) ? true : false,
	  				'S_UNREAD'  => $blnUnread,
	  				'U_LINK'	=> $this->routing->build("chathistory", $this->pdh->get("chat_conversations", "title", array($row['conversation_key'])), $this->pdh->get("chat_conversations", "id", array($row['conversation_key'])))
	  			));
	  		}
	  	}
  	}
  	if ($this->url_id != "") {
  		$firstKey = $this->pdh->get("chat_conversations", "key", array($this->url_id));
  	}
  	
  	$rows = 0;
  	if ($firstKey !== false){
  		$arrHTML = array();
  		$lastElement = false;
		$objQuery = $this->db->prepare("SELECT * FROM __chat_messages WHERE conversation_key=? ORDER BY date DESC")->limit(20)->execute($firstKey);
		if ($objQuery){
			$rows = $objQuery->numRows;
			
			$arrUser = $this->pdh->get("chat_conversations", "user", array($firstKey));
			$lastvisit = false;
			if (count($arrUser) > 2){
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
  									<div class="chatMessage">'.nl2br($this->bbcode->MyEmoticons($row['text'])).'</div><div class="clear"></div>
  								</div>
  							</div>';
			}
		}
		
		$arrHTML = array_reverse($arrHTML);
  		$this->tpl->assign_vars(array(
  				"CHAT_CONTENT"		=> implode("", $arrHTML),
  				"CHAT_LAST_MESSAGE" => cut_text($lastElement['date'], 50),
  				"CHAT_LASTBYME"		=> ((int)$lastElement['user_id'] == $this->user->id) ? 1 : 0,
  				
  		));
  	}
  	
  	$strChatTitle = $this->pdh->get("chat_conversations", "title", array($firstKey));
  	$this->tpl->assign_vars(array(
  		'CHAT_MORE_POSTS'	=> ($rows == 20) ? 'true' : 'false',
  		'CHAT_KEY'			=> $firstKey,
  		'CHAT_TITLE'		=> (strlen($strChatTitle)) ? $strChatTitle : " - ",
  		'CHAT_COUNT'		=> count($this->pdh->get("chat_conversations", "user", array($firstKey))),
  	));
  	
  	
	
    // -- EQDKP ---------------------------------------------------------------
    $this->core->set_vars(array (
      'page_title'    => $this->user->lang('chat_conversation'),
      'template_path' => $this->pm->get_data('chat', 'template_path'),
      'template_file' => 'chathistory.html',
    		'page_path'			=> [
    				['title'=>$this->user->lang('chat'), 'url'=> $this->routing->build('chat')],
    				['title'=>(strlen($strChatTitle)) ? $strChatTitle : " - ", 'url'=> ' '],
    				
    		],
      'display'       => true
    ));

  }
}
?>