<?php
/*	Project:	EQdkp-Plus
 *	Package:	Guildbanker Plugin
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2015 EQdkp-Plus Developer Team
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
define('EQDKP_INC', true);
define('IN_ADMIN', true);
define('PLUGIN', 'chat');

$eqdkp_root_path = './../../../';
include_once($eqdkp_root_path.'common.php');

class chatAdminSettings extends page_generic {
	/**
	* Constructor
	*/
	public function __construct(){
		// plugin installed?
		if (!$this->pm->check('chat', PLUGIN_INSTALLED))
			message_die($this->user->lang('chat_not_installed'));

		$handler = array(
			'save' => array('process' => 'save', 'csrf' => true, 'check' => 'a_chat_settings'),
		);
		parent::__construct('a_chat_settings', $handler);

		$this->process();
	}

	private $arrData = false;

	public function save(){
		$objForm				= register('form', array('chat_settings'));
		$objForm->langPrefix	= 'chat_';
		$objForm->validate		= true;
		$objForm->add_fieldsets($this->fields());
		$arrValues				= $objForm->return_values();

		if($objForm->error){
			$this->arrData		= $arrValues;
		}else{
			// update configuration
			$this->config->set($arrValues, '', 'chat');
			// Success message - Message, Title
			$messages[]			= array($this->user->lang('save_suc'), $this->user->lang('settings'));
			$this->display($messages);
		}
	}

	private function fields(){
		$arrFields = array(
			'sounds' => array(
				'new_message_sound' => array(
					'type' => 'radio',
					'default' => 1,
				)	
			),
			'ajax' => array(
				'reload_chat' => array(
					'type'		=> 'spinner',
					'default'	=> 5,
				),
				'reload_onlinelist' => array(
					'type'		=> 'spinner',
					'default'	=> 5,
				),
			),
		);
		return $arrFields;
	}

	public function display($messages=array()){
		// -- Messages ------------------------------------------------------------
		if ($messages){
			foreach($messages as $val)
				$this->core->message($val[0], $val[1], 'green');
		}

		// get the saved data
		$arrValues		= $this->config->get_config('chat');
		if ($this->arrData !== false) $arrValues = $this->arrData;

		// -- Template ------------------------------------------------------------
		// initialize form class
		$objForm				= register('form', array('chat_settings'));
		$objForm->reset_fields();
		$objForm->lang_prefix	= 'chat_';
		$objForm->validate		= true;
		$objForm->use_fieldsets	= true;
		$objForm->add_fieldsets($this->fields());
		
		// Output the form, pass values in
		$objForm->output($arrValues);

		$this->core->set_vars(array(
			'page_title'	=> $this->user->lang('chat').' '.$this->user->lang('settings'),
			'template_path'	=> $this->pm->get_data('chat', 'template_path'),
			'template_file'	=> 'admin/manage_settings.html',
			'display'		=> true
	  ));
	}

}
registry::register('chatAdminSettings');
?>
