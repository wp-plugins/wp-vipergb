<?php
/*
 * Plugin Name: WP-ViperGB
 * Description: Create a stylish and user-friendly Guestbook for your blog.  Designed to replicate the appearance and behavior of the discontinued <a href="http://www.vipergb.de.vu/">Viper Guestbook</a> project.  
 * Author: Justin Klein
 * Version: 1.1.11
 * Author URI: http://www.justin-klein.com/
 * Plugin URI: http://www.justin-klein.com/projects/wp-vipergb
*/


/*
 * Copyright 2010 Justin Klein (email: justin@justin-klein.com)
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

global $vgb_name, $vgb_homepage, $vgb_version;
$vgb_name               = "WP-ViperGB";
$vgb_homepage           = "http://www.justin-klein.com/projects/wp-vipergb";
$vgb_version            = "1.1.11";

//Our plugin options
global $opt_vgb_page, $opt_vgb_style, $opt_vgb_items_per_pg, $opt_vgb_reverse;
global $opt_vgb_allow_upload, $opt_vgb_allow_upload, $opt_vgb_max_upload_siz;
global $opt_vgb_show_browsers, $opt_vgb_show_flags, $opt_vgb_show_cred_link;
$opt_vgb_page           = 'vgb_page';
$opt_vgb_style          = 'vgb_style';
$opt_vgb_items_per_pg   = 'vgb_items_per_pg';
$opt_vgb_reverse        = 'vgb_reverse';
$opt_vgb_allow_upload   = 'vgb_allow_upload';
$opt_vgb_max_upload_siz = 'vgb_max_upload_siz';
$opt_vgb_show_browsers  = 'vgb_show_browsers';
$opt_vgb_show_flags     = 'vgb_show_flags';
$opt_vgb_show_cred_link = 'vgb_show_cred_link';

//Load the textdomain for localization
define('WPVGB_DOMAIN', 'wpvipergb');
load_plugin_textdomain(WPVGB_DOMAIN, false, dirname( plugin_basename(__FILE__) ) . '/lang');

//Include required implementation code
require_once('_output_guestbook.php');
require_once('_admin_menu.php');


//Hook into the user-selected Guestbook page and fill its contents with the Guestbook.
//NOTE: If you want to manually generate a guestbook in one of your templates, simply
//echo vgb_GetGuestbook(), passing the desired options (or nothing, for default).
add_filter('the_content', 'vgb_replace_content');
function vgb_replace_content($content)
{
    global $post, $opt_vgb_page;
    if( $post->ID != get_option($opt_vgb_page) ) return $content;
    
    global $opt_vgb_reverse, $opt_vgb_allow_upload, $opt_vgb_items_per_pg, $opt_vgb_max_upload_siz;
    global $opt_vgb_show_browsers, $opt_vgb_show_flags, $opt_vgb_show_cred_link;
    return vgb_GetGuestbook(array('entriesPerPg' => get_option($opt_vgb_items_per_pg),
                                  'reverseOrder' => get_option($opt_vgb_reverse),
                                  'allowUploads' => get_option($opt_vgb_allow_upload),
                                  'maxImgSizKb'  => get_option($opt_vgb_max_upload_siz),
                                  'showBrowsers' => get_option($opt_vgb_show_browsers),
                                  'showFlags'    => get_option($opt_vgb_show_flags),
                                  'showCredLink' => get_option($opt_vgb_show_cred_link)));                                         
}


//Since our Guestbook is basically a glorified full-page comments template,
//suppress the regular one by replacing it with a blank file. 
add_filter('comments_template', 'suppress_comments');
function suppress_comments( $file )
{
    global $post, $opt_vgb_page;
    if( $post->ID == get_option($opt_vgb_page) ) return dirname(__FILE__) . '/_blank.php';
    else                                         return $file;  
}


//Add some styles (make sure they come after the ecu stylesheet, so we can override)
wp_enqueue_style('WP-ViperGB-Default', vgb_get_data_url().'styles/Default.css', array('ecu'), $vgb_version );
$currentStyle = get_option($opt_vgb_style);
if( $currentStyle != 'Default' )
    wp_enqueue_style('WP-ViperGB-'.$currentStyle, vgb_get_data_url().'styles/'.$currentStyle.".css", array('WP-ViperGB-Default', 'ecu'), $vgb_version );


//Add support for [img] shortcode in comments (for user-uploaded images)
add_action('comment_text', 'comment_img_shortcode');
function comment_img_shortcode($content)
{
    return preg_replace('/\[img=?\]*(.*?)(\[\/img)?\]/e', '"<img src=\"$1\" class=\"ecu_images\" alt=\"" . basename("$1") . "\" />"', $content);
}


//Authenticate
register_activation_hook(__FILE__, 'vgb_activate');
register_deactivation_hook(__FILE__, 'vgb_deactivate');
function vgb_activate()  
{ 
    if( get_option($GLOBALS['opt_vgb_page']) )
        vgb_auth($GLOBALS['vgb_name'], $GLOBALS['vgb_version'], 1, "ON: " . get_option($GLOBALS['opt_vgb_page'])); 
}
function vgb_deactivate()
{
    if( get_option($GLOBALS['opt_vgb_page']) )
        vgb_auth($GLOBALS['vgb_name'], $GLOBALS['vgb_version'], 0, "OFF: " . get_option($GLOBALS['opt_vgb_page'])); 
}

?>