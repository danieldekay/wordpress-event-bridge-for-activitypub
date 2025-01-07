jQuery( function( $ ) {
	// Accordion handling in various areas.
	$( '.event-bridge-for-activitypub-settings-accordion' ).on( 'click', '.event-bridge-for-activitypub-settings-accordion-trigger', function() {
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
		if ($("#event_bridge_for_activitypub_summary_type_custom").is(':checked')) {
			$("#event_bridge_for_activitypub_summary_type_custom-details").show();
		} else {
			$("#event_bridge_for_activitypub_summary_type_custom-details").hide();
		}
	}

	// Run the toggle function on page load.
	$(document).ready(function() {
		toggleCustomDetailsForSummary(); // Set the correct state on load.

		// Listen for changes on the radio buttons
		$("input[name=event_bridge_for_activitypub_summary_type]").change(function() {
			toggleCustomDetailsForSummary(); // Update visibility on change.
		});
	});

} );
