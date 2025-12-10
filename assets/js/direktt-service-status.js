jQuery( document ).ready(
	function ( $ ) {
		"use strict";
		var ids = $( '#dss_all_ids' ).val();
		ids     = JSON.parse( ids );
		$( '#publish' ).on(
			'click',
			function ( e ) {
				var id = $( '#dss_direktt_subscription_id_input' ).val();
				if ( ! ids.includes( id ) ) {
					e.preventDefault();
					$( '.direktt-admin-popup' ).fadeIn();
				}
			}
		);

		$( document ).on(
			'focus',
			'#dss_direktt_subscription_id_input',
			function () {
				var $el = $( this );
				if ( ! $el.data( 'ui-autocomplete' ) ) {
						$el.autocomplete( { source: ids } );
				}
			}
		);

		$( '#close-dsc-form-error' ).on(
			'click',
			function () {
				event.preventDefault();
				$( '.direktt-admin-popup' ).fadeOut();
			}
		);
	}
);