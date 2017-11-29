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
  header('HTTP/1.0 404 Not Found');exit;
}

$chatSQL = array(

  'uninstall' => array(
    1     => 'DROP TABLE IF EXISTS `__chat_conversations`',
	2     => 'DROP TABLE IF EXISTS `__chat_messages`',
	3     => 'DROP TABLE IF EXISTS `__chat_open_conversations`',
  	4     => 'DROP TABLE IF EXISTS `__chat_conversation_lastvisit`',
  ),

  'install'   => array(
	1 => "CREATE TABLE `__chat_conversations` (
	  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `conversation_key` varchar(50) NOT NULL DEFAULT '',
	  `user` TEXT COLLATE utf8_bin,
	  `title` varchar(255) NOT NULL,
	  `minimized` TINYINT(1) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`id`)
	) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
",
	2 => "CREATE TABLE `__chat_messages` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `conversation_key` varchar(50) NOT NULL DEFAULT '',
	  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
	  `text` TEXT COLLATE utf8_bin NOT NULL,
	  `date` int(11) unsigned NOT NULL,
	  `reed` tinyint(3) unsigned NOT NULL DEFAULT '0',
	  PRIMARY KEY (`id`),
	  KEY `conversation_key` (`conversation_key`)
	) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
",
	
	3 => "CREATE TABLE `__chat_open_conversations` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `conversation_key` varchar(50) NOT NULL DEFAULT '0',
  `open` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`conversation_key`,`user_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"

,
  4 => "CREATE TABLE `__chat_conversation_lastvisit` (
  `conversation_key` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` int(11) unsigned NOT NULL,
  PRIMARY KEY (`conversation_key`,`user_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
  	));

?>