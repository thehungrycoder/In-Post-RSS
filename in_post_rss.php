<?php
/*  Copyright 2008  The HungryCoder  (email : rajuru@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin Name: In Post RSS Feeds
Plugin URI: http://hungrycoder.xenexbd.com/?p=865
Description: Show a feed inside the WordPress Post that updates live. It is very helpful to show relevant contents that is updating live :).
Version: 1.0
Author: The HungryCoder
Author URI: http://hungrycoder.xenexbd.com/

*/



add_action('save_post', 'ipr_meta_save');
add_action('admin_menu', 'ipr_meta_init');
add_filter('the_content','ipr_show_feed');
add_action('admin_menu','ipr_admin_menu');
function ipr_admin_menu(){
	add_options_page('In Post RSS','In Post RSS','edit_post',__FILE__,'ipr_setting_page');
}
function ipr_meta_init() {

	if (function_exists('add_meta_box')) {
		add_meta_box('ipr_meta','In Post RSS', 'ipr_meta_box', 'post', 'normal','high');
	}
}

/**
Meta box code for WordPress 2.5+
*/
function ipr_meta_box() {
	global $post_ID;

	$feedtitle = get_post_meta($post_ID, 'ipr_feedtitle', true);
	$feedurl = get_post_meta($post_ID, 'ipr_feedurl', true);
	$feednumitems = get_post_meta($post_ID, 'ipr_feednumitems', true);

	echo "<input type=\"hidden\" name=\"ipr_nonce\" id=\"ipr_nonce\" value=\"" . wp_create_nonce(md5(plugin_basename(__FILE__))) . "\" />";
?>
		<table id="newmeta1">
			<tr><th>Feed Title</th><td><input  type="text" size="60" tabindex="900" id="ipr_feedtitle" name="ipr_feedtitle" value="<?php echo $feedtitle;?>"></td></tr>
			<tr><th>Feed URL</th><td><input  type="text" size="60" tabindex="901" id="ipr_feedurl" name="ipr_feedurl" value="<?php echo $feedurl;?>"></td></tr>
		</table>

	<?php
	}


function ipr_meta_save($post_id){
	if (!wp_verify_nonce($_POST['ipr_nonce'], md5(plugin_basename(__FILE__)))) { return $post_id; }

	if ('post' == $_POST['post_type']) {
		if (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
	}

	//update the custom values
	if(!empty($_POST['ipr_feedurl'])){
		update_post_meta($post_id,'ipr_feedurl',$_POST['ipr_feedurl']);
	}
	if(!empty($_POST['ipr_feedtitle'])){
		update_post_meta($post_id,'ipr_feedtitle',$_POST['ipr_feedtitle']);
	}

}

function ipr_show_feed($content){
	global $post;
	//get the meta
	$feedurl = get_post_meta($post->ID,'ipr_feedurl',true);

	//is feedurl empty?
	if(empty($feedurl)){
		return $content;
	}


	$feednumitems = intval(get_option('ipr_feednumitems'));
	$hidetitle = get_option('ipr_hidefeedtitle');
	if(!$hidetitle){
		$feedtitle = get_post_meta($post->ID,'ipr_feedtitle',true);
	}
	//we will show feed only num items are greater than 0
	if(!$feednumitems) $feednumitems = 5;

	require_once(ABSPATH . WPINC . '/feed.php');

	//is the function defined?
	if(function_exists('fetch_feed')){
		//then fetch the feed. it will automatically be cached.
		$feed = fetch_feed($feedurl);
	} else {
		return $content;
	}

	//can't the feed be fetched?
	if(is_wp_error($feed)) return $content;

	$num_items = $feed->get_item_quantity();
	if($num_items < $feednumitems){
		$feednumitems = $num_items;
	}

	$feeditems = $feed->get_items();

	$feedcontents = '';
	//set title
	if(!$hidetitle){
		$feedcontents .= '<h2>'. __($feedtitle,'ipr').'</h2>';
	}
	$feedcontents .= '<ul>';
		if($feednumitems == 0){
			$feedcontents .= '<li>Sorry, no feed items found.</li>';
		} else {
			$i=0;
			foreach ($feeditems as $item){
				if($item->get_title()){
					$feedcontents .= '<li><a href="'.$item->get_permalink().'">'.$item->get_title().'</a></li>'.PHP_EOL;
					$i++;
					if($i>=$feednumitems) break;
				}
			}
		}
	$feedcontents .= '</ul>';

	$content = $content . $feedcontents;
	return $content;
}

function ipr_setting_page(){
?>
<div class="wrap">
	<h1>In Post RSS Configuration</h1>
	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options');?>
	<table class="form-table">
		<tr valign="top">
		<th scope="row">Number of Items to show </th>
		<?php
		$num_items = get_option('ipr_feednumitems');
		if(!$num_items) $num_items = 5;
		$hidetitle = get_option('ipr_hidefeedtitle');
		?>
		<td><input type="text" name="ipr_feednumitems" value="<?php echo $num_items; ?>" /><small>Default: 5</small></td>
		</tr>
		<tr valign="top">
		<th scope="row">Hide Title</th>
		<td><input type="checkbox" name="ipr_hidefeedtitle" value="1" <?php if($hidetitle) echo 'checked="checked"'; ?>" /></td>
		</tr>
	</table>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="ipr_feednumitems,ipr_hidefeedtitle" />
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
</form>
<br /><br />
<h3>Information</h3>
Developed by: <a href="http://hungrycoder.xenexbd.com/" target="_blank">The HungryCoder</a> :: <a href="http://hungrycoder.xenexbd.com/?p=865#premium" target="_blank"><b>Buy Premium version</b></a> :: <a href="http://hungrycoder.xenexbd.com/?p=865#comment" target="_blank">Ask for Help</a> :: <a href="http://controlpanelblog.com" target="_blank">Need help about cPanel, Plesk?</a>
</div>
<?php
}
?>