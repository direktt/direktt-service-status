jQuery( document ).ready( function ( $ ) { "use strict";
	const ids = JSON.parse( $( '#dss_all_ids' ).val() );

	$( '#publish' ).on( 'click', function () {
		var id = $( '#dss_direktt_subscription_id_input' ).val();
		if ( ! ids.includes( id ) ) {
			event.preventDefault();
			$( '.direktt-admin-popup' ).fadeIn();
		}
	});

	$( document ).on( 'focus', '#dss_direktt_subscription_id_input', function () {
		var $el = $( this );
		if ( ! $el.data( 'ui-autocomplete' ) ) {
			$el.autocomplete( { source: ids } );
		}
	});

	$( document ).on( 'focus', '#dss_direktt_subscription_id_input_alert', function () {
		var $el = $( this );
		if ( ! $el.data( 'ui-autocomplete' ) ) {
			$el.autocomplete( { source: ids } );
		}
	});

	$( document ).on( 'change', '#dss_direktt_subscription_id_input_alert', function () {
		var id = $( '#dss_direktt_subscription_id_input_alert' ).val();
		$( '#dss_direktt_subscription_id_input' ).val( id );
	});

	$( '#direktt-service-status-publish' ).on( 'click', function () {
		event.preventDefault();
		var id = $( '#dss_direktt_subscription_id_input' ).val();
		if ( ! ids.includes( id ) ) {
			$( '.direktt-admin-popup-error' ).show();
		} else {
			$( '.direktt-admin-popup-error' ).hide();
			$( '.direktt-admin-popup' ).fadeOut();
			setTimeout( function () {
				}, 500 );
			$( '#publish' ).trigger( 'click' );
		}
	});

	$( '#close-direktt-admin' ).on( 'click', function () {
		event.preventDefault();
		$( '.direktt-admin-popup' ).fadeOut();
	});
});