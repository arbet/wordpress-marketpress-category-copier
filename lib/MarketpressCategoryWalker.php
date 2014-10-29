<?php

/* 
 * Custom Implementation of the walker class in order to easily copy product categories and their parents
 * 
 */

class MarketpressCategoryWalker extends Walker {

    // Define our tree type
    public $tree_type = 'product_category';
    
    /*
     * Define the database fields to use
     */
    public $db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );
    
    // The ID of the parent just copied - defaults as top level
    public $parent_id = 0;    
    
    /*
     * Class constructor
     * @param int  $menu_id ID of the parent menu of the items tree
     */
    public function __construct() {
	
    }
    
    /*
     * This function runs at the start of each element, it will copy itself and associate the direct parent ID with it
     * @param string $output Passed by reference. Used to append additional content.
     * @param int    $item  Name of the item
     * @param array  $args   An array of arguments.
     *	    'parent_id' - The parent ID of the current node
     */
    public function start_el(&$output, $category, $depth = 0, $function_args = array()){
	
	// If this is a top level element, unset the parent set to the previous node
	if($depth == 0){
	    $this->parent_id = 0;
	}

	// Check if category already exists in the system
	$term = get_term_by('slug', $category->slug, 'product_category');

	// Category exists
	if($term !== FALSE){

	    // if skip check box is checked, skip it
	    if(isset($_POST['skip_existing'])){
		
		// Set parent ID to skipped node
		$this->parent_id = $term->term_id;
		return;
	    }
	    
	    // Check if update category checkbox is checked, and update it accordingly
	    if(isset($_POST['update_details'])){	
		
		$args = array(
		    'name' => $category->name,
		    'description' => $category->description);
		
		// update product category name and description
		wp_update_term($term->term_id, 'product_category', $args);
		
		
		// Set parent ID to updated node
		$this->parent_id = $term->term_id;
		return; // Return the ID of the category on the origin site, so that it is added to categories copies
	    }

	    // Do not skip, copy
	    $args = array('cat_name'=>$category->name.' (Copy)',
			  'category_description' => $category->description,
			'slug' => $category->slug.'-copy-'.rand(1,1000), // Needed to ensure that it does not conflict with a previously copied category
			'taxonomy' => 'product_category',
			'category_parent' => $this->parent_id);

	    // Set parent ID to copied node
	    $this->parent_id = wp_insert_category($args); 
	    return;

	}

	// category does not exist
	else {

	    // Create category
	    $args = array('cat_name'=>$category->name,
			  'category_description' => $category->description,
			'slug' => $category->slug,
			'taxonomy' => 'product_category',
			'category_parent' => $this->parent_id);

	    // Set parent ID to created category
	    $this->parent_id = wp_insert_category($args); 
	    return;

	}	
		
		
		
		
	
    }
    
    // Function is here to override output of parent class
    public function  end_el( &$output, $item, $depth = 0, $args = array() ) {
    
	
    }
    // Function is here to override output of parent class
    public function start_lvl( &$output, $depth = 0, $args = array() ) {
	
    }
    // Function is here to override output of parent class
    public function end_lvl( &$output, $depth = 0, $args = array() ) {
	 
     }
}
