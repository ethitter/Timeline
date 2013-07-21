( function( $ ) {
	$( document ).ready( function() {
		var datepicker_args = {
			defaultDate: $( '#eth-timeline-start' ).val(),
			dateFormat: 'MM dd, yy',
		};

		$( '#eth-timeline-start' ).datepicker( datepicker_args );

		$.extend( datepicker_args, { defaultDate: $( '#eth-timeline-end' ).val() } );
		$( '#eth-timeline-end' ).datepicker( datepicker_args );
	} );
} )( jQuery );