<?php

class MarketpressCategoryCopier {

    // Categories still to be copied for a certain site - This is required because we have an array of objects, we need to clone the original categories into this object
    var $categories_to_copy; 
    
    // Original List of categories to be copied for each site    
    var $origin_categories; 
    
    // Stores the activity log for all of the websites
    var $combined_activity_log = array();
    
    // Class Constructor
    public function __construct() {

	 // Add menu item to network admin page
	 add_action('network_admin_menu', array($this, 'add_network_menus'));
	 add_action( 'admin_init', array($this, 'register_settings' ));	
	 
	 // Add option to store activity logs, does nothing if it already exists
	 add_option('mcc_activity_log', '', '', 'no');
    }
    
/**
 * Adds the appropriate menu links on the network admin page
 *
 * <p>This function creates the menu links under the network administrator pages</p>
 */
    public function add_network_menus() {
        
	// Add page to menu
	$page_hook_suffix = add_submenu_page( 'settings.php', 'Marketpress Product Category Copier', 'Marketpress Category Copier', 'manage_network_options', 'marketpress_category_copier', array($this, 'display_options_page') );
	
	// Add activity log page without specifying a menu parent
	add_submenu_page( NULL, 'Marketpress Category Copier Activity Log', 'Marketpress Category Copier Activity Log', 'manage_network_options', 'marketpress_category_copier_log', array($this, 'display_activity_log') );
	
	add_action('admin_print_scripts-' . $page_hook_suffix, array($this, 'initialize_admin_scripts'));

    }
    
/**
 * Displays the options page on the network admin settings page
 *
 */    
    public function display_options_page(){
	require (MCC_PATH.'/inc/options.php'); 
    }
 
/**
 * Registers the settings that we want to show on our menu pages
 *
 */        
    public function register_settings(){	
	
	// Add action for ajax requests from option page
	add_action('wp_ajax_mcc_get_marketpress_categories', array($this, 'get_marketpress_product_categories'));	
	add_action('wp_ajax_mcc_get_marketpress_sites', array($this, 'get_marketpress_sites'));
    }
    
/**
 * Adds the necessary admin scripts for out plugin to function properly
 */
    public function initialize_admin_scripts(){
	
	// Initialize chosen script
	wp_enqueue_script( 'jquery-chosen', MCC_URL.'inc/chosen.jquery/chosen.jquery.min.js', array('jquery') );
	
	// Initialize chosen script
	wp_enqueue_script( 'mcc-js', MCC_URL.'inc/mcc.js', array('jquery-chosen') );
	
	// Include CSS for chosen script
	wp_enqueue_style('jquery-chosen-css', MCC_URL.'inc/chosen.jquery/chosen.min.css');
		
    }
    
    public function get_marketpress_product_categories(){
	
	// Switch to posted blog ID
	switch_to_blog(intval( $_POST['blog_id'] ));
	
	// Get categories for marketpress products
	//$categories = get_categories( array( 'taxonomy'=>'product_category', 'hide_empty' => 0));
	
	$categories = wp_dropdown_categories(array( 'taxonomy'=>'product_category', 'hide_empty' => 0,
						'hierarchical' => 1, 'echo' => 1,
						'name'=>'origin_categories[]',
						'id' => 'origin_categories',
						'class' => 'select_chosen',
						));
	
	restore_current_blog();
	
	die();

    }
    
    // Returns the list of sites which have marketpress active
    public function get_marketpress_sites(){
	
	// Switch to posted blog ID
	switch_to_blog(intval( $_POST['blog_id'] ));
	
	// Get site theme
	$theme = wp_get_theme();

	// Get theme name
	$theme_name = $theme->Name;
	
	// Get all sites which have this active theme
	$sites_to_send = array();
	
	// Get a list of all websites
	$all_sites = wp_get_sites();
	
	foreach($all_sites as $key=>$site){
	    
	    // Skip if our site is the current site, we don't want to include that
	    if($site['blog_id'] == intval ($_POST['blog_id'])){
		continue;
	    }
	    
	    // switch to that blog
	    switch_to_blog($site['blog_id']);
	    
	    // Only include sites where Marketpress Lite or Marketpress Pro is active
	    if ( is_plugin_active( 'wordpress-ecommerce/marketpress.php' ) || is_plugin_active('marketpress/marketpress.php') ) {
		$sites_to_send[] = array('blog_id' => $site['blog_id'], 'domain' => $site['domain']);
	    }
	    
	    restore_current_blog();		    
	}

	echo json_encode($sites_to_send);

	die();	
    }
    
    // Copies the categories from origin site to destination sites
    public function copy_categories(){
	
	// Display notices if any and stop processing if invalid
	if(!$this->display_admin_notice()){
	    return false;
	}
	
	// Switch to origin site posted via form
	$this->switch_to_posted_site();		
	
	// Get detailed category information received via POST
	$this->get_categories_information();
	
	restore_current_blog();	
	
	// Get destination sites
	$destination_sites = $_POST['destination_sites'];
	
	// Copy to each of the destination sites
	foreach($destination_sites as $key => $site_id){

	    // Switch to that site
	    switch_to_blog(intval ($site_id));
	    	   	    
	    // Create a copy of the old originial array because we want to update the parent IDs in it
	    // We need to copy each element individually because it's an array of objects, and objects get passed by reference
	    $this->categories_to_copy = array();
	    foreach($this->origin_categories as $key2=>$cat2){
		$this->categories_to_copy[$key2] = clone $cat2;
	    }
	    
	    // Initialize walker class
	    $walker = new MarketpressCategoryWalker();
	    
	    // Walk through tree
	    $walker->walk($this->categories_to_copy,0);
	    
	    // Add current site activity log to combined activity log
	    $this->combined_activity_log[$site_id] = $walker->activity_log;
	    
	    restore_current_blog();
	    
	} // All sites looped through
	
	// Store completed activity log in our database
	update_option('mcc_activity_log', $this->combined_activity_log);
	
    }
    
    /*
     * This function validates the user input and displays an error if invalid
     */
    private function validate_user_input(){
	
	if( empty($_POST['origin_site']) || empty($_POST['origin_categories'])
		|| empty($_POST['destination_sites']) ){
	    
	    add_action( 'admin_notices', array($this, 'display_admin_notice' ) ) ;
	    
	    return false;
	    
	}
	
	return true;
	
    }
    
    /*
     * Displays admin notice (e.g. Input errors, Changes successfully applied...)
     */
    private function display_admin_notice(){
	
	// Invalid user input
	if(!$this->validate_user_input()){
	        ?>
		    <div class="error">
			<p><?php _e( 'You need to fill all required fields before copying.', 'marketpress-category-copier' ); ?></p>
		    </div>
		<?php
		
	    return false; // invalid user input
	}
	
	// Valid user input
	else {

		?>
		    <div class="updated">
			<p><?php _e( 'Categories successfully copied.', 'marketpress-category-copier' ); ?></p>
		    </div>
		<?php
		
	    return true;
	}
    }
    
    // Takes an array of category IDs, and returns an array of associated objects
    private function get_categories_information($posted_categories = array()){
	
	// No category IDs have been passed, use POST data
	if(empty($posted_categories)){
	    // Get the ID of the categories we're copying
	    $posted_categories = $_POST['origin_categories'];
	}
	
	// The purpose of this loop is to add the parents of any children which have been selected without their parents
	do {
	    
	    // We assume that no orphans witout parent exist, if we find one, we'll switch the flag to true
	    $orphans_exist = false;

	    // Check if any orphans exist, and grab their parents
	    foreach($posted_categories as $key=>$cat_id){

		// Get category information
		$category_info = get_term($cat_id, 'product_category');

		// If parent is not in array, and element is not a top level item, add it
		if(!in_array($category_info->parent, $posted_categories) && $category_info->parent != 0){
		    $posted_categories[] = $category_info->parent;
		    $orphans_exist = true;
		}
	    }
	
	} while($orphans_exist);
	
	$this->origin_categories = array();
	
	// Get category information
	foreach($posted_categories as $key => $cat_id){
	    
	    $this->origin_categories[] = get_term($cat_id, 'product_category');
	}
	
	//return $origin_categories;
    }
    
    // Switches to the site ID sent via POST
    private function switch_to_posted_site(){
		
	// Get the ID of the site we're copying from
	$origin_site = intval ($_POST['origin_site']);
	
	// Switch to posted blog ID
	switch_to_blog(intval( $origin_site ));
    }
    
    // Displays the latest activity log
    public function display_activity_log(){
	require (MCC_PATH.'/inc/log.php'); 
    }
    
}

$networkcopier = new MarketpressCategoryCopier();
