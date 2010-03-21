<?php

//Set default options.
add_option($opt_vgb_items_per_pg, 10);
add_option($opt_vgb_max_upload_siz, 50);
add_option($opt_vgb_show_browsers, true);
add_option($opt_vgb_show_flags, true);
add_option($opt_vgb_style, "Default");
add_option($opt_vgb_show_cred_link, false);

/*
 * Tell WP about the Admin page
 */
add_action('admin_menu', 'vgb_add_admin_page', 99);
function vgb_add_admin_page()
{ 
    add_options_page('WP-ViperGB Options', 'WP-ViperGB', 'administrator', "wp-vipergb", 'vgb_admin_page');
}

/**
  * Link to Settings on Plugins page 
  */
add_filter('plugin_action_links', 'vgb_add_plugin_links', 10, 2);
function vgb_add_plugin_links($links, $file)
{
    if( dirname(plugin_basename( __FILE__ )) == dirname($file) )
        $links[] = '<a href="options-general.php?page=' . "wp-vipergb" .'">' . __('Settings','sitemap') . '</a>';
    return $links;
}


/*
 * Output the Admin page
 */
function vgb_admin_page()
{
    global $vgb_name, $vgb_homepage, $vgb_version;
    global $opt_vgb_page, $opt_vgb_style, $opt_vgb_reverse, $opt_vgb_allow_upload;
    global $opt_vgb_items_per_pg, $opt_vgb_max_upload_siz;
    global $opt_vgb_show_browsers, $opt_vgb_show_flags, $opt_vgb_show_cred_link;
    ?>
    <div class="wrap">
      <?php
      if( isset($_POST['opts_updated']) )
      {
          if( get_option($opt_vgb_page) != $_POST[$opt_vgb_page] )
            vgb_auth($vgb_name, $vgb_version, 2, "SET: " . $_POST[$opt_vgb_page]);
          update_option( $opt_vgb_page, $_POST[$opt_vgb_page] );
          update_option( $opt_vgb_style, $_POST[$opt_vgb_style] );
          update_option( $opt_vgb_items_per_pg, $_POST[$opt_vgb_items_per_pg] );
          update_option( $opt_vgb_reverse, $_POST[$opt_vgb_reverse] );
          update_option( $opt_vgb_allow_upload, $_POST[$opt_vgb_allow_upload] );
          update_option( $opt_vgb_max_upload_siz, $_POST[$opt_vgb_max_upload_siz] );
          update_option( $opt_vgb_show_browsers, $_POST[$opt_vgb_show_browsers] );
          update_option( $opt_vgb_show_flags, $_POST[$opt_vgb_show_flags] );
          update_option( $opt_vgb_show_cred_link, $_POST[$opt_vgb_show_cred_link] );
          ?><div class="updated"><p><strong><?php _e('Options saved.', 'mt_trans_domain' ); ?></strong></p></div><?php
      }
      ?>
      <h2 style="clear: none">
         WP-ViperGB Options
         <?php if( get_option($opt_vgb_page) ): ?>
         <span style="font-size:12px;"> <a href="edit-comments.php?p=<?php echo get_option($opt_vgb_page)?>">Manage Entries &raquo;</a></span>
         <?php endif;?>
      </h2>
      To add a Guestbook to your blog, simply create a new page, select it in the first combobox below, and click "Save."<br /><br />
      <hr />
      
      <h4>Main Settings:</h4>
      <form name="formOptions" method="post" action="">
        Guestbook Page:
        <select style="width:150px;" name="<?php echo $opt_vgb_page?>">
          <?php
            $pages = get_pages();  
            $vgb_page = get_option($opt_vgb_page);
            echo '<option value="0" selected>&lt;None&gt;</option>';
            foreach($pages as $page)
               echo '<option value="'.$page->ID.'"'. ($page->ID==$vgb_page?' selected':'').'>'.$page->post_title.'</option>'."\n";
          ?>
        </select><br />
        
        Guestbook Style:
        <select style="width:150px;" name="<?php echo $opt_vgb_style?>">
          <?php
             $stylesDir = opendir(dirname(__FILE__) . "/styles");
             while ($file = readdir($stylesDir))
             {
                if( ($fileEnding = strpos($file, '.css'))===FALSE ) continue;
                $styleName = substr($file, 0, $fileEnding);
                echo '<option value="'.$styleName.'"'. ($styleName==get_option($opt_vgb_style)?' selected':'').'>'.$styleName.'</option>'."\n";
             }
             closedir($stylesDir);
          ?>
        </select><br />
        
        <h4>Extra Settings:</h4>
        <input type="checkbox" name="<?php echo $opt_vgb_reverse?>" value="1" <?php echo get_option($opt_vgb_reverse)?'checked="checked"':''?> /> Reverse Order (list from oldest to newest)<br />
        <input type="text" size="3" name="<?php echo $opt_vgb_items_per_pg?>" value="<?php echo get_option($opt_vgb_items_per_pg) ?>" /> Entries Per Page<br /><br />
        <input type="checkbox" name="<?php echo $opt_vgb_allow_upload?>" value="1" <?php echo get_option($opt_vgb_allow_upload)?'checked="checked"':''?> /> Allow Image Uploads<br />
        <input type="text" size="3" name="<?php echo $opt_vgb_max_upload_siz?>" value="<?php echo get_option($opt_vgb_max_upload_siz) ?>" /> Max Image Filesize (kb)<br /><br />
        <input type="checkbox" name="<?php echo $opt_vgb_show_browsers?>" value="1" <?php echo get_option($opt_vgb_show_browsers)?'checked="checked"':''?> /> Show Browser &amp; OS Icons<br />
        <input type="checkbox" name="<?php echo $opt_vgb_show_flags?>" value="1" <?php echo get_option($opt_vgb_show_flags)?'checked="checked"':''?> /> Show Flag Icons (Requires <a href="http://wordpress.org/extend/plugins/ozhs-ip-to-nation/">Ozh's IP To Nation</a> plugin)<br /><br />
        <input type="checkbox" name="<?php echo $opt_vgb_show_cred_link?>" value="1" <?php echo get_option($opt_vgb_show_cred_link)?'checked="checked"':''?> /> Include a Link to the <a href="<?php echo $vgb_homepage?>">plugin homepage</a> (optional, but much appreciated)<br />
        <input type="hidden" name="opts_updated" value="1" />
        <div class="submit"><input type="submit" name="Submit" value="Save" /></div>
      </form>
    
    <hr />  
      <h4>Development</h4>
      Many hours have gone into making this plugin as clean and easy to use as possible. Although I offer it to you freely, please keep in mind that each hour spent on it was an hour that could've also gone towards income-generating work. If you find it useful, a small donation would be greatly appreciated :)
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_s-xclick" />
        <input type="hidden" name="hosted_button_id" value="BUMTK5NRRG8UN" />
        <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" />
        <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
      </form>
    
    
      <hr />
      <h4>Credits:</h4>
      WP-ViperGB takes advantage of code from several other plugins.  These are:<br /><br />
      <ul>
        <li>&bull; <a href="http://wordpress.org/extend/plugins/easy-comment-uploads/">Easy Comment Uploads</a>: Allow users to embed images in their guestbook entries. <i>This functionality is included by default, but must be explicitly enabled above.</i></li>
        <li>&bull; <a href="http://priyadi.net/archives/2005/03/29/wordpress-browser-detection-plugin/">BrowserSniff</a>: Show a browser and operating system icon in each visitor's guestbook entry.  <i>This functionality is included by default.</i></li>
        <li>&bull; <a href="http://wordpress.org/extend/plugins/ozhs-ip-to-nation/">Ozh's IP To Nation</a>: Show a national flag in each visitor's guestbook entry.  <i>This plugin must be installed separately.</i></li>
      </ul>
    </div>
    <?php
}

?>