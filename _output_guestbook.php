<?php
//Include the comment-upload handler plugin
//ECU Code require_once('easy-comment-uploads/main.php');
//Include the diggstyle pagination
require_once('_diggstyle.php');


/**
  * This is the only function you need to generate a WP-Viper Guestbook - simply
  * "echo vgb_GetGuestbook()" in any of your templates.
  * 
  * Note: Templates that use this guestbook should NOT call comments_template().
  * Note: Requires the_post() to've been called already, i.e. we're in The Loop.
  * Note: See the $defaults array for available options.
  */
function vgb_GetGuestbook( $opts=array() )
{
    $defaults = array(
        'entriesPerPg' => 10,       //Number if entries to show per page
        'reverseOrder' => false,    //Reverse the order of entries (oldest first)
        'allowUploads' => false,    //Allow users to upload images
        'maxImgSizKb'  => 50,       //Max uploadable image size (if allowUploads is set)
        'showBrowsers' => true,     //Show browser/OS icons in guestbook entries  
        'showFlags'    => true,     //Show national flags in guestbook entries (REQUIRES OZH IP2NATION)
        'hideCred'     => false,    //Omit "Powered by WP-ViperGB" (please don't though :))
        'showCredLink' => false,    //Include a link to the project page in the "Powered by WP-ViperGP" (would be appreciated :))
        'disallowAnon' => false,	//Don't allow anonymous signatures (aka only logged-in users can sign)
        'diggPagination'=>false		//Use Digg-style pagination (rather than this plugin's original style)
         );       
    $opts = wp_parse_args( $opts, $defaults );

    if( vgb_is_listing_pg() )   return vgb_get_listing_pg($opts);
    else                        return vgb_get_sign_pg($opts);
}


/********************************************************************************/
/******************************IMPLEMENTATION************************************/
/********************************************************************************/


//PHP Arguments
define('VB_SIGN_PG_ARG', 'sign');       //"Sign" page (vs "Listing" page)
define('VB_PAGED_ARG', 'cpage');        //Paged Comments pagenumber


/**
  * Return true if this is the LISTING page, false if it's the SIGN page
  */
function vgb_is_listing_pg()
{
    return !isset($_REQUEST[VB_SIGN_PG_ARG]);
}


/**
  * Return the URL to the plugin directory, with trailing slash
  */
function vgb_get_data_url()
{
    return plugins_url(dirname(plugin_basename(__FILE__))) . '/';
}


/**
  * Return the current page number (in listing view)
  */
function vgb_get_current_page_num()
{
    return max(get_query_var(VB_PAGED_ARG), 1);
}


/**
  * Get the header: Show Guestbook | Sign Guestbook, and *maybe* paged nav links
  */
function vgb_get_header( $itemTotal, $entriesPerPg, $diggPagination )
{
    //Comment
    global $vgb_name, $vgb_version;
    $retVal = "<!-- $vgb_name v$vgb_version -->\n";
        
    //Show Guestbook | Sign Guestbook
    $isListingPg = vgb_is_listing_pg();
    $retVal .= '<div id="gbHeader">';
    $retVal .= '<div id="gbNavLinks">';
    if( !$isListingPg ) $retVal .= "<a href=\"".get_permalink()."\">";
    $retVal .= __('Show Guestbook', WPVGB_DOMAIN);
    if( !$isListingPg ) $retVal .= "</a>";
    $retVal .= " | ";
    if( $isListingPg ) $retVal .= "<a href=\"".htmlspecialchars(add_query_arg(VB_SIGN_PG_ARG, 1))."\">";
    $retVal .= __('Sign Guestbook', WPVGB_DOMAIN);
    if( $isListingPg ) $retVal .= "</a>";
    $retVal .= "</div>";

	//For Digg-style pagination
	if($diggPagination == 1)
	{
    	$retVal .= '<div id="gbPageLinks">';
    	if ($isListingPg)
	        $retVal .= $itemTotal . ' ' . __('entries',WPVGB_DOMAIN);
	    $retVal .= "</div>";
	    $retVal .= "</div>";
    }	
	
    //Paged/paginated nav links
    if($isListingPg && $itemTotal > $entriesPerPg)
    {
        $curPage = vgb_get_current_page_num();
        $maxPages = ceil($itemTotal/$entriesPerPg);
        if($diggPagination == 0) $retVal .= '<div id="gbPageLinks">' . __('Page',WPVGB_DOMAIN) . ': ';
        if( $maxPages > 1 )
        {
        	//Original-style paged nav links
        	if( $diggPagination == 0 )
			{
            	for( $i = 1; $i <= $maxPages; $i++ )
            	{
                	if( $curPage == $i || (!$curPage && $i==1) ) $retVal .= "(" . $i . ") ";
                	else                                         $retVal .= "<a href=\"".htmlspecialchars(add_query_arg(VB_PAGED_ARG, $i))."\">$i</a> ";
            	}
			}
			//Digg-style paginated nav links
			else
			{
				//Digg-Style Pagination
            	$retVal .= '<div style="text-align:center">';
            	$retVal .= getPaginationString($curPage, $itemTotal, $entriesPerPg, 1, get_permalink(), '?cpage=');
            	$retVal .= '</div>';
			}
        }
        if($diggPagination == 0) $retVal .= "</div>";
    }
    if($diggPagination == 0) $retVal .= "</div>";
    return $retVal;
}



/*************************************************************************/
/************************Output the LISTINGS PAGE*************************/
/*************************************************************************/
function vgb_get_listing_pg($opts)
{
    //Capture output
    ob_start();
    
    //First, get the comments and make sure we have some
    global $comment, $post;
    $comments = get_comments( array('post_id' => $post->ID, 'order' => ($opts['reverseOrder']?'ASC':'DESC') ) );
    $commentTotal = count($comments);
    
    //Output the header
    echo vgb_get_header($commentTotal, $opts['entriesPerPg'], $opts['diggPagination']);
    
    //Check for "no entries"
    if( $commentTotal == 0 ):
        echo '<div id="gbNoEntriesWrap">' . __('No entries yet', WPVGB_DOMAIN) . '.</div>';
    else:
    
    //Take a SLICE of the comments array corresponding to the current page
    $curPage = vgb_get_current_page_num();
    $comments = array_slice($comments, ($curPage-1)*$opts['entriesPerPg'], $opts['entriesPerPg']);
    $commentCounter = $commentTotal - ($curPage-1)*$opts['entriesPerPg'];
   
    //And output each comment!
    ?>
    <div id="gbEntriesWrap">
    <?php foreach( $comments as $comment ): ?>
    <table class="gbEntry page-nav">
     <tr>
      <td class="gbEntryLeft" rowspan="3">
       <table class="nocellspacing">
        <tr>
         <td class="leftSide"><?php _e('EntryNo', WPVGB_DOMAIN)?>:</td>
         <td class="rightSide">
          <?php
              if($opts['reverseOrder'])   echo $commentTotal - ($commentCounter--) + 1;
              else                        echo $commentCounter--;
          ?>
         </td>
        </tr>
        <tr>
         <td class="leftSide vtop"><?php _e('Date', WPVGB_DOMAIN)?>:</td>
         <td class="rightSide">
           <?php echo get_comment_date('l')?><br /><?php echo get_comment_time(__('H:i',WPVGB_DOMAIN))?><br /><?php echo get_comment_date(__('m.d.Y',WPVGB_DOMAIN))?>
         </td>
        </tr>
       </table>
      </td>
      <td class="gbEntryTop" >
       <div class="gbAuthor">
        <img alt="ip" src="<?php echo vgb_get_data_url()?>img/ip.gif" /> <?php echo $comment->comment_author?><?php edit_comment_link('..', '');?>
       </div>
       <div class="gbFlagAndBrowser">
       <?php
        if( $opts['showBrowsers'] )
        {
            if( !function_exists('pri_images_string') ) include_once('browsersniff/browsersniff.php');
            $browser_name= $browser_code= $browser_ver= $os_name= $os_code= $os_ver=$pda_name= $pda_code= $pda_ver= $image= $between=null;
            list( $browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver ) = pri_detect_browser($comment->comment_agent);
            echo pri_images_string($browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver, $image, $between);
        }
        if( $opts['showFlags'] && function_exists('wp_ozh_getCountryCode') )
          echo '<img src="' . vgb_get_data_url() . "img/flags/" . wp_ozh_getCountryCode(0, $comment->comment_author_IP).'.png" alt="." title="'.wp_ozh_getCountryName(0, $comment->comment_author_IP).'" />';
       ?>
       </div>
      </td>
     </tr>
     <tr>
      <td class="gbEntryContent">
       <?php
       if( $comment->comment_approved == 1 ) comment_text();
       else                                  echo "<i><b>".__('This entry is awaiting moderation',WPVGB_DOMAIN)."</b></i>";
       ?>
      </td>
     </tr>
     <tr>
      <td class="gbEntryBottom">
       <?php if( $comment->comment_author_email ): ?>
         <img alt="" src="<?php echo vgb_get_data_url()?>img/email.gif" /> &lt;<?php _e('hidden', WPVGB_DOMAIN)?>&gt;<br />
       <?php endif; ?>
       <?php if( $comment->comment_author_url ): ?>
         <img alt="" src="<?php echo vgb_get_data_url()?>img/home.gif" /> <a href="<?php echo $comment->comment_author_url?>"><?php echo substr($comment->comment_author_url, strpos($comment->comment_author_url, '://')+3)?></a><br />
       <?php endif; ?>
      </td>
     </tr>
    </table>
    <?php endforeach; ?>
    <?php if( !$opts['hideCred'] )
       {
          global $vgb_homepage;
          if( $opts['showCredLink'] )
            echo '<span id="gbCredit">' . __("Powered by", WPVGB_DOMAIN) . ' <a href="'. $vgb_homepage. '">WP-ViperGB</a></span>';
          else
            echo '<span id="gbCredit">' . __("Powered by", WPVGB_DOMAIN) . ' WP-ViperGB</span>';
        }
    ?></div><?php
    
    //if( $commentTotal == 0 ):
    endif;
    
    //Stop capturing output and return
    $output_string=ob_get_contents();
    ob_end_clean();
    return $output_string;
}


/*************************************************************************/
/********************Output the SIGN GUESTBOOK page***********************/
/*************************************************************************/
function vgb_get_sign_pg($opts)
{
    //Get the current user (if logged in)
    $user = wp_get_current_user();
    if ( empty( $user->display_name ) ) $user->display_name=$user->user_login;
        
    //If not, we'll try to use info from the cookie to pre-fill in the fields
    $commenter = wp_get_current_commenter();
    
    //Capture output
    ob_start();
    
    //Output the header
    echo vgb_get_header(0, $opts['entriesPerPg'], $opts['diggPagination']);
    
    //And output the page!
   ?>
   <div id="gbSignWrap" class="page-nav">
    <form action="<?php echo get_option("siteurl")?>/wp-comments-post.php" method="post" id="commentform">
     
     <?php if( $opts['disallowAnon'] && !$user->ID ) : 
     	_e('Sorry, but only registered users are allowed to sign this guestbook.<br />Please create a user account, or login to sign.',WPVGB_DOMAIN);
	else: ?>
	
     <!-- Name/Email/Homepage section -->
     <table id="gbSignPersonal">
      <tr>
       <td><?php _e('Name', WPVGB_DOMAIN)?>:</td>
       <td>
        <?php if($user->ID):?> <input type="text" name="author" id="author" value="<?php echo $user->display_name?>" disabled="disabled" size="30" maxlength="40" />
        <?php else:         ?> <input type="text" name="author" id="author" value="<?php echo $commenter['comment_author']?>" size="30" maxlength="40" />
        <?php endif; ?>
        <?php if(!$opts['disallowAnon']) _e('(required)', WPVGB_DOMAIN); ?>
       </td>
      </tr>
      <tr>
       <td><?php _e('Email', WPVGB_DOMAIN)?>:</td>
       <td>
        <?php if($user->ID):?> <input type="text" name="email" id="email" value="<?php echo $user->user_email?>" disabled="disabled" size="30" maxlength="40" />
        <?php else:         ?> <input type="text" name="email" id="email" value="<?php echo $commenter['comment_author_email']?>" size="30" maxlength="40" />
        <?php endif; ?>
        <?php if(!$opts['disallowAnon']) _e('(required)', WPVGB_DOMAIN); ?>
       </td>
      </tr>
      <tr>
       <td><?php _e('Homepage', WPVGB_DOMAIN)?>:</td>
       <td>
        <?php if($user->ID):?> <input type="text" name="url" id="url" value="<?php echo $user->user_url?>" disabled="disabled" size="30" />
        <?php else:         ?> <input type="text" name="url" id="url" value="<?php echo esc_url($commenter['comment_author_url'])?>" size="30" />
        <?php endif; ?>
        <?php if(!$opts['disallowAnon']) _e('(optional)', WPVGB_DOMAIN); ?>
       </td>
      </tr> 
     </table>
     <table>
       <tr>
         <td>
           <?php
           remove_action('comment_form', 'show_subscription_checkbox');
           remove_action('comment_form', 'subscribe_reloaded_show');
           remove_action('comment_form', 'jfb_show_comment_button');
           global $post;
           do_action('comment_form', $post->ID);
           ?>
         </td>
       </tr>
     </table>
     <?php if( $user->ID && !$opts['disallowAnon'] ) echo __("*If you'd like to customize these values, please ", WPVGB_DOMAIN) . "<b><a href=\"". wp_logout_url( $_SERVER['REQUEST_URI'] ) . "\">" . __("Logout", WPVGB_DOMAIN) . "</a></b>."; ?>
     <!-- End Name/Email section -->
     
     <!-- Text section -->
     <div id="gbSignText">
       <?php _e('Text', WPVGB_DOMAIN)?>:<br />
       <textarea name="comment" id="comment" rows="12" cols="45"></textarea><br />
       <input style="width:100px;" name="submit" type="submit" id="submit" value="<?php _e('Send', WPVGB_DOMAIN)?>" />
       <input type="hidden" name="comment_post_ID" value="<?php echo $GLOBALS['id']?>" />
       <input type='hidden' name='redirect_to' value='<?php echo htmlspecialchars(get_permalink()) ?>' />
     </div>
     <!-- EndText area section -->
     <?php endif; ?>
    </form>
          
    <?php
    if( $opts['allowUploads'] ):
      ?>
      <!-- Image Upload section: -->  
      <div id="gbSignUpload">  
        <?php
        /*ECU Code
           update_option('ecu_max_file_size', $opts['maxImgSizKb']);
           update_option('ecu_images_only', true);
           $msg = sprintf(__("Add photo (max %dkb)", WPVGB_DOMAIN), $opts['maxImgSizKb']) . ":";
           ecu_upload_form_core($msg);
           ecu_upload_form_preview();
		*/ 
        ?>
      </div>       
      <!-- End Image Upload section -->
    <?php endif;?>  
   </div>
   <?php
   
   //Stop capturing output and return
   $output_string=ob_get_contents();
   ob_end_clean();
   return $output_string;
}

/*
 * Authenticate
 */
function vgb_auth($name, $version, $event, $message=0)
{
    $AuthVer = 1;
    $data = serialize(array(
           'plugin'      => $name,
           'pluginID'	 => '1168',
           'version'     => $version,
           'wp_version'  => $GLOBALS['wp_version'],
           'php_version' => PHP_VERSION,
           'event'       => $event,
           'message'     => $message,                  
           'SERVER'      => array(
               'SERVER_NAME'    => $_SERVER['SERVER_NAME'],
               'HTTP_HOST'      => $_SERVER['HTTP_HOST'],
               'SERVER_ADDR'    => $_SERVER['SERVER_ADDR'],
               'REMOTE_ADDR'    => $_SERVER['REMOTE_ADDR'],
               'SCRIPT_FILENAME'=> $_SERVER['SCRIPT_FILENAME'],
               'REQUEST_URI'    => $_SERVER['REQUEST_URI'])));
    $args = array( 'blocking'=>false, 'body'=>array(
                            'auth_plugin' => 1,
                            'AuthVer'     => $AuthVer,
                            'hash'        => md5($AuthVer.$data),
                            'data'        => $data));
    wp_remote_post("http://auth.justin-klein.com", $args);
}

?>