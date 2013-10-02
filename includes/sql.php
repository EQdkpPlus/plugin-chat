<?php
/*
 * Project:     EQdkp Shoutbox
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2011-08-09 10:00:07 +0200 (Di, 09. Aug 2011) $
 * -----------------------------------------------------------------------
 * @author      $Author: Aderyn $
 * @copyright   2008-2011 Aderyn
 * @link        http://eqdkp-plus.com
 * @package     shoutbox
 * @version     $Rev: 10949 $
 *
 * $Id: sql.php 10949 2011-08-09 08:00:07Z Aderyn $
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