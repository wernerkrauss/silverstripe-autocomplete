/**
 * Register Autocomplete functions with fields.
 * This is not strictly using jQuery, like the rest of the CMS.
 */

(function($) {
	$(function() {

		// Load autocomplete functionality when field gets focused
		$('.field.autocomplete input.text').live('focus', function() {
			
			var input = $(this);
            
			// Prevent this field from loading itself multiple times
			if(input.attr('data-loaded') == 'true')
				return;
			input.attr('data-loaded', 'true');
                        
                        var source = input.attr('data-source');
                        var filterField = '';
                        var filterFieldAs = '';
                        
                        
                        if (input.attr('data-filter-field')) {
                            filterField = input.attr('data-filter-field');
                            filterFieldAs = input.attr('data-filter-field-as')
                                ? input.attr('data-filter-field-as')
                                : filterField;
                            
                        var value = $('[name=' + filterField + ']').filter(":input").val();
                            source = source + '?filter[' + filterFieldAs + ']=' + value;
                        }
            
			// load autocomplete into this field
			input.autocomplete({
				source: source,
				minLength: input.attr('data-min-length'),
				change: function( event, ui ) {
					
					// Check if a selection from the list is required
					if(!input.attr('data-require-selection')) return true;
					
					// Accept if item selected from list
					if(ui.item) return true;

					// remove invalid value, as it didn't match anything
					input.val("");
					input.data("autocomplete").term = "";
					return false;
				}
			});
		});
	});
})(jQuery);