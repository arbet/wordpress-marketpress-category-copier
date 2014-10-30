<?php

/* 
 * Contains the options page for the plugin
 */

// Form has been submitted
if(!empty($_POST)){
    $category_copier = new MarketpressCategoryCopier();
    $category_copier->copy_categories();
}
?>

<div class="wrap">
<h2>Bulk Marketpress Product Category Copier</h2>
<p><a href='settings.php?page=marketpress_category_copier_log'>View Activity Log of Last Run</a></p>
<form method="post" action="settings.php?page=marketpress_category_copier"> 

<?php 

// Tell my options page which settings to handle
settings_fields( 'marketpress-category-copier' );

// replaces the form field markup in the form itself
do_settings_sections( 'marketpress-category-copier' );


// Get list of sites in the system
$sites = wp_get_sites();
?>
<table class="form-table">
        <tr valign="top">
        <th scope="row">Origin Site*</th>
        <td><select name='origin_site' id='origin_site' class='select_chosen'>
<?php    foreach($sites as $key=>$site){
	echo "<option value='".$site['blog_id']."'>".$site['domain']."</option>";
    }
?>
	    </select>
	    <p class="description">
		Site to copy Marketpress product categories from
	    </p>	    
	</td>
        </tr>
        <tr valign="top">
        <th scope="row">Categories to be copied*</th>
        <td id='origin_cat_cell'><select name='origin_categories[]' id='origin_categories' class='select_chosen' style="display:none" multiple>
	    </select>  <button id='all_categories' class='ui_button'>Add All</button> <button id='reset_categories' class='ui_button'>Clear</button>
	    <p class="description">Lists all product categories on the site you're copying from. If you select a child category, its ancestors will be automatically copied.</p>
	</td>
        </tr>	
        <tr valign="top">
        <th scope="row">Destination Sites*</th>
        <td><select name='destination_sites[]' id="destination_sites" multiple class='select_chosen'> 
	    </select> <button id='all_sites' class='ui_button'>Add All</button> <button id='reset_sites' class='ui_button'>Clear</button>
	    <p class="description">
		Sites you're copying Marketpress categories to. Only sites that have Marketpress currently active will be displayed here.
	    </p>	    
	</td>
        </tr>	

        <tr valign="top">
        <th scope="row">An existing category has:*</th>
        <td><select name='duplicate_category[]' id="duplicate_category" class='select_chosen' multiple> 
		<option value='slug' selected>Same slug</option>		
	    <option value='name'>Same name</option>				
	    </select>
	    <p class="description">
		Please select how to determine if a category already exists on a destination site or not. If more than one option is selected, it will check for all, i.e. duplicate slug AND duplicate name.
	    </p>	    	    	    
	</td>
        </tr>		
	
        <tr valign="top">
        <th scope="row">If category exists on destination site*</th>
        <td><select name='category_exists' id="category_exists" class='select_chosen'> 
	    <option value='skip' selected>Skip Category</option>		
	    <option value='duplicate'>Duplicate Category</option>		
	    <option value='update'>Update Category</option>		
	    </select>
	    <p class="description category_exists_description" id='skip_description'>
		Existing categories will be skipped. Categories on destination sites will be left intact.
	    </p>	    
	    <p class="description category_exists_description" id='duplicate_description' style='display:none'>
		Existing categories will be copied and <strong>(Copy)</strong> will be appended to the category name.
	    </p>	    	    
	    <p class="description category_exists_description" id='update_description' style='display:none'>
		Category , slug name and description will be updated to reflect the ones on the origin site. 
	    </p>	    	    
	</td>
        </tr>	
    <tbody id="skip_settings"  class ='category_exists_settings'>
        <tr valign="top">
        <th scope="row"></th>
        <td><input type="checkbox" name="skip_children" value=""/> Skip Children
	    <p class="description">
		If checked, children of skipped categories will be skipped as well.
	    </p>	    
	    <p class="description category_exists_description" id='duplicate_description' style='display:none'>
		Existing categories will be copied and <strong>(Copy)</strong> will be appended to the category name.
	    </p>	    	    
	    <p class="description category_exists_description" id='update_description' style='display:none'>
		Category name and category description will be updated to reflect the ones on the origin site. 
	    </p>	    	    
	</td>
        </tr>	
    </tbody>
    <tbody id="duplicate_settings" class ='category_exists_settings' style="display:none"><!-- Stores settings for Duplicate Category option--></tbody>
    <tbody id="update_settings" class ='category_exists_settings' style="display:none"><!-- Stores settings for Update Category option--></tbody>
	
</table> 
    
<?php
// Submit button
submit_button(); 

?>
</form>
</div>
