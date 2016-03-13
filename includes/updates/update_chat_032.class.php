<?php
/*	Project:	EQdkp-Plus
 *	Package:	Chat Plugin
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

if (!defined('EQDKP_INC')){
	header('HTTP/1.0 404 Not Found');exit;
}

include_once(registry::get_const('root_path').'maintenance/includes/sql_update_task.class.php');

if (!class_exists('update_chat_032')){
	class update_chat_032 extends sql_update_task{

		public $author		= 'GodMod';
		public $version		= '0.3.2';    // new version
		public $name		= 'Chat 0.3.2 Update';
		public $type		= 'plugin_update';
		public $plugin_path	= 'chat'; // important!

		/**
		* Constructor
		*/
		public function __construct(){
			parent::__construct();

			// init language
			$this->langs = array(
				'english' => array(
					'update_chat_032' => 'Chat 0.3.2 Update Package',
					'update_function' => 'Add setting',
				),
				'german' => array(
					'update_chat_032' => 'Chat 0.3.2 Update Paket',
					'update_function' => 'Füge Einstellung hinzu',
				),
			);

		}
		
		public function update_function(){
			$this->config->set('new_message_sound', 1, 'chat');
			return true;
		}
	}
}
?>
