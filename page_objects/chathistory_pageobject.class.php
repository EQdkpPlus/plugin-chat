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
class chathistory_pageobject extends pageobject
{
  /**
   * __dependencies
   * Get module dependencies
   */
  public static function __shortcuts()
  {
    $shortcuts = array('pm', 'user', 'core', 'in', 'pdh', 'time', 'tpl', 'html', 'routing', 'db');
   	return array_merge(parent::__shortcuts(), $shortcuts);
  }  
  
  private $data = array();

  /**
   * Constructor
   */
  public function __construct()
  {
    // plugin installed?
    if (!$this->pm->check('chat', PLUGIN_INSTALLED))
      message_die($this->user->lang('gr_plugin_not_installed'));

    $handler = array();
    parent::__construct('u_chat_view', $handler);

    $this->process();
  }
  
  public function display()
  {
	
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
		FROM eqdkp20_chat_messages m1 LEFT JOIN eqdkp20_chat_messages m2
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
  					'text'		=> $row['text'],
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
				$arrHTML[] = '<div class="chatPost'.((!$reed) ? ' chatNewPost' : '').'" data-post-id="'.(int)$row['id'].'">
  								<div class="chatTime">'.$this->time->user_date((int)$row['date'], true).'</div>
  								<div class="chatAvatar" title="'.$strUsername.'">'.$strAvatar.'</div>
  								<div class="chatMessage">'.$row['text'].'</div><div class="clear"></div>
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
  	
  	$this->tpl->assign_vars(array(
  		'CHAT_MORE_POSTS'	=> ($rows == 20) ? 'true' : 'false',
  		'CHAT_KEY'			=> $firstKey,
  		'CHAT_TITLE'		=> $this->pdh->get("chat_conversations", "title", array($firstKey)),
  		'CHAT_COUNT'		=> count($this->pdh->get("chat_conversations", "user", array($firstKey))),
  	));
  	
  	
	
    // -- EQDKP ---------------------------------------------------------------
    $this->core->set_vars(array (
      'page_title'    => $this->user->lang('gr_add'),
      'template_path' => $this->pm->get_data('chat', 'template_path'),
      'template_file' => 'chathistory.html',
      'display'       => true
    ));

  }
}
?>