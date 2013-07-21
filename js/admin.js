( function( $ ) {
	$( document ).ready( function() {
		var datepicker_args = {
			altField: 'input[name="eth-timeline[start]"]',
			altFormat: '@',
			dateFormat: 'M dd, yy',
		};

		$( '#eth-timeline-start' ).datepicker( datepicker_args );

		$.extend( datepicker_args, { altField: 'input[name="eth-timeline[end]"]', } );
		$( '#eth-timeline-end' ).datepicker( datepicker_args );
	} );
} )( jQuery );