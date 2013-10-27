<?php
/*
 * Project:     EQdkp chat
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2012-11-11 18:36:16 +0100 (So, 11. Nov 2012) $
 * -----------------------------------------------------------------------
 * @author      $Author: godmod $
 * @copyright   2008-2011 Aderyn
 * @link        http://eqdkp-plus.com
 * @package     chat
 * @version     $Rev: 12434 $
 *
 * $Id: chat_portal_hook.class.php 12434 2012-11-11 17:36:16Z godmod $
 */

if (!defined('EQDKP_INC'))
{
  header('HTTP/1.0 404 Not Found');exit;
}


/*+----------------------------------------------------------------------------
  | chat_portal_hook
  +--------------------------------------------------------------------------*/
if (!class_exists('chat_portal_hook'))
{
  class chat_portal_hook extends gen_class
  {
    /* List of dependencies */
    public static $shortcuts = array('user', 'pdh', 'tpl', 'core');

	/**
    * hook_portal
    * Do the hook 'portal'
    *
    * @return array
    */
	public function portal()
	{		
		if ($this->user->check_auth("u_chat_view", false) && $this->core->header_format == 'full' && $this->user->is_signedin()){
			$this->tpl->js_file($this->root_path.'plugins/chat/includes/js/jquery.tokeninput.js');
			$this->tpl->js_file($this->root_path.'plugins/chat/includes/js/chat.js');
			$this->tpl->css_file($this->root_path.'plugins/chat/includes/css/chat.css');			
			$this->tpl->add_js("EQdkpChat.init();
				$('.chat-tooltip-trigger').on('click', function(event){
					$('#chat-tooltip').show('fast');
					$('.chatTooltipRemove').remove();
					$('.chatTooltipUnread').show();
					$.get(mmocms_root_path+ \"plugins/chat/ajax.php\"+mmocms_sid+\"&unreadTooltip\", function(data){
						$('.chatTooltipUnread').hide();
						$('.chatTooltipUnread').parent().prepend(data);
					});
					$(document).on('click', function(event) {
						var count = $(event.target).parents('.chat-tooltip-container').length;									
						if (count == 0){
							$('.chat-tooltip').hide('fast');
						}
					});
					
				});
					
			", "docready");
			
			$this->tpl->assign_block_vars("personal_area_addition", array(
				"TEXT" => '<div class="chat-tooltip-container"><a href="'.register("routing")->build("chathistory").'">
									<i class="fa fa-comments"></i>Chat
									</a> 
									<div class="notification-tooltip-container">
									<span class="notification-bubble-red chat-tooltip-trigger hand"></span>
									<ul class="dropdown-menu chat-tooltip" role="menu" id="chat-tooltip">
										<li class="chatTooltipUnread"><div style="text-align:center;"><i class="fa-spin fa fa-spinner fa-lg"></i></div></li>
										<li class="tooltip-divider"></li>
										<li><a href="'.register("routing")->build("chathistory").'">Alle Konversationen</a></li>
									</ul>
								</div>
							</div>',
			));
			
			$this->tpl->staticHTML('<div class="chatContainer">
										<div id="chatMenu" class="chatFloat">
											<div id="chatOnlineMinimized" class="chatWindowMin">
												<i class="fa fa-comments"></i> Chat (<span class="chatOnlineCount">0</span>)
											</div>
											<div id="chatOnlineMaximized" class="chatWindowContainer" style="display:none;">
												<div class="chatWindowMenu">
													<div class="chatWindowHeader2">
														<i class="fa fa-comments"></i> Chat <i class="fa fa-times floatRight hand"></i>
													</div>
													<div class="chatWindowContent">
														<div class="chatOnlineList"></div>
														<div class="clear"></div>
													</div>
													<div class="clear"></div>
													<div class="chatInput">
														<input type="text" id="chatOnlineSearch" placeholder="Benutzer filtern" />
													</div>
												</div>
											</div>
										</div>
										<div id="chatWindows"><div id="chatWindowList" class="chatFloat"></div></div>
									</div>');
			
		}
	}
  }
}
?>