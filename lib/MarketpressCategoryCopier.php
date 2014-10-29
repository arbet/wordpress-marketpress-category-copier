<?php

class MarketpressCategoryCopier {

    var $categories_to_copy; // Categories still to be copied for a certain site
    var $categories_copied; // Categories that are being copied
    var $origin_categories; // List of categories to be copied for each site
    public function __construct() {

	 // Add menu item to network admin page
	 add_action('network_admin_menu', array($this, 'add_network_menus'));
	 add_action( 'admin_init', array($this, 'register_settings' ));	 
    }
    
/**
 * Adds the appropriate menu links on the network admin page
 *
 * <p>This function creates the menu links under the network administrator pages</p>
 */
    public function add_network_menus() {
         
	$page_hook_suffix = add_submenu_page( 'settings.php', 'Marketpress Product Category Copier', 'Marketpress Category Copier', 'manage_network_options', 'marketpress_category_copier', array($this, 'display_options_page') );
	
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
	add_action('wp_ajax_mcc_get_menu_locations', array($this, 'get_menu_locations'));
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
    
    public function get_menu_locations(){	

	// Switch to posted blog ID
	switch_to_blog(intval( $_POST['blog_id'] ));


	// Get theme mods option to get menu locations - 
	// get_registered_nav_menus not working with switch_to_blog since it uses global variables
	// More information: http://scotty-t.com/2012/03/13/switch_to_blog-is-an-unfunny-nightmare/

	// Get registered menu locations
	$theme_options = get_option('theme_mods_'. get_stylesheet());

	$theme_menu_locations = $theme_options['nav_menu_locations'];
	
	echo json_encode($theme_menu_locations);
	
	restore_current_blog();	
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
	    
	    // Initialize copied categories array
	    $this->categories_copied = array();
	    
	    // Copy top level categories
	    $this->copy_children_categories(0);

	    // Copy categories with no parent, and add them to an array
	    while(!empty($this->categories_to_copy)){		

		// Keep passing through the array until all of the items have been copied
		foreach($this->categories_to_copy as $key=>$cat_to_copy){
		    
		    $this->copy_children_categories($cat_to_copy->parent);
		}
		
		//// First level children should be already copied
	    } // End of while loop
	    
	    restore_current_blog();
	    
	} // All sites looped through
	
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
    
    private function copy_category($category, $new_parent){

	// Check if category already exists in the system
	$term = get_term_by('slug', $category->slug, 'product_category');

	// Category exists
	if($term !== FALSE){

	    // if skip check box is checked, skip it
	    if(isset($_POST['skip_existing'])){
		return $category->term_id; // Return the ID of the category on the origin site, so that it is added to categories copied (or processed)
	    }
	    
	    // Check if update category checkbox is checked, and update it accordingly
	    if(isset($_POST['update_details'])){	
		
		$args = array(
		    'name' => $category->name,
		    'description' => $category->description);
		
		// update product category name and description
		wp_update_term($term->term_id, 'product_category', $args);
		
		return $category->term_id; // Return the ID of the category on the origin site, so that it is added to categories copies
	    }

	    // Do not skip, copy
	    $args = array('cat_name'=>$category->name.' (Copy)',
			  'category_description' => $category->description,
			'slug' => $category->slug.'-copy-'.rand(1,1000), // Needed to ensure that it does not conflict with a previously copied category
			'taxonomy' => 'product_category',
			'category_parent' => $new_parent);

	    return wp_insert_category($args); // Pass the created category ID

	}

	// category does not exist
	else {

	    // Do not skip, copy
	    $args = array('cat_name'=>$category->name,
			  'category_description' => $category->description,
			'slug' => $category->slug,
			'taxonomy' => 'product_category',
			'category_parent' => $new_parent);

	    return wp_insert_category($args); // Pass the created category ID		    

	}
    }
    
    // Copies the children categories of a parent
    // Takes an array of categories to be copied and categories copied (both by reference)
    // and a parent ID, 0 means top level
    private function copy_children_categories($parent){
	
	// Copy each of the categories individually
	foreach($this->categories_to_copy as $key=>$category){
	    
	    // Check if the parent has been already copied, if not skip for non-top level categories
	    //echo 'category ID is '.$category->term_id."<br/>";
	    //echo 'Parent ID is '.$category->parent."<br/>";
	    //echo "Passed parent parameter is ".$parent."<br/>";
	    //echo "<br/>";
	    if( ($category->parent != 0) && (!in_array($parent, $this->categories_copied) ) ){
		//echo "Category skipped because parent category has not been copied<br/>";
		continue;
	    }
	    
	    // Copy only nodes that are direct children of our parent
	    if ( $category->parent != $parent){
		//echo "Category skipped because it has a different parent<br/>";
		continue;
	    }

	    // Copy category
	    $new_cat_id = $this->copy_category($category, $parent);
	    
	    // Unset in categories to copy
	    unset($this->categories_to_copy[$key]);
	    
	    // Add to copied categories
	    $this->categories_copied[] = $new_cat_id;
	    
	    // Update the parent IDs in array to be copied to reflect the newly inserted ones
	    foreach($this->categories_to_copy as $key => $cat){
		
		// The category parent ID is the same as the category of the ID just updated
		if($cat->parent == $category->term_id){
		    
		    // Change the parent ID of the category to be copied
		    $this->categories_to_copy[$key]->parent = $new_cat_id;
		}
	    }
	    
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
}

$networkcopier = new MarketpressCategoryCopier();
