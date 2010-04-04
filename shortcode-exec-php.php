<?php
/*
Plugin Name: Shortcode Exec PHP
Plugin URI: http://blog.bokhorst.biz/3626/computers-en-internet/wordpress-plugin-shortcode-exec-php/
Description: Execute reusable PHP code in posts, pages and widgets using shortcodes
Version: 0.4
Author: Marcel Bokhorst
Author URI: http://blog.bokhorst.biz/about/
*/

/*
	Copyright 2010 Marcel Bokhorst

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

#error_reporting(E_ALL);

// Needed for ajax calls
global $wp_version;
if (!$wp_version)
	require_once('../../../wp-config.php');

// Include support class
require_once('shortcode-exec-php-class.php');

// Check pre-requisites
WPShortcodeExecPHP::Check_prerequisites();

// Start plugin
global $wp_shortcode_exec_php;
$wp_shortcode_exec_php = new WPShortcodeExecPHP();

// Check ajax requests
$wp_shortcode_exec_php->Check_ajax();

// That's it!

?>
