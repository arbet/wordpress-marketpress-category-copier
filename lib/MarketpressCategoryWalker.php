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
    
    // An array of the node parents and grandparents
    public $node_parents = array();
    
    // The ID of the record just inserted, defaults to null
    public $inserted_id = 0;  
    
    // Stores the result of the whole run
    public $activity_log = array();
    
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
     *	    'inserted_id' - The ID of the last inserted node
     */
    public function start_el(&$output, $category, $depth = 0, $function_args = array(), $id = 0){
	
	// Set parent ID based on our depth
	if($depth != 0){

	    // Set parent ID to the last parent in our parents array for non-top level elements
	    $parent_id = end(array_values($this->node_parents));				    
	}

	// This is a top level element, parent ID is none
	else{
	    $parent_id = 0;
	}
	
	// Check if the category already exists
	$term = $this->get_duplicate($category);
	
	// Category already exists
	if($term!==FALSE){

	    // Skip category option is selected
	    if($_POST['category_exists'] == 'skip'){
		
		// Set inserted ID to skipped node
		$this->inserted_id = $term->term_id;
		
		// Add that node was skipped to activity log
		$this->activity_log[] = array('origin_node' => $category, 'destination_node' => $term, 'action' =>'skipped');
		return;
	    }
	    
	    // Update category option is selected
	    elseif($_POST['category_exists'] == 'update'){	
		
		// Check if there are any changes to be implemented, if not return (We don't want to log item as updated if no change has been implemented, this is why the check is here
		if( ($term->name == $category->name) && ($term->description == $category->description) && ($term->slug == $category->slug)){
		    return;
		}
		
		$args = array(
		    'name' => $category->name,
		    'description' => $category->description,
		    'slug'	  => $category->slug			);
		
		// update product category name and description
		wp_update_term($term->term_id, 'product_category', $args);
		
		// Set inserted ID to updated node
		$this->inserted_id = $term->term_id;
		
		// Add that node was updated to activity log
		$this->activity_log[] = array('origin_node' => $category, 'destination_node' => $term, 'action' =>'updated');
		
		return;
	    }

	    // Copy category option is selected
	    elseif($_POST['category_exists'] == 'duplicate'){
		
	    	    
		// Do not skip, copy
		$args = array('cat_name'=>$category->name.' (Copy)',
			      'category_description' => $category->description,
			    'slug' => $category->slug.'-copy-'.rand(1,1000), // Needed to ensure that it does not conflict with a previously copied category
			    'taxonomy' => 'product_category',
			    'category_parent' => $this->parent_id);

		// Set parent ID to copied node
		$this->parent_id = wp_insert_category($args); 

		// Add that node was copied to activity log
		    $this->activity_log[] = array('origin_node' => $category, 'destination_node' => $term, 'action' =>'copied');

		return;
	    }

	} // End of duplicate category processing

	// category does not exist
	else {

	    // Create category
	    $args = array('cat_name'=>$category->name,
			  'category_description' => $category->description,
			'slug' => $category->slug,
			'taxonomy' => 'product_category',
			'category_nicename' => $category->slug,
			'category_parent' => $parent_id);

	    // Set parent ID to created category
	    $insert_id = wp_insert_category($args, 1); 
	    
	    // Error has occurred during copy
	    if(is_wp_error($this->inserted_id)){
		$this->activity_log[] = array('origin_node' => $category, 'destination_node' => $term, 'action' =>'added', 'error' => $insert_id);	
		
	    }
	    
	    // No errors, set parent ID to newly created element
	    else{
		$this->inserted_id = $insert_id;
	    }
	    
	    return;

	}	
				
	
    }
    
    // When a level starts, add last inserted ID to parents array
    public function start_lvl( &$output, $depth = 0, $args = array() ) {		

	$this->node_parents[] = $this->inserted_id;
		
	return;
	
    }
    
    // When a level ends, remove the last parent ID from the array
    public function end_lvl( &$output, $depth = 0, $args = array() ) {

	// Get category data for the last inserted one
	$category_data = get_term($this->inserted_id, 'product_category');

	// if the parent ID is in the array, pop it
	if(in_array($category_data->parent, $this->node_parents)){
	    array_pop($this->node_parents);
	}
	
    }    
    
    
    private function get_duplicate($category){
		
	// Check how a category is being marked as duplicate
	$criteria = $_POST['duplicate_category'];		
	
	// Is slug selected as a duplicate criteria
	if(in_array('slug', $criteria)){
	    
	    // Get term by slug	    	
	    $term = get_term_by('slug', $category->slug, 'product_category');	    
	    
	    // Term does not exist, it isn't duplicate
	    if($term === FALSE)
		return false;
	    
	    // Slugs are different, there is no duplicates
	    if($category->slug != $term->slug)
		return false;
	}
	
	// Is name selected as a duplicate criteria
	if(in_array('name', $criteria)){
	    
	    // Get term by name	    	
	    $term = get_term_by('name', $category->name, 'product_category');	    
	    
	    // Term does not exist, it isn't duplicate
	    if($term === FALSE)
		return false;	    
	    
	    // Names are different, there is no duplicates
	    if($category->name != $term->name)
		return false;
	}	
	
	// Term is a duplicate, return duplicate by slug or name
	if(in_array('slug', $criteria)){
	    
	    return get_term_by('slug', $category->slug,'product_category');
	    
	}
	
	elseif(in_array('name', $criteria)){
	    return get_term_by('name', $category->name, 'product_category');	    
	}
	
    }
    
}
