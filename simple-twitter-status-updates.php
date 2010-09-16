<?php

/*

Plugin Name: Simple Twitter Status Updates
Plugin URI: http://www.bannerweb.ch/unsere-leistungen/wordpress-dev/simple-twitter-status-updates/
Description: Automatically publishes a status on your twitter account when a post has been plublished, modified or commented by an user.
Version: 1.2
Author: Bannerweb GmbH
Author URI: http://www.bannerweb.ch/

*/

# Functions
# -----------------------------------------------------

// Display incompatibility notification
function stsu_incompatibility_notification(){
	
	echo '<div id="message" class="error">
	
	<p><b>The &quot;Simple Twitter Status Updates&quot; plugin does not work on this wordpress installation!</b></p>
	<p>Please check your installation for following minimum requirements:</p>
	
	<p>
	- Wordpress version 2.7 or higer<br />
	- PHP version 5.2 or higher<br />
	- PHP extension CURL 7.0 or higher
	</p>
	
	<p>Do you need help? Contact us on twitter <a href="http://twitter.com/bannerweb">@bannerweb</a></p>
	
	</div>';
}

# Compatibilty check / plugin initialization
# -----------------------------------------------------

// get wordpress version number and fill it up to 9 digits
$int_wp_version = preg_replace('#[^0-9]#', '', get_bloginfo('version'));
while(strlen($int_wp_version) < 9){
	
	$int_wp_version .= '0'; 
}

// get php version number and fill it up to 9 digits
$int_php_version = preg_replace('#[^0-9]#', '', phpversion());
while(strlen($int_php_version) < 9){
	
	$int_php_version .= '0'; 
}

// Check if CURL is loaded, get version number and fill it up to 9 digits
if(extension_loaded('curl') === true){
	
	$arr_curl_version = curl_version();
	$int_curl_version = preg_replace('#[^0-9]#', '', $arr_curl_version['version']);
	while(strlen($int_curl_version) < 9){
		
		$int_curl_version .= '0'; 
	}
}

// Check overall plugin compatibility
if(	$int_wp_version >= 270000000 and 	// Wordpress version > 2.7
	$int_php_version >= 520000000 and 	// PHP version > 5.2
	$int_curl_version >= 700000000 and 	// CURL version > 7.0
	defined('ABSPATH') and 				// Plugin is not loaded directly
	defined('WPINC')){					// Plugin is not loaded directl
		
	// Load class file
	require_once(dirname(__FILE__).'/stsu.class.php');
	
	// Build admin menu
	add_action('admin_menu', array('STSU', 'buildAdminMenu'), 1);
	
	// Register publish post action
	add_action('publish_post', array('STSU', 'postPagePublish'), 10, 1);

	// Register new popst comment action
	add_action('comment_post', array('STSU', 'commentPublish'), 10, 2);
	
	// Register new popst comment action
	add_action('edit_comment', array('STSU', 'commentEdit'), 10, 1);
}

// Plugin is not compatible with current configuration
else{
	
	// Display incompatibility information
	add_action('admin_notices', 'stsu_incompatibility_notification');
}

?>