<?php
/*
 * Project:     EQdkp guildrequest
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2012-10-13 22:48:23 +0200 (Sa, 13. Okt 2012) $
 * -----------------------------------------------------------------------
 * @author      $Author: godmod $
 * @copyright   2008-2011 Aderyn
 * @link        http://eqdkp-plus.com
 * @package     guildrequest
 * @version     $Rev: 12273 $
 *
 * $Id: archive.php 12273 2012-10-13 20:48:23Z godmod $
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
                GROUP BY s.session_user_id
                ORDER BY s.session_current DESC';
  	$result = $this->db->prepare($sql)->execute($this->time->time-600);
  	$intOnlineCount = 0;
  	if ($result)
  	{
  		$intOnlineCount = $result->numRows;
  		// add row by row to local copy
  		while ($row = $result->fetchAssoc())
  		{
  			$user_id = (int)$row['session_user_id'];
  			
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
      'display'       => true
    ));

  }
  
  private function generate_key($arrUsers){
  	asort($arrUsers);
  	return md5(implode(",", $arrUsers));
  }
}
?>