<?php
/*
Plugin Name: EELV My Widgets 
Plugin URI: http://ecolosites.eelv.fr
Description: create and share your text widgets in a multisites plateform
Version: 1.0
Author: Bastien Ho, EELV
License: CC
*/
function eelvmkpg(){
	load_plugin_textdomain( 'eelv_widgets', false, 'eelv_widgets/languages' );
	
	// Add the post_type for all blogs
  register_post_type('eelv_widget', array(  'label' => 'Widgets','description' => 'creez et publiez vos propres widgets','public' => true,'show_ui' => true,'show_in_menu' => 'themes.php','capability_type' => 'post','hierarchical' => false,'rewrite' => array('slug' => ''),'query_var' => true,'has_archive' => false,'supports' => array('title','editor','revisions','thumbnail','author',),'labels' => array (
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
  
  
  global $wpdb; 
  
  
  // select all blogs
   $sql = "SELECT `blog_id` FROM `$wpdb->blogs` WHERE  `public`=1 AND  `archived` =  '0' AND  `mature` =0 AND  `spam` =0 AND  `deleted` =0 ORDER BY `domain` ";
   $blogs_list = $wpdb->get_col($wpdb->prepare($sql));

  // Construct the query on all blogs 
  $req="";
  foreach ($blogs_list as $blog):
	  $chem = $wpdb->prefix.$blog.'_posts';
	  if($blog==1) $chem = $wpdb->prefix.'posts';
  		$req.="SELECT `post_author`, `post_date`, `post_content`,`post_name`,`guid`,`post_title` FROM `".$chem."` WHERE `post_status`='publish' AND `post_type`='eelv_widget'
	UNION
	";	 
  endforeach;  
  $req=substr($req,0,-7)." ORDER BY `post_title`"; 
  
   // Parse all widgets
  $widget_list = $wpdb->get_results($req);
  foreach($widget_list as $widget):  
 	 $widget->uid = str_replace(
		 array('http://',DOMAIN_CURRENT_SITE,'/','.','?','&','p=','=','post_type','eelv_widget'),
		 array('','','_','_','','','','','',''),
		 html_entity_decode($widget->guid)
	 );	 
	 if(substr($widget->uid,0,1)=='_') $widget->uid=str_replace('.','_',DOMAIN_CURRENT_SITE).$widget->uid;
	 
	 	$construct='
          wp_register_sidebar_widget( "eelv_widget'.$widget->uid.'","# '.str_replace('é','e',$widget->post_title).'", "eelv_widget'. $widget->uid.'",array("description" => "'.$widget->uid.' ('.$widget->post_date.') "));          
          
          function eelv_widget'. $widget->uid.'($params) {
            echo $params[\'before_widget\'];
            echo $params[\'before_title\'];
            echo "'.$widget->post_title.'";
            echo $params[\'after_title\'];
            echo\'<div class="wigeelv">\'; 
            echo $params[\'before_content\'];
            echo "'.str_replace('"','\"',$widget->post_content).'<div class=\"clear\"></div>";
            echo $params[\'after_content\'];
            echo\'</div>\';
            echo $params[\'after_widget\'];
          }          
          ';
		  eval($construct);
  endforeach;
 
}
add_action( 'init', 'eelvmkpg' );

/* When the post is saved, saves our custom data */
function eelv_widgets_save_postdata( $post_id ) {
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
     return;

  if ( !wp_verify_nonce( $_POST['eelv_widget_noncename'], plugin_basename( __FILE__ ) ) )
   return ;

  if ( 'eelv_widget' != $_POST['post_type'] )
    return;
  
  //alert the administrator
  // coming soon
  /*
  $wdg=get_post($post_id);  
  $admin_mail=get_option( 'admin_email');
 if($admin_mail){   
  global $current_user;
  get_currentuserinfo();
  mail($admin_mail ,__('New widget created and shared','eelv_widgets'),sprintf(__('A new widget "%$1$s" has been created and shared : %$2$s (or being updated, I don\'t really know...)  ','eelv_widgets'),$wdg->post_title,$wdg->guid),"From: ".$current_user->display_name."<".$current_user->user_email.">");
  }*/
}


add_action( 'save_post', 'eelv_widgets_save_postdata' );