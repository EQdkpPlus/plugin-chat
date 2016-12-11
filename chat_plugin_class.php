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
  header('HTTP/1.0 404 Not Found');
  exit;
}


/*+----------------------------------------------------------------------------
  | chat
  +--------------------------------------------------------------------------*/
class chat extends plugin_generic
{

  public $version    = '0.4.0';
  public $build      = '';
  public $copyright  = 'GodMod';
  
  protected static $apiLevel = 23;

  /**
    * Constructor
    * Initialize all informations for installing/uninstalling plugin
    */
  public function __construct()
  {
    parent::__construct();

    $this->add_data(array (
      'name'              => 'Chat',
      'code'              => 'chat',
      'path'              => 'chat',
      'template_path'     => 'plugins/chat/templates/',
      'icon'              => 'fa-comments',
      'version'           => $this->version,
      'author'            => $this->copyright,
      'description'       => $this->user->lang('chat_short_desc'),
      'long_description'  => $this->user->lang('chat_long_desc'),
      'homepage'          => EQDKP_PROJECT_URL,
      'manuallink'        => false,
      'plus_version'      => '2.3',
      'build'             => $this->build,
    ));

    $this->add_dependency(array(
      'plus_version'      => '2.3'
    ));

    // -- Register our permissions ------------------------
    // permissions: 'a'=admins, 'u'=user
    // ('a'/'u', Permission-Name, Enable? 'Y'/'N', Language string, array of user-group-ids that should have this permission)
    // Groups: 1 = Guests, 2 = Super-Admin, 3 = Admin, 4 = Member
	$this->add_permission('u', 'view',    'Y', $this->user->lang('chat_view'),    array(2,3,4));
	$this->add_permission('u', 'mod_pub',    'Y', $this->user->lang('chat_mod_pub'),    array(2,3));
    $this->add_permission('a', 'manage', 'N', $this->user->lang('manage'), array(2,3));
	$this->add_permission('a', 'settings', 'N', $this->user->lang('menu_settings'), array(2,3));
	
	$this->add_pdh_read_module('chat_online');
	$this->add_pdh_read_module('chat_open_conversations');
	$this->add_pdh_read_module('chat_conversations');
	$this->add_pdh_read_module('chat_messages');
	$this->add_pdh_read_module('chat_conversation_lastvisit');
	
	$this->add_pdh_write_module('chat_open_conversations');
	$this->add_pdh_write_module('chat_conversations');
	$this->add_pdh_write_module('chat_messages');
	$this->add_pdh_write_module('chat_conversation_lastvisit');
	
	$this->add_hook('portal', 'chat_portal_hook', 'portal');
	    
    //Routing
	$this->routing->addRoute('Chat', 'chat', 'plugins/chat/page_objects');
	$this->routing->addRoute('ChatHistory', 'chathistory', 'plugins/chat/page_objects');
	
	// -- Menu --------------------------------------------
    $this->add_menu('admin', $this->gen_admin_menu());
	
	$this->add_menu('main', $this->gen_main_menu());
	//$this->add_menu('settings', $this->usersettings());
	
  }

  /**
    * pre_install
    * Define Installation
    */
   public function pre_install()
  {
    // include SQL and default configuration data for installation
    include($this->root_path.'plugins/chat/includes/sql.php');

    // define installation
    for ($i = 1; $i <= count($chatSQL['install']); $i++)
      $this->add_sql(SQL_INSTALL, $chatSQL['install'][$i]);
    
    $this->config->set('new_message_sound', 1, 'chat');
  }

  /**
    * pre_uninstall
    * Define uninstallation
    */
  public function pre_uninstall()
  {
    // include SQL data for uninstallation
    include($this->root_path.'plugins/chat/includes/sql.php');

    for ($i = 1; $i <= count($chatSQL['uninstall']); $i++)
      $this->add_sql(SQL_UNINSTALL, $chatSQL['uninstall'][$i]);
  }
  
  /**
   * gen_admin_menu
   * Generate the Admin Menu
   */
  private function gen_main_menu()
  {
  
  	$main_menu = array(
  			1 => array (
  					'link'  => $this->routing->build('Chat', false, false, true, true),
  					'text'  => $this->user->lang('chat'),
  					'check' => 'u_chat_view',
  			),
  	);
  
  	return $main_menu;
  }

  /**
    * gen_admin_menu
    * Generate the Admin Menu
    */
  private function gen_admin_menu()
  {

    $admin_menu = array (array(
        'name' => $this->user->lang('chat'),
        'icon' => 'fa-comments',
        1 => array (
          'link'  => 'plugins/chat/admin/settings.php'.$this->SID,
          'text'  => $this->user->lang('settings'),
          'check' => 'a_chat_settings',
          'icon'  => 'fa-wrench'
        ),
    ));

    return $admin_menu;
  }
  
  private function usersettings(){
	$settings = array(
		'chat' => array(
			'icon' => 'fa-comments',
		
		'gr_send_notification_mails'	=> array(
			'fieldtype'	=> 'checkbox',
			'default'	=> 0,
			'name'		=> 'gr_send_notification_mails',
			'language'	=> 'gr_send_notification_mails',
		),
		
		'gr_jgrowl_notifications'	=> array(
			'fieldtype'	=> 'checkbox',
			'default'	=> 0,
			'name'		=> 'gr_jgrowl_notifications',
			'language'	=> 'gr_jgrowl_notifications',
		)),
	);
	return $settings;
  }
}
?>
