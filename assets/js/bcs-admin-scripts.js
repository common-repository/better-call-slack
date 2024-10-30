(function($) {
	
	$(document).ready(function(){		
			
			$('.bcs-form-checkbox').each( function() {
				var that = $(this);
				if ( that.is(':checked') ) {
					that.next().show();
					that.parents('tr').next().show();
				}
			});
				
			$('.bcs-form-checkbox').on( 'change', function() {
				
				var that = $(this);
				if ( that.is(':checked') ) {
					that.next().fadeIn('fast');
					that.parents('tr').next().fadeIn('fast');
				}
				else {
					that.next().hide();
					that.parents('tr').next().fadeOut('fast');
				}	

			});	
		
	});
	
})( jQuery );	