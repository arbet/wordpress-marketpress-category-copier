/* 
 * Custom JS code for NMC
*/

jQuery(document).ready(function($) {
    
    // Get site menus on page load
    get_marketpress_categories();

    // Make beautiful select boxes
    jQuery(".select_chosen").chosen({width: "25%"});
    
    // Get sites by theme
    get_marketpress_sites();    
    
    // Get menu locations
    get_menu_locations();    
    
    // Add onclick event for skip existing checkbox
    jQuery("#skip_existing").click(enable_name_checkbox);
    
    // When the site field is changed, we need to get the list of menus available to that site
    jQuery("#origin_site").chosen().change(function(){
        
            // Get menus for specific theme
            get_marketpress_categories();
            
            // Get sites which have the active theme
            get_marketpress_sites();
            
            // Get menu locations
            get_menu_locations();
    });

function get_marketpress_categories(){
    
            
        // Create post data
        var data = {
                'action': 'mcc_get_marketpress_categories',
                'blog_id': jQuery( "#origin_site" ).val(),
        };            
        
       // console.log('Blog ID is ' + data.blog_id)
    // Get ajax value
    jQuery.post(ajaxurl, data, function(response) {
        
      console.log(response);
        //var menus = JSON.parse(response);
       // console.log(menus);
        // Empty existing values
        $('#origin_categories').replaceWith(response);

        // Loop through all of the select fields
        /*$.each(menus, function(key, value) { 
            // Replace select boxes
            $("#origin_categories").append($("<option></option>").attr("value",value.term_id).text(value.name)); 
        });*/
        
        // Change to multiple select box
       
    // Change to multiple select box
    $("#origin_categories").attr("multiple",  "multiple" );
    
    // Change to none selected
    $("#origin_categories option").prop("selected", false);

    // Switch to chosen box
    $("#origin_categories").chosen({width: "25%"});
    
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
        //console.log(sites);
        // Empty existing values
        $('#destination_sites').empty();

        // Loop through all of the select fields
        $.each(sites, function(key, value) { 

            // Replace select boxes
            $("#destination_sites").append($("<option></option>").attr("value",value.blog_id).text(value.domain)); 
        });
        
        // Rebuild chosen select boxes
        $("#destination_sites").trigger("chosen:updated");
    });   
    
}

// Displays the menu locations for the active site's theme
function get_menu_locations(){
    
        // Create post data
        var data = {
                'action': 'mcc_get_menu_locations',
                'blog_id': jQuery( "#origin_site" ).val(),
        };            
        
    // Get ajax value
    jQuery.post(ajaxurl, data, function(response) {
        
        
        console.log(response);

        // Empty existing values
        $('#menu_location').empty();
        
        // If response is null, return
        if(response ==  'null'){
            // Rebuild chosen select boxes
            $("#menu_location").trigger("chosen:updated");
            
            return;
        }

        var menus = JSON.parse(response);
        
        // Loop through all of the select fields
        $.each(menus, function(key, value) { 
            
            // Replace select boxes
            $("#menu_location").append($("<option></option>").attr("value",key).text(key)); 
        });
        
        // Rebuild chosen select boxes
        $("#menu_location").trigger("chosen:updated");
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
 
    
});
