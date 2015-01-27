<?php
/*
 * Project:     EQdkp Plus Chat
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2012-06-22 20:20:07 +0200 (Fr, 22. Jun 2012) $
 * -----------------------------------------------------------------------
 * @author      $Author: godmod $
 * @copyright   2013 GodMod
 * @link        http://eqdkp-plus.eu
 * @package     Chat
 * @version     $Rev: 11830 $
 *
 * $Id: shoutbox.php 11830 2012-06-22 18:20:07Z godmod $
 */
 
define('EQDKP_INC', true);
$eqdkp_root_path = './../../';
include_once($eqdkp_root_path.'common.php');

class AjaxChat extends page_generic {

	public function __construct(){
		$this->user->check_auth('u_chat_view');
		register("pm");
		$handler = array(
			'userlist'			=> array('process' => 'get_userlist'),
			'openConversation'	=> array('process' => 'openConversation'),
			'closeConversation'	=> array('process' => 'closeConversation'),
			'loadOpenConversations'	=> array('process' => 'loadOpenConversations'),
			'save'				=> array('process' => 'saveMessage'),
			'markRead'			=> array('process' => 'markRead'),
			'checkNew'			=> array('process' => 'checkNew'),
			'getUser'			=> array('process' => 'getUser'),
			'addUser'			=> array('process' => 'addUser'),
			'changeTitle'		=> array('process' => 'changeTitle'),
			'loadLatestMessages'=> array('process' => 'loadLatestMessages'),
			'loadOlderMessages'=> array('process' => 'loadOlderMessages'),
			'archiveGroupConv'	=> array('process' => 'archiveGroupConversation'),
			'unreadTooltip'		=> array('process' => 'unreadTooltip'),
			'leaveConversation'	=> array('process' => 'leaveConversation'),
		);
		parent::__construct(false, $handler);
		$this->process();
	}
	
	public function get_userlist(){
		header('Content-type: application/json; charset=UTF-8');
		$htmlUserlist = $this->pdh->geth('chat_online', 'userlist', array());
		$arrOut = array(
			'html'	=> $htmlUserlist,
			'count' => $this->pdh->get('chat_online', 'online_user_count', array()),
			'unread' => $this->getUnreadChats(),
			'user' => $this->pdh->get('chat_online', 'online_user', array()),
		);
		echo json_encode($arrOut);
		die();
	}
	
	public function openConversation(){
		header('Content-type: application/json; charset=UTF-8');
		$arrOut = array();
		$strKey = $this->in->get('key');
		if ($strKey != ""){
			$intUser = $this->pdh->put("chat_open_conversations", "openConversation", array($strKey, $this->user->id, 1, $this->in->getArray('user', 'int')));
			$this->pdh->process_hook_queue();
			//ToDo: check permissions
			$arrMessages = $this->pdh->get('chat_messages', 'latestOrUnread', array($strKey));
			$arrLastMessage = $arrMessages[0];
			$arrMessages = array_reverse($arrMessages);

			$arrOut = array(
				'messages' => $arrMessages,
				'lasttime' => $arrLastMessage['timestamp'],
				'lastbyme' => ($arrLastMessage['user_id'] == $this->user->id) ? 1 : 0,
			);
		}
		echo json_encode($arrOut);
		die();
	}
	
	public function loadLatestMessages(){
		header('Content-type: application/json; charset=UTF-8');
		$arrOut = array();
		
		$arrMessages = $this->pdh->get('chat_messages', 'latestOrUnread', array($this->in->get('key')));
		$arrLastMessage = $arrMessages[0];
		$arrMessages = array_reverse($arrMessages);
			
		$arrOut = array(
				'messages' => $arrMessages,
				'lasttime' => $arrLastMessage['timestamp'],
				'lastbyme' => ($arrLastMessage['user_id'] == $this->user->id) ? 1 : 0,
		);
		echo json_encode($arrOut);
		die();
	}
	
	public function closeConversation(){
		if ($this->in->get('key') != ""){
			$this->pdh->put("chat_open_conversations", "closeConversation", array($this->in->get('key'), $this->user->id));
			$this->pdh->process_hook_queue();
		}
	}
	
	public function loadOpenConversations(){
		header('Content-type: application/json; charset=UTF-8');
		$arrOpen = $this->pdh->get('chat_open_conversations', 'openUserConversations', array($this->user->id));
		$arrOut = array();
		foreach ($arrOpen as $val){
			if ($val['open']){	
				$arrOut[$val['conversation_key']] = array(
					'key'	=> $val['conversation_key'],
					'title' => $this->pdh->get('chat_conversations', 'title', array($val['conversation_key'])),
					'count' => count($this->pdh->get('chat_conversations', 'user', array($val['conversation_key']))),
				);
			}
		}
		echo json_encode($arrOut);
		die();
	}
	
	public function saveMessage(){
		$strMessage = $this->in->get('txt');
		$strKey = $this->in->get('key');
		if ($strMessage != "" && $strKey != ""){
			$arrUser = $this->pdh->get("chat_conversations", "user", array($strKey));
			if($strKey != 'guildchat' && !in_array($this->user->id, $arrUser)) return false;
			
			$this->pdh->put('chat_messages', 'addMessage', array($strKey, $strMessage));

			if (count($arrUser) <= 2){
				$this->pdh->put("chat_messages", "markRead", array($strKey));
			} else {
				$this->pdh->put("chat_conversation_lastvisit", "setLastVisit", array($strKey));
			}
			
			//Open Windows for other users
			if ($strKey != "guildchat"){
				$arrUser = $this->pdh->get("chat_conversations", "user", array($strKey));
				foreach($arrUser as $user_id){
					if ($user_id === $this->user->id) continue;
					if (!$this->pdh->get('chat_open_conversations', 'is_open', array($user_id, $strKey))){
						$this->pdh->put("chat_open_conversations", "openConversation", array($strKey, $user_id));
					}
				}
			}
			$this->pdh->process_hook_queue();
		}
	}
	
	public function checkNew(){
		$arrOpen = $this->in->getArray("open","string");
		$arrTimestamps = $this->in->getArray("tsp", "int");
		unset($arrOpen[0]); unset($arrTimestamps[0]);
		$arrOut = $arrMessages = $arrNewMessages = $arrNewWindows = array();
		if(count($arrOpen)) {
			foreach($arrOpen as $k => $key){
				$arrKey[$key] = $arrTimestamps[$k];
			}		
			
			$intMin = min($arrTimestamps);
			$arrResult = $this->db->prepare("SELECT * FROM __chat_messages WHERE conversation_key :in AND date > ?")->in($arrOpen)->execute($intMin);			
			if ($arrResult){
				$objLatestVisits = $this->db->prepare("SELECT * FROM __chat_conversation_lastvisit WHERE conversation_key :in AND user_id=?")->in($arrOpen)->execute($this->user->id);
				$arrLatestVisits = array();
				if($arrLatestVisits){
					while($row = $objLatestVisits->fetchAssoc()){
						$arrLatestVisits[$row['conversation_key']] = (int)$row['date'];
					}
				}
				$objUsers = $this->db->prepare("SELECT * FROM __chat_conversations WHERE conversation_key :in")->in($arrOpen)->execute();
				$arrUserCount = array();
				if($objUsers){
					while($row = $objUsers->fetchAssoc()){
						$arrUserCount[$row['conversation_key']] = count(unserialize($row['user']));
					}
				}
				$this->bbcode->SetSmiliePath($this->server_path.'images/smilies');
				while($row = $arrResult->fetchAssoc()){
					if ($row['date'] <= $arrKey[$row['conversation_key']]) continue;
					if (!isset($arrNewMessages[$row['conversation_key']])){
						$arrNewMessages[$row['conversation_key']] = array('messages' => array(), 'lasttime' => 0, 'lastbyme' => 0);
					}
					
					$reed = ($arrUserCount[$row['conversation_key']] <= 2) ? (((int)$row['user_id'] == $this->user->id) ? 1 : (int)$row['reed']) : (($arrLatestVisits[$row['conversation_key']] >= (int)$row['date']) ? 1 : 0);
					
					$arrNewMessages[$row['conversation_key']]['messages'][] = array(
						'id'		=> $row['id'],
						'user_id'	=> (int)$row['user_id'],
						'username'	=> $this->pdh->get('user', 'name', array((int)$row['user_id'])),
						'text'		=> nl2br($this->bbcode->MyEmoticons($row['text'])),
						'reed'		=> $reed,
						'avatar'	=> $this->pdh->geth('user', 'avatarimglink', array((int)$row['user_id'])),
						'profile'	=> $this->routing->build('user', $this->pdh->get('user', 'name', array((int)$row['user_id'])), 'u'.$row['user_id']),
						'date'		=> $this->time->user_date((int)$row['date'], true),
						'timestamp'	=> (int)$row['date'],
					);
					$arrNewMessages[$row['conversation_key']]['lasttime'] = (int)$row['date'];
					$arrNewMessages[$row['conversation_key']]['lastbyme'] = ((int)$row['user_id'] == $this->user->id) ? 1 : 0;
				}
			}
		}
		
		
		//Open new Conversations
		$arrOpenConvs = $this->pdh->get('chat_open_conversations', 'openUserConversations', array($this->user->id));
		$arrNewOpen = array();
		foreach ($arrOpenConvs as $val){
			if (!$val['open']) continue;
			if (!in_array($val['conversation_key'], $arrOpen)) $arrNewOpen[] = array(
				'key'	=> $val['conversation_key'],
				'title' => $this->pdh->get("chat_conversations", "title", array($val['conversation_key'])),
				'count'	=> count($this->pdh->get("chat_conversations", "user", array($val['conversation_key']))),				
			);
		}
		
		//Check if he read my message
		$arrReed = array();
		if (count($arrOpen)){
			$objQuery = $this->db->prepare("SELECT m1.*
	FROM __chat_messages m1 LEFT JOIN __chat_messages m2
	 ON (m1.conversation_key  = m2.conversation_key  AND m1.id < m2.id AND m1.user_id=m2.user_id)
	WHERE m2.id IS NULL AND m1.user_id=? AND m1.conversation_key :in")->in($arrOpen)->execute($this->user->id);
			if ($objQuery){
				while($row = $objQuery->fetchAssoc()){
					if (($row['reed']) == 1){
						$arrReed[] = $row['conversation_key'];
					}
				}
			}
		}

				
		$arrOut = array(
			'new_messages' => $arrNewMessages,
			'new_windows'  => $arrNewOpen,
			'new_reed'	   => $arrReed,
		);
		
		header('Content-type: application/json; charset=UTF-8');
		echo json_encode($arrOut);
		die();
	}
	
	public function markRead(){
		$strKey = $this->in->get('key');
		if ($strKey != ""){
			$arrUser = $this->pdh->get("chat_conversations", "user", array($strKey));
			if (count($arrUser) <= 2){
				$this->pdh->put("chat_messages", "markRead", array($strKey));
			} else {
				$this->pdh->put("chat_conversation_lastvisit", "setLastVisit", array($strKey));
			}
			$this->pdh->process_hook_queue();
		}
	}
	
	public function getUser(){
		header('Content-type: application/json; charset=UTF-8');
		$strKey = $this->in->get('key');
		$arrUser = $arrPrepopulate = array();
		if ($strKey != ""){
			$arrRecentUser = $this->pdh->get("chat_conversations", "user", array($strKey));
			foreach($arrRecentUser as $user_id){
				if ($user_id == $this->user->id) continue;
				$arrPrepopulate[] = array(
					'id' => intval($user_id),
					'name' => $this->pdh->get('user', 'name', array(intval($user_id))),
				);
			}
			$arrUserIDs = $this->pdh->get('user', 'id_list');
			foreach($arrUserIDs as $user_id){
				if ($user_id == $this->user->id) continue;
				$arrUser[] = array(
						'id' => intval($user_id),
						'name' => $this->pdh->get('user', 'name', array(intval($user_id))),
				);
			}
		}
		
		echo json_encode(array('user' => $arrUser, 'prepopulate' => $arrPrepopulate));
		exit();
	}
	
	public function addUser(){
		header('Content-type: application/json; charset=UTF-8');
		
		$arrUser = explode(",", $this->in->get('user'));
		$arrUser[] = $this->user->id;
		
		if ($this->in->get('user') != "" && count($arrUser) > 1){
			asort($arrUser);
			$strKey = md5(implode(",", $arrUser));
			
			$this->pdh->put("chat_open_conversations", "openConversation", array($strKey, $this->user->id, 1, $arrUser));
			$this->pdh->process_hook_queue();
			//ToDo: get latest chats, check permissions
			$arrMessages = $this->pdh->get('chat_messages', 'latestOrUnread', array($strKey));
			$arrLastMessage = $arrMessages[0];
			$arrMessages = array_reverse($arrMessages);
				
			$arrOut = array(
					'key'	   => $strKey,
					'title'	   => $this->pdh->get('chat_conversations', 'title', array($strKey)),
					'count'	   => count($arrUser),
					'messages' => $arrMessages,
					'lasttime' => $arrLastMessage['timestamp'],
					'lastbyme' => ($arrLastMessage['user_id'] == $this->user->id) ? 1 : 0,
			);
		} else {
			$arrOut['count'] = 0;
		}

		echo json_encode($arrOut);
		exit();
	}
	
	public function archiveGroupConversation(){
		$strKey = $this->in->get('key');
		$this->pdh->put("chat_open_conversations", "archiveConversation", array($strKey, $this->user->id));
	}
	
	public function leaveConversation(){
		$strKey = $this->in->get('key');
		$arrUser = $this->pdh->get("chat_conversations", "user", array($strKey));
		$key = array_search($this->user->id, $arrUser);
		if($key !== false){
			unset($arrUser[$key]);
			asort($arrUser);
			$strNewKey = md5(implode(",", $arrUser));
			//Open Conversations with old key
			$arrOpenUser = $this->pdh->get("chat_open_conversations", "openConversationsWithkey", array($strKey));
			$key = array_search($this->user->id, $arrOpenUser);
			if($key !== false) unset($arrOpenUser[$key]);
			//Close old windows & open new windows
			foreach($arrOpenUser as $intUserID){
				$this->pdh->put("chat_open_conversations", "closeConversation", array($strKey, $intUserID));
				$this->pdh->put("chat_open_conversations", "openConversation", array($strNewKey, $intUserID, 1, $arrUser));
				$this->pdh->process_hook_queue();
			}
		}
	}
	
	public function changeTitle(){
		header('Content-type: application/json; charset=UTF-8');
		$strKey = $this->in->get('key');
		$strTitle = $this->in->get('title');
		$arrOut = array();
		if ($strKey != "" && $strTitle != ""){
			$this->pdh->put("chat_conversations", "changeTitle", array($strKey, $strTitle));
			$this->pdh->process_hook_queue();
		}
		$arrOut['title'] = $this->pdh->get("chat_conversations", "title", array($strKey));
		echo json_encode($arrOut);
		exit();
	}
	
	public function loadOlderMessages(){
		header('Content-type: application/json; charset=UTF-8');
		$strKey = $this->in->get('key');
		$intOffset = $this->in->get("offset", 0);
		
		$arrHTML = array();
		$objQuery = $this->db->prepare("SELECT * FROM __chat_messages WHERE conversation_key=? ORDER BY date DESC")->limit(20, $intOffset)->execute($strKey);
		if ($objQuery){
			$arrOut['count'] = $objQuery->numRows;
			
			$arrUser = $this->pdh->get("chat_conversations", "user", array($strKey));
			$lastvisit = false;
			if (count($arrUser) > 2){
				$lastvisit = $this->pdh->get("chat_conversation_lastvisit", "lastVisit", array($this->user->id, $strKey));
			}
			$this->bbcode->SetSmiliePath($this->server_path.'images/smilies');
			
			while($row = $objQuery->fetchAssoc()){
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
		$arrOut['content'] = implode("", $arrHTML);
		echo json_encode($arrOut);
		exit();
	}
	
	protected function getUnreadChats($blnGetKeysOnly=true){
		$unread = array();
		$arrUnreadData = array();
		
		//Get all conversations, sorted by last message date
		$arrConversations = $this->pdh->get("chat_conversations", "id_list");
		$arrMyConversations = array();
		$arrUserCount = array();
		foreach($arrConversations as $key){
			$arrUser = $this->pdh->get("chat_conversations", "user", array($key));
			if (in_array($this->user->id, $arrUser)) $arrMyConversations[] = $key;
			$arrUserCount[$key] = count($arrUser);
		}
		
		if (count($arrMyConversations)){
			//Get latest Messages
			$objQuery = $this->db->prepare("SELECT m1.*
		FROM __chat_messages m1 LEFT JOIN __chat_messages m2
		 ON (m1.conversation_key  = m2.conversation_key  AND m1.id < m2.id)
		WHERE m2.id IS NULL AND m1.conversation_key :in ORDER BY date DESC")->in($arrMyConversations)->execute();
						
			if ($objQuery){
				$objLatestVisits = $this->db->prepare("SELECT * FROM __chat_conversation_lastvisit WHERE conversation_key :in AND user_id=?")->in($arrMyConversations)->execute($this->user->id);
				$arrLatestVisits = array();
				if($objLatestVisits){
					while($row = $objLatestVisits->fetchAssoc()){
						$arrLatestVisits[$row['conversation_key']] = (int)$row['date'];
					}
				}
				
				while($row = $objQuery->fetchAssoc()){			
					if ($arrUserCount[$row['conversation_key']] <= 2){
						if ((int)$row['user_id'] != $this->user->id && (int)$row['reed'] == 0) {
							$unread[] = $row['conversation_key'];
							$arrUnreadData[$row['conversation_key']] = $row;
						}
					} else {

						if ((int)$row['date'] > $arrLatestVisits[$row['conversation_key']]) {
							$unread[] = $row['conversation_key'];
							$arrUnreadData[$row['conversation_key']] = $row;
						}
					}
				}
			}
		}
		return (($blnGetKeysOnly) ? $unread : $arrUnreadData);
	}
	
	public function unreadTooltip(){
		header('Content-type: text/html; charset=UTF-8');
		$arrUnread = $this->getUnreadChats(false);
		if (count($arrUnread) == 0) {
			echo '<div class="chatTooltipRemove">'.$this->user->lang('chat_no_unread').'</div>';
			die();
		}
		
		$strOut = "";
		foreach($arrUnread as $key => $row){
			$strLink = $this->routing->build("chathistory", $this->pdh->get("chat_conversations", "title", array($key)), $this->pdh->get("chat_conversations", "id", array($key)));
			
			$arrUser = $this->pdh->get("chat_conversations", "user", array($key));
			if (count($arrUser) <= 2){
				foreach($arrUser as $user_id){
					if ($this->user->id == $user_id) continue;
					$strAvatar = $this->pdh->geth('user', 'avatarimglink', array((int)$user_id));
				}
			} else {
				$strAvatar = '<i class="fa fa-group fa-4x"></i>';
			}
			
			$strOut .='<li class="chatTooltipRemove"><a href="'.$strLink.'">
				<div class="chatTooltipAvatar">
				'.$strAvatar.'
				</div>
				<div class="chatTooltipDate">
				'.$this->time->user_date((int)$row['date'], true).'
				</div>
				<div class="chatTooltipTitle">
				'.$this->pdh->get("chat_conversations", "title", array($key)).'
				</div>
				<div class="chatTooltipLastMessage">';
				if (((int)$row['user_id'] == $this->user->id)){
					$strOut .='<i class="fa fa-reply"></i>';
				}
				$strOut .= cut_text($row['text'], 50).'</div>
				<div class="clear"></div>
			</a></li>';
		}
		echo $strOut;
		die();
	}
	
	public function display(){
		
	}
}

registry::register('AjaxChat');
?>