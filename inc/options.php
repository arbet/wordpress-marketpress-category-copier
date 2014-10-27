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
        <th scope="row">Site to copy Marketpress product categories from*</th>
        <td><select name='origin_site' id='origin_site' class='select_chosen'>
<?php    foreach($sites as $key=>$site){
	echo "<option value='".$site['blog_id']."'>".$site['domain']."</option>";
    }
?>
	    </select>
	</td>
        </tr>
        <tr valign="top">
        <th scope="row">Marketpress product categories to be copied*</th>
        <td><select name='origin_categories[]' id='origin_categories' style="display:none" multiple>
	    </select>
	    <p class="description">Lists all product categories on the site you're copying from. If you select a child category, its ancestors will be automatically copied.</p>
	</td>
        </tr>	
        <tr valign="top">
        <th scope="row">Sites to copy Marketpress product categories to*</th>
        <td><select name='destination_sites[]' id="destination_sites" multiple class='select_chosen'> 
	    </select>
	    <p class="description">
		Only sites that have Marketpress currently active will be displayed here. If you have network activated this plugin it should list all of your websites here.
	    </p>	    
	</td>
        </tr>		
        <th scope="row">Skip Existing Categories?</th>
        <td><input type='checkbox' name='skip_existing' value='' checked/>
	    <p class="description">
		If this is checked, any categories which have the same slug will be skipped. If unchecked, they will be added with the name "Category X (Copy) " , where Category X is the name of the original category.
	    </p>
	</td>
        </tr>	
        </tr>		       
        </tr>	
</table> 
    
<?php
// Submit button
submit_button(); 

?>
</form>
</div>
