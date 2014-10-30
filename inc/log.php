<div class="wrap">
<h2>Bulk Marketpress Product Category Copier Activity Log</h2>

<?php

// Get activity log object
$activity_log = get_option('mcc_activity_log');

// Set log to empty
$is_log_empty = true;

// Loop through each site's activity log
foreach($activity_log as $site_id => $site_log){
    
    // The log for this site is empty, skip it
    if(empty($site_log)){
	continue;
    }
    
    // Log is not empty, switch flag
    $is_log_empty = false;
    
    // Get current site details
    $current_site = get_blog_details($site_id);
    
    // Output site name
    echo "<h3>".$current_site->blogname."</h3>";
    
    // Loop through individual logs
    foreach($site_log as $key=>$entry){
	
	
	$destination_node = $entry['destination_node'];
	
	echo "<a href='".$current_site->siteurl."/wp-admin/edit-tags.php?action=edit&taxonomy=product_category&tag_ID=".$destination_node->term_id."&post_type=product'>".$destination_node->name."</a> has been ".$entry['action'];
	
	// Echo error if it exists
	if(isset($entry['error'])){
	    echo "<span style='color: #FF0000'> Error: ".$entry['error']->get_error_message()."</span>";
	}
	echo "<br/>";
    }
}

if($is_log_empty){
    echo "<p>Log is empty. All categories have been copied without any categories being skipped, updated or duplicated.</p>";
}

?>
</div>
