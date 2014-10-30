/* 
 * Custom JS code for NMC
*/

jQuery(document).ready(function() {    

    // Make beautiful select boxes
    jQuery(".select_chosen").chosen({width: "25%"});
    
    // Get site menus on page load
    get_marketpress_categories();
    
    // Get sites by theme
    get_marketpress_sites();  
    
    // Activate reset and add all buttons
    activate_buttons();
    
    // Add onclick event for skip existing checkbox
    jQuery("#skip_existing").click(enable_name_checkbox);
    
    // When the site field is changed, we need to get the list of menus available to that site
    jQuery("#origin_site").chosen().change(function(){
        
            // Get menus for specific theme
            get_marketpress_categories();
            
            // Get sites which have the active theme
            get_marketpress_sites();
    });
    
    // Update description when skip category action is changed
    jQuery("#category_exists").chosen().change(function(){
        
            // Get current value of select box
            value = jQuery( "#category_exists" ).val();
            
            //Hide all descriptions and option settings
            jQuery('.category_exists_description').hide();            
            jQuery('.category_exists_settings').hide();            
            
            // Show proper description
            jQuery('#' + value + '_description').show();
            jQuery('#' + value + '_settings').show();
            
    });

function get_marketpress_categories(){
    
            
        // Create post data
        var data = {
                'action': 'mcc_get_marketpress_categories',
                'blog_id': jQuery( "#origin_site" ).val(),
        };            
        

    // Get ajax value
    jQuery.post(ajaxurl, data, function(response) {        
    
    // Parse html to insert options
    response = jQuery(jQuery.parseHTML( response)).find('option');;    
        
    // Empty existing values
    jQuery('#origin_categories').empty();

    // Loop through all of the select fields
    jQuery.each(response, function(i, el) { 

        jQuery('#origin_categories').append(el);
    });

    // Deselect all options
    jQuery("#origin_categories:selected").removeAttr("selected");    
    jQuery("#origin_categories").prop("selectedIndex", -1); // Removes default select option
    
    // Rebuild chosen select boxes
    jQuery("#origin_categories").trigger("chosen:updated");
    
    return;
    
    });   
        
}

// Returns the sites which have marketpress installed
function get_marketpress_sites(){
    
        // Create post data
        var data = {
                'action': 'mcc_get_marketpress_sites',
                'blog_id': jQuery( "#origin_site" ).val(),
        };    
        
   // Get ajax value
    jQuery.post(ajaxurl, data, function(response) {

        var sites = JSON.parse(response);

        // Empty existing values
        jQuery('#destination_sites').empty();

        // Loop through all of the select fields
        jQuery.each(sites, function(key, value) { 

            // Replace select boxes
            jQuery("#destination_sites").append(jQuery("<option></option>").attr("value",value.blog_id).text(value.domain)); 
        });
        
        // Rebuild chosen select boxes
        jQuery("#destination_sites").trigger("chosen:updated");
    });   
    
}

// Enables or disables update name checkbox based on the value of Skip Existing
function enable_name_checkbox() {
  if (this.checked) {
    // disable and uncheck when skip existing is checked
    jQuery("#update_details").attr("disabled", true); 
    jQuery('#update_details').attr('checked', false);
  } else {
      
    // Enable when skip existing is unchecked
    jQuery("#update_details").removeAttr("disabled");
  }
}
 
function activate_buttons(){
    
    // Event for add all categories
    jQuery('#all_categories').click(function(){
        
        // Select all categories
        jQuery("#origin_categories option").prop('selected', true); 
        
        // Rebuild chosen select boxes
        jQuery("#origin_categories").trigger("chosen:updated");        
        
        return false;
    });
    
    // Event for add all categories
    jQuery('#reset_categories').click(function(){
        
        // Select all categories
        jQuery("#origin_categories option").prop('selected', false); 
        
        // Rebuild chosen select boxes
        jQuery("#origin_categories").trigger("chosen:updated");        
        
        return false;
    });    
    
    // Event for add all categories
    jQuery('#all_sites').click(function(){
        
        // Select all categories
        jQuery("#destination_sites option").prop('selected', true); 
        
        // Rebuild chosen select boxes
        jQuery("#destination_sites").trigger("chosen:updated");        
        
        return false;
    });
    
    // Event for add all categories
    jQuery('#reset_sites').click(function(){
        
        // Select all categories
        jQuery("#destination_sites option").prop('selected', false); 
        
        // Rebuild chosen select boxes
        jQuery("#destination_sites").trigger("chosen:updated");        
        
        return false;
    });      
}

});

