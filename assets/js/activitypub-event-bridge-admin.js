jQuery( function( $ ) {
	// Accordion handling in various areas.
	$( '.activitypub-event-bridge-settings-accordion' ).on( 'click', '.activitypub-event-bridge-settings-accordion-trigger', function() {
		var isExpanded = ( 'true' === $( this ).attr( 'aria-expanded' ) );

		if ( isExpanded ) {
			$( this ).attr( 'aria-expanded', 'false' );
			$( '#' + $( this ).attr( 'aria-controls' ) ).attr( 'hidden', true );
		} else {
			$( this ).attr( 'aria-expanded', 'true' );
			$( '#' + $( this ).attr( 'aria-controls' ) ).attr( 'hidden', false );
		}
	} );

	// Function to toggle visibility of custom details based on selected radio button.
	function toggleCustomDetailsForSummary() {
		if ($("#activitypub_summary_type_custom").is(':checked')) {
			$("#activitypub_summary_type_custom-details").show();
		} else {
			$("#activitypub_summary_type_custom-details").hide();
		}
	}

	// Run the toggle function on page load.
	$(document).ready(function() {
		toggleCustomDetailsForSummary(); // Set the correct state on load.

		// Listen for changes on the radio buttons
		$("input[name=activitypub_summary_type]").change(function() {
			toggleCustomDetailsForSummary(); // Update visibility on change.
		});
	});
} );
