<?php
/*
  Plugin Name: My shared widgets
  Plugin URI: http://ecolosites.eelv.fr/widgets-personnalises/
  Description: create and share your text widgets in a multisites plateform
  Version: 1.7
  Author: bastho, EELV
  License: GPLv2
  Text Domain: eelv_widgets
  Domain Path: /languages/
  Network : 1
  Tags: widget, widgets, network, multisite, share, EELV
 */
$eelv_widget_options = array();

class SharedWidgets {

    public $admin_days;
    public $admin_cache;
    public $admin_cache_time;

    function __construct() {
        global $eelv_widget_options;
        $eelv_widget_options = get_option('eelv_widget_options');
        if (!is_array($eelv_widget_options))
            $eelv_widget_options = array();

        add_action('init', array(&$this, 'init'));
        add_action('add_meta_boxes', array(&$this, 'add_custom_box'));
        add_action('save_post', array(&$this, 'save_postdata'));

        add_filter('manage_eelv_widget_posts_columns', array(&$this, 'columns_head'), 2);
        add_action('manage_eelv_widget_posts_custom_column', array(&$this, 'columns_content'), 10, 2);

        add_action('wp_ajax_eelv_widget_uptime', array(&$this, 'uptime'));

        add_action('admin_print_scripts', array(&$this, 'admin_scripts'));
        add_action('network_admin_menu', array(&$this, 'network_menu'));
    }

    // PHP4 constructor
    public function SharedWidgets() {
        $this->__construct();
    }

    function init() {
        load_plugin_textdomain('eelv_widgets', false, 'eelv-my-widgets/languages');

        $this->admin_days = abs(get_site_option('eelv_widgets_admin_days'));
        $this->admin_cache = abs(get_site_option('eelv_widgets_admin_cache'));
        $this->admin_cache_time = abs(get_site_option('eelv_widgets_admin_cache_time'));


        global $wpdb;
        // Add the post_type for all blogs
        register_post_type('eelv_widget', array('label' => __('My shared widgets', 'eelv_widgets'),
            'description' => __('create and share your text widgets in a multisites plateform', 'eelv_widgets'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'themes.php',
            'capability_type' => 'post',
            'hierarchical' => false, 'rewrite' => array('slug' => ''),
            'query_var' => true,
            'has_archive' => false,
            'supports' => array('title', 'editor', 'author',),
            'labels' => array(
                'name' => __('My shared widgets', 'eelv_widgets'),
                'singular_name' => __('One of my shared widgets', 'eelv_widgets'),
                'menu_name' => __('My shared widgets', 'eelv_widgets'),
                'add_new' => __('Share a new widget', 'eelv_widgets'),
                'add_new_item' => __('Add a widget', 'eelv_widgets'),
                'edit' => __('Edit', 'eelv_widgets'),
                'edit_item' => __('Edit shared widget', 'eelv_widgets'),
                'new_item' => __('New widget', 'eelv_widgets'),
                'view' => __('View', 'eelv_widgets'),
                'view_item' => __('View widget', 'eelv_widgets'),
                'search_items' => __('Search Widgets', 'eelv_widgets'),
                'not_found' => __('No Widget Found', 'eelv_widgets'),
                'not_found_in_trash' => __('No Widget Found in Trash', 'eelv_widgets'),
                'parent' => __('Parent Widget', 'eelv_widgets')
            ))
        );


        if ($this->admin_cache == 0 || $this->admin_cache_time == 0 || $this->admin_cache_time < time()) {



            // select all blogs
            $offset = 0;
            $limit = 100;
            $rb = 'SHOW TABLE STATUS LIKE \'' . $wpdb->blogs . '\' ';
            $qb = $wpdb->get_row($rb);
            $count = $qb->Auto_increment;
            $widget_list = array();

            // Parse all widgets
            for ($s = 0; $s < $count; $s+=$limit) {
                $blogs_list = wp_get_sites(array('limit' => $limit, 'offset' => $offset, 'deleted' => false, 'archived' => false, 'spam' => false));

                // Construct the query on all blogs, splitted by 100 to prevent SQL to go away
                $date_limit = '';
                if ($this->admin_days > 0) {
                    $date_limit = ' AND `post_modified`>=\'' . date('Y-m-d H:i:s', strtotime('-' . $this->admin_days . 'days')) . '\'';
                }
                $req = '';
                foreach ($blogs_list as $blogue):
                    $blog = $blogue['blog_id'];
                    $chem = $wpdb->base_prefix . $blog . '_posts';
                    if ($blog == 1)
                        $chem = $wpdb->base_prefix . 'posts';
                    $req.='(SELECT `ID`,`post_title`,\'_' . $blog . '\' FROM `' . $chem . '` WHERE `post_status`=\'publish\' AND `post_type`=\'eelv_widget\' ' . $date_limit . ') UNION ';
                endforeach;
                $req = substr($req, 0, -7);
                $widgets = $wpdb->get_results($req);
                if (is_array($widgets)) {
                    foreach ($widgets as $wdg) {
                        $widget_list['_' . $blog . '_' . $wdg->ID] = $wdg;
                    }
                }
                $offset = $s;
            }



            // Save cache if needed
            if ($this->admin_cache > 0) {
                $this->admin_cache_time = strtotime('+' . $this->admin_cache . 'minutes');
                update_site_option('eelv_widgets_admin_cache_time', $this->admin_cache_time);
                update_site_option('eelv_widgets_cache_value', $widget_list);
            }
        } else {
            $widget_list = get_site_option('eelv_widgets_cache_value');
        }
        $users_cach = array();
        foreach ($widget_list as $widget):
            $widget = eelv_widget_get($widget);

            $widget->uid = str_replace(
                    array('http://', DOMAIN_CURRENT_SITE, '/', '.', '?', '&', 'p=', '=', 'post_type', 'eelv_widget', '-', '"', '\''), array('', '', '_', '_', '', '', '', '', '', '', '_', '', ''), html_entity_decode($widget->guid)
            );
            if (substr($widget->uid, 0, 1) == '_')
                $widget->uid = str_replace('.', '_', DOMAIN_CURRENT_SITE) . $widget->uid;
            $widget->uid = trim(str_replace('__', '_', $widget->uid));
            $sitename = substr($widget->uid, 0, strrpos($widget->uid, '_'));

            if (isset($users_cach[$widget->post_author])) {
                $author = $users_cach[$widget->post_author];
            } else {
                $a_req = 'SELECT `user_nicename` FROM wp_users WHERE ID = ' . $widget->post_author;
                $author = $wpdb->get_results($a_req);
                $author = $author[0]->user_nicename;
            }

            if (!function_exists('eelv_widget_callback_' . $widget->blog_id . '_' . $widget->ID)) {
                eval('function eelv_widget_callback_' . $widget->blog_id . '_' . $widget->ID . '($p){eelv_widget_callback($p);}');
            }
            if (!function_exists('eelv_widget_control_' . $widget->blog_id . '_' . $widget->ID)) {
                eval('function eelv_widget_control_' . $widget->blog_id . '_' . $widget->ID . '(){eelv_widget_control(' . $widget->blog_id . ',' . $widget->ID . ');}');
            }


            wp_register_sidebar_widget(
                    'eelv_wdg_' . $widget->blog_id . '_' . $widget->ID, '# ' . (!empty($widget->post_title) ? ucfirst($widget->post_title) : $sitename . ' ' . $widget->ID), 'eelv_widget_callback_' . $widget->blog_id . '_' . $widget->ID, array(
                "description" => $sitename . ' ' . __('by:', 'eelv_widgets') . ' ' . $author . ' - ' . date_i18n(get_option('date_format'), strtotime($widget->post_modified))
                    )
            );
            wp_register_widget_control('eelv_wdg_' . $widget->blog_id . '_' . $widget->ID, __('Widget options', 'eelv_widgets'), 'eelv_widget_control_' . $widget->blog_id . '_' . $widget->ID);



        endforeach;
    }

    function alive($post_id, $time = false) {
        if ($this->admin_days == 0) {
            return __("Your widget will be displayed for everyone since you move it into trash", 'eelv_widgets');
        } else {
            if ($time == false) {
                $time = get_post_modified_time('U', false, $post_id);
            }
            $visible_date = strtotime('+' . $this->admin_days . 'days', $time);
            if ($visible_date > time()) {
                $ret = '<span class="eelv_widgets_on">' . sprintf(__("Shared until %s", 'eelv_widgets'), date_i18n(get_option('date_format'), $visible_date)) . ' ';
            } else {
                $ret = '<span class="eelv_widgets_off">' . sprintf(__("Hidden since %s", 'eelv_widgets'), date_i18n(get_option('date_format'), $visible_date)) . ' ';
            }
            return $ret . '<button class="eelv_widget_uptime" data-id="' . $post_id . '"><span class="dashicons"></span> ' . __('Extend', 'eelv_widgets') . '</button></span>';
        }
    }

    /* Info panel in edit window */

    function add_custom_box() {
        add_meta_box(
                'eelv_widgets_side_info', __("Visibility", 'eelv_widgets'), array(&$this, 'side_info_function'), 'eelv_widget', 'side'
        );
    }

    function side_info_function() {
        echo $this->alive(get_the_id(), time());
    }

    /* Uptime by saving the post */

    function uptime() {
        if (is_user_logged_in() && current_user_can('publish_posts') && isset($_GET['widget_id']) && is_numeric($_GET['widget_id'])) {
            wp_update_post(array('ID' => $_GET['widget_id']));
            //force cache refreshing
            update_site_option('eelv_widgets_admin_cache_time', 0);
            echo $this->alive($_GET['widget_id']);
        } else {
            _e('Bad parameters', 'eelv_widgets');
        }
        exit;
    }

    /* When the post is saved, saves our custom data */

    function save_postdata($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['post_type']) || 'eelv_widget' != $_POST['post_type']) {
            return;
        }
        //force cache refreshing
        update_site_option('eelv_widgets_admin_cache_time', 0);

        //alert the administrator

        $wdg = get_post($post_id);
        $admin_mail = get_site_option('eelv_widgets_admin_surveillance');
        if ($admin_mail && !empty($admin_mail)) {
            global $current_user;
            get_currentuserinfo();

            $action = __('created', 'eelv_widgets');
            if ($_POST['original_post_status'] == 'publish') {
                $action = __('updated', 'eelv_widgets');
            }
            
            $headers = array('Content-Type: text/html; charset=UTF-8');                
                
            $eol = "\r\n";
            $body = '<body>'
                    . '<h1>' . sprintf(__('A new widget "%s" has been %s and shared', 'eelv_widgets'), '<a href="' . $wdg->guid . '" target="_blank">'.$wdg->post_title.'</a>', $action) . '</h1>' . $eol
                    .'<h3>' . $current_user->display_name . '</h3>'. $eol
                    .'<hr>' . apply_filters('the_content', $wdg->post_content).$eol
                    .'</body>';
            wp_mail($admin_mail, sprintf(__('A new widget has been %s and shared', 'eelv_widgets'), $action), $body, $headers);
        }
    }

    // ADD COLUMNS
    function columns_head($defaults) {
        $defaults['widgets'] = __('Share status', 'eelv_widgets');
        return $defaults;
    }

    // COLUMN CONTENT  
    function columns_content($column_name, $post_id) {
        if ($column_name == 'widgets') {
            echo $this->alive($post_id);
        }
    }

    function admin_scripts() {
        wp_enqueue_style('eelv_widgets', plugins_url('/my-widgets.css', __FILE__), false, null);
        wp_enqueue_script('eelv_widgets', plugins_url('/my-widgets.js', __FILE__), array('jquery'), false, true);
    }

    /*
     * Network stuff
     * 
     */

    // Ajout du menu d'option sur le reseau
    function network_menu() {
        add_submenu_page('settings.php', __('Their widgets', 'eelv_widgets'), __('Their widgets', 'eelv_widgets'), 'Super Admin', 'eelv_widgets_network_configuration', array(&$this, 'network_configuration'));
    }

    function network_configuration() {
        if ($_REQUEST['type'] == 'update') {
            update_site_option('eelv_widgets_admin_surveillance', $_REQUEST['eelv_widgets_admin_surveillance']);
            update_site_option('eelv_widgets_admin_cache', $_REQUEST['eelv_widgets_admin_cache']);
            update_site_option('eelv_widgets_admin_days', $_REQUEST['eelv_widgets_admin_days']);
            ?>
            <div class="updated"><p><strong><?php _e('Options saved', 'eelv_widget'); ?></strong></p></div>
            <?php
        }
        $eelv_widgets_admin_surveillance = get_site_option('eelv_widgets_admin_surveillance');
        $this->admin_cache = get_site_option('eelv_widgets_admin_cache');
        $this->admin_days = get_site_option('eelv_widgets_admin_days');
        ?>  
        <div class="wrap">
            <div id="icon-edit" class="icon32 icon32-posts-newsletter"><br/></div>
            <h2><?= _e('Their widgets', 'eelv_widget') ?></h2>

            <form name="typeSite" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">  
                <input type="hidden" name="type" value="update">


                <table class="widefat" style="margin-top: 1em;">
                    <thead>
                        <tr>
                            <th scope="col" colspan="2"><?= __('Configuration ', 'menu-config') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td width="30%">
                                <label for="newsletter_default_exp"><?= _e('Send an alert for each creation :', 'eelv_widgets') ?></label>
                            </td><td>
                                <input  type="text" name="eelv_widgets_admin_surveillance"  size="60"  id="eelv_widgets_admin_surveillance"  value="<?= $eelv_widgets_admin_surveillance ?>" class="wide">
                            </td>
                        </tr>

                        <tr>
                            <td width="30%">
                                <label for="eelv_widgets_admin_cache"><?= _e('Keep widgets in cache :', 'eelv_widgets') ?></label>
                            </td><td>
                                <input  type="number" name="eelv_widgets_admin_cache"  size="3"  id="eelv_widgets_admin_cache"  value="<?= abs($this->admin_cache) ?>" class="wide"><?= _e('minute(s)', 'eelv_widgets') ?>
                                <em><?= _e('value 0 is no-cache', 'eelv_widgets') ?></em>
                            </td>
                        </tr>                 

                        <tr>
                            <td width="30%">
                                <label for="eelv_widgets_admin_days"><?= _e('Hide widgets older than :', 'eelv_widgets') ?></label>
                            </td><td>
                                <input  type="number" name="eelv_widgets_admin_days"  size="3"  id="eelv_widgets_admin_days"  value="<?= abs($this->admin_days) ?>" class="wide"><?= _e('day(s)', 'eelv_widgets') ?>
                                <em><?= _e('value 0 disables option', 'eelv_widgets') ?></em>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <p class="submit">
                                    <input type="submit" name="Submit" value="<?php _e('save', 'eelv_widgets') ?>" />
                                </p>                    
                            </td>
                        </tr>
                    </tbody>
                </table>

            </form>
        </div>

        <?php
    }

}

$eelv_SharedWidgets = new SharedWidgets();


/**
 * Following functions are called by self-created widgets functions
 * 
 */

//Get all usefull attributes of a shared widget
function eelv_widget_get($widget) {
    $widget = (array) $widget;
    $vals = array_values($widget);
    $blog_id = substr($vals[2], 1);
    global $wpdb;
    $chem = $wpdb->base_prefix . $blog_id . '_posts';
    if ($blog_id == 1)
        $chem = $wpdb->base_prefix . 'posts';
    $req = 'SELECT `ID`,`post_title`,`post_author`,`post_modified`,`guid` FROM ' . $chem . ' WHERE ID = ' . $vals[0] . ' LIMIT 1';
    $widget = $wpdb->get_results($req);
    $widget[0]->blog_id = $blog_id;
    return $widget[0];
}

/**
 * @desc Global callback to display a shared widget
 * @param $p : eelv_wdg_$blogid_$postid
 */

function eelv_widget_callback($p) {
    global $eelv_widget_options;
    $p_w = explode('_', $p['widget_id']);
    $widget = get_blog_post($p_w[2], $p_w[3]);
    $widget_id = $p_w[2] . '_' . $p_w[3];
    if (!isset($eelv_widget_options[$widget_id]))
        $eelv_widget_options[$widget_id] = array('show_title' => 1);
    switch_to_blog($p_w[2]);
    echo $p['before_widget'];
    if ($eelv_widget_options[$widget_id]['show_title'] == '1' && !empty($widget->post_title)) {
        echo $p['before_title'];
        echo apply_filters('the_title', $widget->post_title);
        echo $p['after_title'];
    }
    echo'<div class="wigeelv">';
    echo apply_filters('the_content', $widget->post_content);
    echo'</div>';
    echo $p['after_widget'];
    restore_current_blog();
}

/**
 * @desc Global control callback to manage a shared widget
 * @param $b : $blogid
 * @param $p : $postid
 */

function eelv_widget_control($b, $p) {
    global $eelv_widget_options;
    $widget_id = $b . '_' . $p;
    if (isset($_POST['eelv_widget_options'])) {
        $eelv_widget_options[$widget_id] = $_POST['eelv_widget_options'];
        update_option('eelv_widget_options', $eelv_widget_options);
        _e('Options saved', 'eelv_widgets');
    }
    if (!isset($eelv_widget_options[$widget_id]))
        $eelv_widget_options[$widget_id] = array('show_title' => 1);
    ?>
    <p><label><?php _e('Title', 'eelv_widgets') ?>
            <select name='eelv_widget_options[show_title]'>
                <option value="1" <?= ($eelv_widget_options[$widget_id]['show_title'] == '1' ? 'selected' : '') ?>><?php _e('Show', 'eelv_widgets') ?></option>
                <option value="0" <?= ($eelv_widget_options[$widget_id]['show_title'] == '0' ? 'selected' : '') ?>><?php _e('Hide', 'eelv_widgets') ?></option>
            </select> 
        </label>
    </p>

    <?php
}
