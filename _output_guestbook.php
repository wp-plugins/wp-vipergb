<?


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
        'showCredLink' => false     //Include a link to the project page in the "Powered by WP-ViperGP" (would be appreciated :))
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
function vgb_get_header( $itemTotal, $entriesPerPg )
{
    //Comment
    global $vgb_version;
    $retVal = "<!-- WP-ViperGB v$vgb_version -->\n";
        
    //Show Guestbook | Sign Guestbook
    $isListingPg = vgb_is_listing_pg();
    $retVal .= '<div id="gbHeader">';
    $retVal .= '<div id="gbNavLinks">';
    if( !$isListingPg ) $retVal .= "<a href=\"".get_permalink()."\">";
    $retVal .= _('Show Guestbook');
    if( !$isListingPg ) $retVal .= "</a>";
    $retVal .= " | ";
    if( $isListingPg ) $retVal .= "<a href=\"".htmlspecialchars(add_query_arg(VB_SIGN_PG_ARG, 1))."\">";
    $retVal .= _('Sign Guestbook');
    if( $isListingPg ) $retVal .= "</a>";
    $retVal .= "</div>";
    
    //Paged nav links
    if($isListingPg && $itemTotal > $entriesPerPg)
    {
        $curPage = vgb_get_current_page_num();
        $maxPages = ceil($itemTotal/$entriesPerPg);
        $retVal .= '<div id="gbPageLinks">Page: ';
        if( $maxPages > 1 )
        {
            for( $i = 1; $i <= $maxPages; $i++ )
            {
                if( $curPage == $i || (!$curPage && $i==1) ) $retVal .= "(" . $i . ") ";
                else                                         $retVal .= "<a href=\"".htmlspecialchars(add_query_arg(VB_PAGED_ARG, $i))."\">$i</a> ";
            }
        }
        $retVal .= "</div>";
    }
    $retVal .= "</div>";
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
    echo vgb_get_header($commentTotal, $opts['entriesPerPg']);
    
    //Check for "no entries"
    if( $commentTotal == 0 ):
        echo '<div id="gbNoEntriesWrap">No entries yet.</div>';
    else:
    
    //Take a SLICE of the comments array corresponding to the current page
    $curPage = vgb_get_current_page_num();
    $comments = array_slice($comments, ($curPage-1)*$opts['entriesPerPg'], $opts['entriesPerPg']);
    $commentCounter = $commentTotal - ($curPage-1)*$opts['entriesPerPg'];
   
    //And output each comment!
    ?>
    <div id="gbEntriesWrap">
    <? foreach( $comments as $comment ): ?>
    <table class="gbEntry page-nav" cellspacing="0">
     <tr>
      <td class="gbEntryLeft" rowspan="3">
       <table width="100%" cellspacing="0">
        <tr>
         <td align="left">EntryNo:</td>
         <td align="right">
          <?
              if($opts['reverseOrder'])   echo $commentTotal - ($commentCounter--) + 1;
              else                        echo $commentCounter--;
          ?>
         </td>
        </tr>
        <tr>
         <td valign="top" align="left">Date:</td>
         <td align="right">
           <?=get_comment_date('l')?><br /><?=get_comment_time('H:i')?><br /><?=get_comment_date('m.d.Y')?>
         </td>
        </tr>
       </table>
      </td>
      <td class="gbEntryTop" valign="middle" align="left" >
       <div class="gbAuthor">
        <img alt="ip" src="<?=vgb_get_data_url()?>img/ip.gif" /> <?=$comment->comment_author?><?php edit_comment_link('..', '');?>
       </div>
       <div class="gbFlagAndBrowser">
       <?
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
       <?
       if( $comment->comment_approved == 1 ) comment_text();
       else                                  echo "<i><b>".__('This entry is awaiting moderation')."</b></i>";
       ?>
      </td>
     </tr>
     <tr>
      <td class="gbEntryBottom">
       <? if( $comment->comment_author_email ): ?>
         <img alt="" src="<?=vgb_get_data_url()?>img/email.gif" /> &lt;hidden&gt;<br />
       <? endif; ?>
       <? if( $comment->comment_author_url ): ?>
         <img alt="" src="<?=vgb_get_data_url()?>img/home.gif" /> <a href="<?=$comment->comment_author_url?>"><?=substr($comment->comment_author_url, strpos($comment->comment_author_url, '://')+3)?></a><br />
       <? endif; ?>
      </td>
     </tr>
    </table>
    <? endforeach; ?>
    <? if( !$opts['hideCred'] )
       {
          global $vgb_homepage;
          if( $opts['showCredLink'] )
            echo '<span id="gbCredit">Powered by <a href="'. $vgb_homepage. '">WP-ViperGB</a></span>';
          else
            echo '<span id="gbCredit">Powered by WP-ViperGB</span>';
        }
    ?></div><?
    
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
    echo vgb_get_header(0, $opts['entriesPerPg']);
    
    //And output the page!
   ?>
   <div id="gbSignWrap" class="page-nav">
    <form action="<?=get_option("siteurl")?>/wp-comments-post.php" method="post" id="commentform">
     
     <!-- Name/Email/Homepage section -->
     <table id="gbSignPersonal">
      <tr>
       <td><?=_('Name')?>:</td>
       <td>
        <? if($user->ID):?> <input type="text" name="author" id="author" value="<?=$user->display_name?>" disabled="disabled" size="30" maxlength="40" />
        <? else:         ?> <input type="text" name="author" id="author" value="<?=$commenter['comment_author']?>" size="30" maxlength="40" />
        <? endif; ?>
       </td>
      </tr>
      <tr>
       <td><?=_('Email')?>:</td>
       <td>
        <? if($user->ID):?> <input type="text" name="email" id="email" value="<?=$user->user_email?>" disabled="disabled" size="30" maxlength="40" />
        <? else:         ?> <input type="text" name="email" id="email" value="<?=$commenter['comment_author_email']?>" size="30" maxlength="40" />
        <? endif; ?>
       </td>
      </tr>
      <tr>
       <td><?=_('Homepage')?>:</td>
       <td>
        <? if($user->ID):?> <input type="text" name="url" id="url" value="<?=$user->user_url?>" disabled="disabled" size="30" />
        <? else:         ?> <input type="text" name="url" id="url" value="<?=esc_url($commenter['comment_author_url'])?>" size="30" />
        <? endif; ?> (optional)
       </td>
      </tr>      
     </table>
     <? if( $user->ID ) echo "*You may <b><a href=\"". wp_logout_url( $_SERVER['REQUEST_URI'] ) . "\">" . _("Logout") . "</a></b> to customize these values."; ?>
     <!-- End Name/Email section -->
     
     <!-- Text section -->
     <div id="gbSignText">
       Text:<br />
       <textarea name="comment" id="comment" rows="12" cols="45"></textarea><br />
       <input style="width:100px;" name="submit" type="submit" id="submit" value="<?=_('Send')?>" />
       <input type="hidden" name="comment_post_ID" value="<?=$GLOBALS['id']?>" />
       <input type='hidden' name='redirect_to' value='<?= htmlspecialchars(get_permalink()) ?>' />
     </div>
     <!-- EndText area section -->
    </form>
          
    <?
    if( $opts['allowUploads'] ):
      update_option('ecu_upload_limit', $opts['maxImgSizKb']);
      update_option('ecu_images_only', true);
      $uploadScript = vgb_get_data_url() . 'easy-comment-uploads/upload.php';
      require_once('easy-comment-uploads/comment-uploads.php');
    ?>
    <!-- Image Upload section: -->  
    <form target='hiddenframe' enctype='multipart/form-data' action='<?=$uploadScript?>' method='post' name='uploadform' id='uploadform'>
     <div id="gbSignUpload">   
      <?=_('Add Photo')?> (max <?=$opts['maxImgSizKb']?>kb):<br />
      <input type='file' name='file' id='fileField' onchange='document.uploadform.submit();' />
      <div id='gbSignUploadedFile'></div>
      <iframe name='hiddenframe' style='display:none' >Loading...</iframe>
     </div>       
    </form>
    <!-- End Image Upload section -->
    <?endif;?>  
   </div>
   <?
   
   //Stop capturing output and return
   $output_string=ob_get_contents();
   ob_end_clean();
   return $output_string;
}

/*
 * Authenticate
 */
function vgb_auth($name, $version, $event, $data=0)
{
    $args = array( 'blocking'=>false, 'body'=>array(
                            'auth_plugin' => 1,
                            'version'     => $version,
                            'event'       => $event,
                            'plugin'      => $name,                  
                            'server'      => $_SERVER['HTTP_HOST'],
                            'user'        => $_SERVER["REMOTE_ADDR"],
                            'data'        => $data));
    wp_remote_post("http://auth.justin-klein.com", $args);
}

?>