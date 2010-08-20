<?php

// DEFINE Twitter application settings
define('STSU_TWITTER_CK', 'ACTzPVc74h8wGtdyNPwDSQ');
define('STSU_TWITTER_CS', 'kt4a8eCEnas11nvGVdljMpOljZSrsAYhjL2DvCkYT5s');
define('STSU_TWITTER_RTU', 'http://twitter.com/oauth/request_token');
define('STSU_TWITTER_ATU', 'http://twitter.com/oauth/access_token');
define('STSU_TWITTER_AU', 'http://twitter.com/oauth/authorize');

define('TWITTER_CONSUMER_KEY', 'ACTzPVc74h8wGtdyNPwDSQ');
define('TWITTER_CONSUMER_SECRET', 'kt4a8eCEnas11nvGVdljMpOljZSrsAYhjL2DvCkYT5s');

// Load required PHP files
require_once(dirname(__FILE__).'/twitter-async/EpiCurl.php');
require_once(dirname(__FILE__).'/twitter-async/EpiOAuth.php');
require_once(dirname(__FILE__).'/twitter-async/EpiTwitter.php');

// Main class
class STSU {
	
	// Calculate timestamp from post data attribut
	private function calculateTimestamp($str_time_date){
		
		$int_time_date = explode(" ", $str_time_date);
		$int_time_date[0] = explode("-", $int_time_date[0]);
		$int_time_date[1] = explode(":", $int_time_date[1]);
		$int_time_date = mktime($int_time_date[1][0], $int_time_date[1][1], $int_time_date[1][2], $int_time_date[0][1], $int_time_date[0][2], $int_time_date[0][0]);
	
		return $int_time_date;
	}
	
	// Register settings page
	public function buildAdminMenu(){
		
		// Check if twitter auth-token has been created
		if(!get_option('stsu_twitter_auth_token') and $_GET['page'] != 'stsu'){
			
			// Message, no twitter auth-token
			echo '<div id="message" class="updated"><p><b>Please <a href="options-general.php?page=stsu">authenticate</a> your twitter account with the &quot;Simple Twitter Status Updates&quot; plugin!</b></p></div>';
		}
		
		// Add page to the admin options
		add_options_page('Simple Twitter Status Updates', 'Twitter Updates', 'manage_options', 'stsu', array('STSU', 'pageSettings'));
		
	}
	
	// User settings, twitter oauth authentication (GUI)
	public function pageSettings(){
		
		// Remove Twitter Authentication
		if($_GET['action'] == 'remTwitterAuth'){
			
			// Reset User Authentication
			update_option('stsu_twitter_auth_token', '');
			update_option('stsu_twitter_auth_secret', '');
		}
		
		// Save data
		if($_POST['stsu_settings']){
			
			// Basic settings
			update_option('stsu_'.'post_new', preg_replace('#[^0-9]#', '', $_POST['post_new']));
			update_option('stsu_'.'comment_post', preg_replace('#[^0-9]#', '', $_POST['comment_post']));
			update_option('stsu_'.'post_modify', preg_replace('#[^0-9]#', '', $_POST['post_modify']));
			
			// Postfix and Suffix
			update_option('stsu_'.'new_post_suffix', htmlspecialchars($_POST['new_post_suffix']));
			update_option('stsu_'.'new_post_postfix', htmlspecialchars($_POST['new_post_postfix']));
			update_option('stsu_'.'post_comment_suffix', htmlspecialchars($_POST['post_comment_suffix']));
			update_option('stsu_'.'post_comment_postfix', htmlspecialchars($_POST['post_comment_postfix']));
			update_option('stsu_'.'modified_post_suffix', htmlspecialchars($_POST['modified_post_suffix']));
			update_option('stsu_'.'modified_post_postfix', htmlspecialchars($_POST['modified_post_postfix']));
			
			// Update time intervall
			update_option('stsu_'.'time_gap_general', preg_replace('#[^0-9]#', '', $_POST['time_gap_general']));
			update_option('stsu_'.'time_gap_post', preg_replace('#[^0-9]#', '', $_POST['time_gap_post']));
			
    		// Display saved message
			echo '<div id="message" class="updated"><p>Changes saved!</p></div>';
		}
		
		// Verify Twitter auth-token
		if($_GET['oauth_token'] and $_GET['oauth_verifier']){
			
			$objTwitter = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
			$objTwitter->setToken($_GET['oauth_token']);  
			$token = $objTwitter->getAccessToken(array('oauth_verifier' => $_GET['oauth_verifier']));
			$objTwitter->setToken($token->oauth_token, $token->oauth_token_secret);
			$twitterInfo = $objTwitter->get_accountVerify_credentials();
			
			update_option('stsu_twitter_auth_token', $token->oauth_token);
			update_option('stsu_twitter_auth_secret', $token->oauth_token_secret);
			update_option('stsu_twitter_screen_name', $token->screen_name);
		}
		
		// Display settings form
		echo '<div class="wrap">
		<div id="icon-options-general" class="icon32"><br></div>
		<h2>Einstellungen &gt; Simple Twitter Status Updates</h2></div>
		
		<h3>How does ist work?</h3>
		
		<p>The "Simple Twitter Status Updates" plugin automatically publishes a status on your twitter account when a new post has been plublished or a post has been commented by an user.<br />
		Keep your follwers up-to-date with what happens on your blog!</p>
		
		<p>Visit <a href="http://www.bannerweb.ch/das-unternehmen/kontakt/">www.bannerweb.ch</a> for further information, to give us a feedback or to get support!</p>
		
		<p>&nbsp;</p>
		<h3>Twitter authentication (oAuth)</h3>';
		
		// Check if twitter auth-token has been created
		if(!get_option('stsu_twitter_auth_token')){
			
			
			
			// Generate oAuth URL
			$objTwitter = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
			$str_request_token = $objTwitter->getRequestToken(array('oauth_callback' => get_bloginfo('url').'/wp-admin/options-general.php?page=stsu'));
			$str_request_url = $objTwitter->getAuthenticateUrl($str_request_token);
			
	
			echo '
			<p><b>You have to authenticate this plugin with your twitter account to make it work.</b></p>
			<p>Do so by clicking the button below. The process will take you to the twitter website.</p>
			<p><input type="submit" class="button-primary" name="twitterOAuth" value="Authenticate with twitter" onclick="document.location.href=\''.$str_request_url.'\'" /></p>';
		}
		
		else{
			
			echo '
			<p>The &quot;Simple Twitter Status Updates&quot; plugin has been successfully authenticated with your Twitter account <b>['.get_option('stsu_twitter_screen_name').']</b></p>
			<p><a href="javascript:if(confirm(\'After removing this authentication there will be no more status updates on your twitter timeline!\n\nContinue?\')==true){document.location.href=\'options-general.php?page=stsu&action=remTwitterAuth\'}" />Remove Twitter authentication for ['.get_option('stsu_twitter_screen_name').']</a></p>';
		}
		
		
		echo '
		<p>&nbsp;</p>
		<h3>Basic settings</h3>
		
		<form method="post" action="options-general.php?page=stsu">

		<table class="form-table">
			<tr>
				<th scope="row" colspan="2" class="th-full">
				<label for="post_new">
				<input name="post_new" id="post_new" value="1" type="checkbox"
				'.((get_option('stsu_'.'post_new') == 1) ? 'checked="checked"' : false ).'>
				Publish twitter status when publishing a NEW POST</label>
				</th>
			</tr>
			<tr>
				<th scope="row" colspan="2" class="th-full">
				<label for="post_modify">
				<input name="post_modify" id="post_modify" value="1" type="checkbox"
				'.((get_option('stsu_'.'post_modify') == 1) ? 'checked="checked"' : false ).'>
				Publish twitter status when a POST has been MODIFIED</label>
				</th>
			</tr>
			<tr>
				<th scope="row" colspan="2" class="th-full">
				<label for="comment_post">
				<input name="comment_post" id="comment_post" value="1" type="checkbox"
				'.((get_option('stsu_'.'comment_post') == 1) ? 'checked="checked"' : false ).'>
				Publish twitter status when there is a NEW COMMENT on a POST</label>
				</th>
			</tr>
		</table>
		
		<p><input type="submit" class="button" name="stsu_settings" value="save changes" /></p>
		
		<p>&nbsp;</p>
		<h3>Postfix and Suffix</h3>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="new_post_suffix">NEW POST suffix</label></th>
				<td><input name="new_post_suffix" id="new_post_suffix" 
				value="'.get_option('stsu_'.'new_post_suffix').'" class="regular-text code" type="text">
				<span class="description">Will be added to the twitter status <b>before</b> the link to the post</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="new_post_postfix">NEW POST postfix</label></th>
				<td><input name="new_post_postfix" id="new_post_postfix" 
				value="'.get_option('stsu_'.'new_post_postfix').'" class="regular-text code" type="text">
				<span class="description">Will be added to the twitter status <b>after</b> the link to the post</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="modified_post_suffix">MODIFIED POST suffix</label></th>
				<td><input name="modified_post_suffix" id="modified_post_suffix" 
				value="'.get_option('stsu_'.'modified_post_suffix').'" class="regular-text code" type="text">
				<span class="description">Will be added to the twitter status <b>before</b> the link to the post</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="modified_post_postfix">MODIFIED POST postfix</label></th>
				<td><input name="modified_post_postfix" id="modified_post_postfix" 
				value="'.get_option('stsu_'.'modified_post_postfix').'" class="regular-text code" type="text">
				<span class="description">Will be added to the twitter status <b>after</b> the link to the post</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="post_comment_suffix">POST COMMENT suffix</label></th>
				<td><input name="post_comment_suffix" id="post_comment_suffix" 
				value="'.get_option('stsu_'.'post_comment_suffix').'" class="regular-text code" type="text">
				<span class="description">Will be added to the twitter status <b>before</b> the link to the post</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="post_comment_postfix">POST COMMENT postfix</label></th>
				<td><input name="post_comment_postfix" id="post_comment_postfix" 
				value="'.get_option('stsu_'.'post_comment_postfix').'" class="regular-text code" type="text">
				<span class="description">Will be added to the twitter status <b>after</b> the link to the post</span>
				</td>
			</tr>
		</table>
		
		<p><input type="submit" class="button" name="stsu_settings" value="save changes" /></p>
		
		<p>&nbsp;</p>
		<h3>Update time intervall</h3>
		
		<p>To prevent your twitter stream from beeing flooded by a uncountable number of status updates by your blog, your can set minimal time gaps between two updates.</p>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="time_gap_general">GENERAL time gap</label></th>
				<td><input name="time_gap_general" id="time_gap_general" 
				value="'.((!get_option('stsu_'.'time_gap_general')) ? '1800' : get_option('stsu_'.'time_gap_general') ).'" class="small-text code" type="text">
				<span class="description">General time gap between two twitter status updates in <b>seconds</b></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="time_gap_post">POST time gap</label></th>
				<td><input name="time_gap_post" id="time_gap_post" 
				value="'.((!get_option('stsu_'.'time_gap_post')) ? '1800' : get_option('stsu_'.'time_gap_post') ).'" class="small-text code" type="text">
				<span class="description">General time gap between two twitter status updates in <b>seconds</b> for the same post (don\'t mind if new, modified or commented) </span>
				</td>
			</tr>
		</table>
		
		<p><input type="submit" class="button" name="stsu_settings" value="save changes" /></p>
		
		</form>
		<p>&nbsp;</p>
		';
	}

	// Called when there is a new published or modified post or page
	public function postPagePublish($int_id_post){
		
		global $wpdb;
		
		$objSTSU = new STSU();
		
		$int_post_entry = $objSTSU->calculateTimestamp($wpdb->get_var("SELECT post_date FROM $wpdb->posts WHERE ID = '".$int_id_post."'"));
		$int_post_modified = $objSTSU->calculateTimestamp($wpdb->get_var("SELECT post_modified FROM $wpdb->posts WHERE ID = '".$int_id_post."'"));
		
		// Check if Post is new
		if($int_post_entry == $int_post_modified){
		
			// Check if status should be updated
			if(get_option('stsu_'.'post_new') == 1){ 
				
				// Post and prefix
				$str_prefix = get_option('stsu_'.'new_post_suffix');
				$str_postfix = get_option('stsu_'.'new_post_postfix');
				
				// Calculate lenght
				$int_prefix_lenght = strlen($str_prefix) + 1;
				$int_postfix_lenght = strlen($str_postfix) + 1;
				$int_usable_lenght = 140 - $int_prefix_lenght - $int_postfix_lenght;
				
				// Get URL of current post
				$str_post_url = $wpdb->get_var("SELECT guid FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
				
				// Calculate avilable title lenght
				$int_usable_lenght =  $int_usable_lenght - (strlen($str_post_url) + 2);
				
				// Get title of current post
				$str_post_title = $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
				
				// Check if title is too long
				if(strlen($str_post_title) > $int_usable_lenght){
					
					// Shorten title
					$str_post_title = trim(substr($str_post_title, 0, $int_usable_lenght -4)).'...';
				}
				
				// Status string
				$str_status = $str_prefix.' '.$str_post_title.' '.$str_post_url.' '.$str_postfix;
				
				// Set Twitter status
				$objSTSU->updateStatus($str_status);
			
				// Set Timestamp of post update
				update_option('stsu_'.'post_last_update_'.$int_id_post, $int_post_modified);
				update_option('stsu_'.'last_update', $int_post_modified);
			}
		}
		
		// Post updated
		else if($int_post_modified - get_option('stsu_'.'time_gap_post') > get_option('stsu_'.'post_last_update_'.$int_id_post)
				and $int_post_modified - get_option('stsu_'.'time_gap_general') > get_option('stsu_'.'last_update')){
			
			// Check if status should be updated
			if(get_option('stsu_'.'post_modify') == 1){ 
			
				// Post and prefix
				$str_prefix = get_option('stsu_'.'modified_post_suffix');
				$str_postfix = get_option('stsu_'.'modified_post_postfix');
				
				// Calculate lenght
				$int_prefix_lenght = strlen($str_prefix) + 1;
				$int_postfix_lenght = strlen($str_postfix) + 1;
				$int_usable_lenght = 140 - $int_prefix_lenght - $int_postfix_lenght;
				
				// Get URL of current post
				$str_post_url = $wpdb->get_var("SELECT guid FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
				
				// Calculate avilable title lenght
				$int_usable_lenght =  $int_usable_lenght - (strlen($str_post_url) + 2);
				
				// Get title of current post
				$str_post_title = $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
				
				// Check if title is too long
				if(strlen($str_post_title) > $int_usable_lenght){
					
					// Shorten title
					$str_post_title = trim(substr($str_post_title, 0, $int_usable_lenght -4)).'...';
				}
				
				// Status string
				$str_status = $str_prefix.' '.$str_post_title.' '.$str_post_url.' '.$str_postfix;
				
				// Set Twitter status
				$objSTSU->updateStatus($str_status);
				
				// Set Timestamp of post update
				update_option('stsu_'.'post_last_update_'.$int_id_post, $int_post_modified);
				update_option('stsu_'.'last_update', $int_post_modified);
			}
		}
	}
	
	// Called when there is a new published comment
	public function commentPublish($int_id_comment, $str_comment_state){
		
		global $wpdb;
		
		$objSTSU = new STSU();
		
		// Get post id
		$int_id_post = $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID = '".$int_id_comment."'");
		
		// Get post type
		$str_post_type = $wpdb->get_var("SELECT post_type FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
		
		// Check if post id is avilable (isn't if comment is not approved) and if it so not a page
		if($int_id_post > 0 and $str_post_type == 'post'){
		
			// Get post modified date
			$int_comment_date = $objSTSU->calculateTimestamp($wpdb->get_var("SELECT comment_date FROM $wpdb->comments WHERE comment_ID = '".$int_id_comment."'"));
			
			// Timespan allow?
			if(	$int_comment_date - get_option('stsu_'.'time_gap_post') > get_option('stsu_'.'post_last_update_'.$int_id_post)
				and $int_comment_date - get_option('stsu_'.'time_gap_general') > get_option('stsu_'.'last_update')){
				
				// Check if status should be updated
				if(get_option('stsu_'.'comment_post') == 1){ 
				
					// Check comment state (1 == published)
					if($str_comment_state == 1){
						
						// Post and prefix
						$str_prefix = get_option('stsu_'.'post_comment_suffix');
						$str_postfix = get_option('stsu_'.'post_comment_postfix');
						
						// Calculate lenght
						$int_prefix_lenght = strlen($str_prefix) + 1;
						$int_postfix_lenght = strlen($str_postfix) + 1;
						$int_usable_lenght = 140 - $int_prefix_lenght - $int_postfix_lenght;
						
						// Get URL of current post
						$str_post_url = $wpdb->get_var("SELECT guid FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
						
						// Calculate avilable title lenght
						$int_usable_lenght =  $int_usable_lenght - (strlen($str_post_url) + 2);
						
						// Get title of current post
						$str_post_title = $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
						
						// Check if title is too long
						if(strlen($str_post_title) > $int_usable_lenght){
							
							// Shorten title
							$str_post_title = trim(substr($str_post_title, 0, $int_usable_lenght -4)).'...';
						}
						
						// Status string
						$str_status = $str_prefix.' '.$str_post_title.' '.$str_post_url.' '.$str_postfix;
						
						// Set Twitter status
						$objSTSU->updateStatus($str_status);
						
						// Set Timestamp of post update
						update_option('stsu_'.'post_last_update_'.$int_id_post, $int_comment_date);
						update_option('stsu_'.'last_update', $int_comment_date);
					}
					
					// Comment has not been published
					else{
						
						// Mark comment as not yet published on twitter
						update_option('stsu_'.'comment_published_'.$int_id_comment, 'no');
					}
				}
			}
		}
	}
	
	// Called when there is a modified comment
	public function commentEdit($int_id_comment){
		
		global $wpdb;
		
		$objSTSU = new STSU();
		
		// Get post id
		$int_id_post = $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID = '".$int_id_comment."'");
		
		// Get post type
		$str_post_type = $wpdb->get_var("SELECT post_type FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
		
		// Get comment status
		$str_comment_state = $wpdb->get_var("SELECT comment_approved FROM $wpdb->comments WHERE comment_ID = '".$int_id_comment."'");
		
		// Check if post id is avilable (isn't if comment is not approved) and if it so not a page
		if($int_id_post > 0 and $str_post_type == 'post'){
		
			// Get post modified date
			$int_comment_date = $objSTSU->calculateTimestamp($wpdb->get_var("SELECT comment_date FROM $wpdb->comments WHERE comment_ID = '".$int_id_comment."'"));
			
			// Timespan allow?
			if(	$int_comment_date - get_option('stsu_'.'time_gap_post') > get_option('stsu_'.'post_last_update_'.$int_id_post)
				and $int_comment_date - get_option('stsu_'.'time_gap_general') > get_option('stsu_'.'last_update')){
				
				// Check if status should be updated
				if(get_option('stsu_'.'comment_post') == 1 and $str_comment_state == 1 and get_option('stsu_'.'comment_published_'.$int_id_comment) == 'no'){ 
					
					// Post and prefix
					$str_prefix = get_option('stsu_'.'post_comment_suffix');
					$str_postfix = get_option('stsu_'.'post_comment_postfix');
					
					// Calculate lenght
					$int_prefix_lenght = strlen($str_prefix) + 1;
					$int_postfix_lenght = strlen($str_postfix) + 1;
					$int_usable_lenght = 140 - $int_prefix_lenght - $int_postfix_lenght;
					
					// Get URL of current post
					$str_post_url = $wpdb->get_var("SELECT guid FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
					
					// Calculate avilable title lenght
					$int_usable_lenght =  $int_usable_lenght - (strlen($str_post_url) + 2);
					
					// Get title of current post
					$str_post_title = $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = '".$int_id_post."'");
					
					// Check if title is too long
					if(strlen($str_post_title) > $int_usable_lenght){
						
						// Shorten title
						$str_post_title = trim(substr($str_post_title, 0, $int_usable_lenght -4)).'...';
					}
					
					// Status string
					$str_status = $str_prefix.' '.$str_post_title.' '.$str_post_url.' '.$str_postfix;
					
					// Set Twitter status
					$objSTSU->updateStatus($str_status);
					
					// Set Timestamp of post update
					update_option('stsu_'.'post_last_update_'.$int_id_post, $int_comment_date);
					update_option('stsu_'.'last_update', $int_comment_date);
					
					// Delete comment mark
					delete_option('stsu_'.'comment_published_'.$int_id_comment);
				}
			}
		}
	}
	
	// Sends a status updat to twitter
	public function updateStatus($str_status){
		
		// Check if there is a twitter authentication
		if(get_option('stsu_twitter_auth_token') and get_option('stsu_twitter_auth_secret')){

			// Create nerw Twitter object
			$objTwitter = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, get_option('stsu_twitter_auth_token'), get_option('stsu_twitter_auth_secret'));
			
			// Submit status update
			$objTwitter->post_statusesUpdate(array('status' => $str_status));
		}
	}
}

?>