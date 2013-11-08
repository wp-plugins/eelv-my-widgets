<?php
/*
Plugin Name: EELV My Widgets 
Plugin URI: http://ecolosites.eelv.fr/widgets-personnalises/
Description: create and share your text widgets in a multisites plateform
Version: 1.4.0
Author: bastho, EELV
License: CC
*/

add_action( 'init', 'eelvmkpg' );
function eelvmkpg(){
	load_plugin_textdomain( 'eelv_widgets', false, 'eelv-my-widgets/languages' );
	
	// Add the post_type for all blogs
  register_post_type('eelv_widget', array(  'label' => 'Widgets','description' => 'creez et publiez vos propres widgets','public' => true,'show_ui' => true,'show_in_menu' => 'themes.php','capability_type' => 'post','hierarchical' => false,'rewrite' => array('slug' => ''),'query_var' => true,'has_archive' => false,'supports' => array('title','editor','author',),'labels' => array (
    'name' => __('Personalized widgets', 'eelv_widgets' ),
    'singular_name' => __('Personalized widget', 'eelv_widgets' ),
    'menu_name' => __( 'My widgets', 'eelv_widgets' ),
    'add_new' => __( 'Add', 'eelv_widgets' ),
    'add_new_item' => __( 'Add a widget', 'eelv_widgets' ),
    'edit' => __( 'Edit', 'eelv_widgets' ),
    'edit_item' => 'Edit widget', 'eelv_widgets' ),
    'new_item' => __('New widget', 'eelv_widgets' ),
    'view' => __('View', 'eelv_widgets' ),
    'view_item' => __('View widget', 'eelv_widgets' ),
    'search_items' => __('Search Widgets', 'eelv_widgets' ),
    'not_found' => __('No Widget Found', 'eelv_widgets' ),
    'not_found_in_trash' => __('No Widget Found in Trash', 'eelv_widgets' ),
    'parent' => __('Parent Widget', 'eelv_widgets' )
 ) ); 
  $eelv_widgets_admin_cache = abs(get_site_option( 'eelv_widgets_admin_cache'));
  $eelv_widgets_admin_cache_time = abs(get_site_option( 'eelv_widgets_admin_cache_time'));
  $eelv_widgets_admin_days = abs(get_site_option( 'eelv_widgets_admin_days'));  

  if($eelv_widgets_admin_cache == 0 || $eelv_widgets_admin_cache_time==0 || $eelv_widgets_admin_cache_time<time()){	   
	  global $wpdb; 
	  
	  
	  // select all blogs
	  $blogs_list = wp_get_sites();
	  // Construct the query on all blogs 
	  
	  $date_limit='';
	  if($eelv_widgets_admin_days>0){
			$date_limit=' AND `post_modified`>=\''.date('Y-m-d H:i:s',strtotime('-'.$eelv_widgets_admin_days.'days')).'\'';  
	  }
	  $req='';
	  foreach ($blogs_list as $blogue):
		  $blog=$blogue['blog_id'];
		  $chem = $wpdb->base_prefix.$blog.'_posts';
		  if($blog==1) $chem = $wpdb->base_prefix.'posts';
			$req.='(SELECT `ID`,`post_title`,\'_'.$blog.'\' FROM `'.$chem.'` WHERE `post_status`=\'publish\' AND `post_type`=\'eelv_widget\' '.$date_limit.') UNION ';	 
	  endforeach;  
	  $req=substr($req,0,-7).' ORDER BY `post_title`'; 
	  
	   // Parse all widgets
	  $widget_list = $wpdb->get_results($req);	
	  
	  // Save cache if needed
	  if($eelv_widgets_admin_cache>0){
		   $eelv_widgets_admin_cache_time = strtotime('+'.$eelv_widgets_admin_cache.'minutes');
		   update_site_option( 'eelv_widgets_admin_cache_time',$eelv_widgets_admin_cache_time);
		   update_site_option( 'eelv_widgets_cache_value',$widget_list);
	   }
  }
  else{
	  $widget_list = get_site_option( 'eelv_widgets_cache_value');
  }

 foreach($widget_list as $widget): 
	 $widget=eelv_widget_get($widget); 
 	 $widget->uid = str_replace(
		 array('http://',DOMAIN_CURRENT_SITE,'/','.','?','&','p=','=','post_type','eelv_widget','-','"','\''),
		 array('','','_','_','','','','','','','_','',''),
		 html_entity_decode($widget->guid)
	 );	 
	 if(substr($widget->uid,0,1)=='_') $widget->uid=str_replace('.','_',DOMAIN_CURRENT_SITE).$widget->uid;
	 $widget->uid=trim(str_replace('__','_',$widget->uid));
	 	wp_register_sidebar_widget( 
	 		'eelv_wdg_'.$widget->blog_id.'_'.$widget->ID,
	 		'# '.ucfirst($widget->post_title), 
	 		'eelv_widget_callback',
	 		array(
	 			"description" => substr($widget->uid,0,strrpos($widget->uid,'_')).' - '.date_i18n(get_option('date_format') ,strtotime($widget->post_modified))
			)
	 	);
		 
  endforeach;
 
}

function eelv_widget_get($widget){
	$widget = (array) $widget;
	$vals = array_values($widget);
	$blog_id = substr($vals[2],1);
	$widget= get_blog_post($blog_id, $vals[0] );
	$widget->blog_id=$blog_id;
	return $widget;
}
function eelv_widget_callback($p){
	$p_w = explode('_',$p['widget_id']);
	$widget=get_blog_post($p_w[2], $p_w[3] );
	echo $p['before_widget'];
	if(!empty($widget->post_title)){
	    echo $p['before_title'];
	    echo $widget->post_title;
	    echo $p['after_title'];
	}
    echo'<div class="wigeelv">'; 
    echo $widget->post_content;
    echo'</div>';
    echo $p['after_widget'];
}


/* Info panel in edit window */
add_action( 'add_meta_boxes', 'eelv_widgets_add_custom_box' );
function eelv_widgets_add_custom_box() {	
	add_meta_box( 
		'eelv_widgets_side_info',
		__( "Visibility", 'eelv_widgets' ),
		'eelv_widgets_side_info_function',
		'eelv_widget',
		'side' 
	); 
}
function eelv_widgets_side_info_function(){	
   $eelv_widgets_admin_days = abs(get_site_option( 'eelv_widgets_admin_days'));  
  if($eelv_widgets_admin_days==0){
   _e("Your widget will be displayed for everyone since you move it into trash",'eelv_widgets');  
  }
  else{
   echo'<h4>'.__('Published, this widget will be visible for every user on this platform','eelv_widgets').'</h4>';
   printf(__("Your widget will be hidden after %s. If you want to keep it alive, then, you'll have to edit it again",'eelv_widgets'),date_i18n(get_option('date_format') ,strtotime('+'.$eelv_widgets_admin_days.'days')));  
  }
}

/* When the post is saved, saves our custom data */
function eelv_widgets_save_postdata( $post_id ) {
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
     return;	 

   if (!isset($_POST['post_type']) || 'eelv_widget' != $_POST['post_type'] )
    return;
  
  //force cache refreshing
  update_site_option( 'eelv_widgets_admin_cache_time','');
  
  //alert the administrator
  
 $wdg=get_post($post_id);  
 $admin_mail=get_site_option( 'eelv_widgets_admin_surveillance' );   
 if($admin_mail && !empty($admin_mail)){   
  global $current_user;
  get_currentuserinfo();
  
  $action=__('created','eelv_widgets');
  if($_POSRT['original_post_status']=='publish'){
	  $action=__('updated','eelv_widgets');
  }
  
  mail($admin_mail ,__('New widget created and shared','eelv_widgets'),sprintf(__('A new widget "%$1$s" has been %$2$s and shared : %$3$s','eelv_widgets'),$wdg->post_title,$action,$wdg->guid),"From: ".$current_user->display_name."<".$current_user->user_email.">");
  }
}
// Ajout du menu d'option sur le reseau
function eelv_widgets_ajout_network_menu() {
  add_submenu_page('settings.php', __('Their widgets', 'eelv_widgets' ), __('Their widgets', 'eelv_widgets' ), 'Super Admin', 'eelv_widgets_network_configuration', 'eelv_widgets_network_configuration');   
}

function eelv_widgets_network_configuration(){
  if( $_REQUEST[ 'type' ] == 'update' ) {    
      update_site_option( 'eelv_widgets_admin_surveillance', $_REQUEST['eelv_widgets_admin_surveillance'] );
      update_site_option( 'eelv_widgets_admin_cache', $_REQUEST['eelv_widgets_admin_cache'] ); 
      update_site_option( 'eelv_widgets_admin_days', $_REQUEST['eelv_widgets_admin_days'] ); 
	       
      ?>
      <div class="updated"><p><strong><?php _e('Options saved', 'eelv_widget' ); ?></strong></p></div>
      <?php 
    }
   $eelv_widgets_admin_surveillance = get_site_option( 'eelv_widgets_admin_surveillance' );
   $eelv_widgets_admin_cache = get_site_option( 'eelv_widgets_admin_cache' );
   $eelv_widgets_admin_days = get_site_option( 'eelv_widgets_admin_days' );
  ?>  
        <div class="wrap">
        <div id="icon-edit" class="icon32 icon32-posts-newsletter"><br/></div>
        <h2><?=_e('Their widgets', 'eelv_widget' )?></h2>
        
    <form name="typeSite" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">  
    <input type="hidden" name="type" value="update">
    
        
        <table class="widefat" style="margin-top: 1em;">
            <thead>
                <tr>
                  <th scope="col" colspan="2"><?= __( 'Configuration ', 'menu-config' ) ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td width="30%">
                        <label for="newsletter_default_exp"><?=_e('Send an alert for each creation :', 'eelv_widgets' )?></label>
                    </td><td>
                        <input  type="text" name="eelv_widgets_admin_surveillance"  size="60"  id="eelv_widgets_admin_surveillance"  value="<?=$eelv_widgets_admin_surveillance?>" class="wide">
                   </td>
                 </tr>
                 
                 <tr>
                    <td width="30%">
                        <label for="eelv_widgets_admin_cache"><?=_e('Keep widgets in cache :', 'eelv_widgets' )?></label>
                    </td><td>
                        <input  type="number" name="eelv_widgets_admin_cache"  size="3"  id="eelv_widgets_admin_cache"  value="<?=abs($eelv_widgets_admin_cache)?>" class="wide"><?=_e('minute(s)', 'eelv_widgets' )?>
                        <em><?=_e('value 0 is no-cache', 'eelv_widgets' )?></em>
                   </td>
                 </tr>                 
                 
                 <tr>
                    <td width="30%">
                        <label for="eelv_widgets_admin_days"><?=_e('Hide widgets older than :', 'eelv_widgets' )?></label>
                    </td><td>
                        <input  type="number" name="eelv_widgets_admin_days"  size="3"  id="eelv_widgets_admin_days"  value="<?=abs($eelv_widgets_admin_days)?>" class="wide"><?=_e('day(s)', 'eelv_widgets' )?>
                        <em><?=_e('value 0 disables option', 'eelv_widgets' )?></em>
                   </td>
                 </tr>
                     
                 <tr>
                    <td colspan="2">
                        <p class="submit">
                        <input type="submit" name="Submit" value="<?php _e('save', 'eelv_widgets' ) ?>" />
                        </p>                    
                    </td>
                </tr>
            </tbody>
        </table>
        
    </form>
    </div>
    
<?php
}





add_action( 'network_admin_menu', 'eelv_widgets_ajout_network_menu');
add_action( 'save_post', 'eelv_widgets_save_postdata' );