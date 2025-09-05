jQuery( document ).ready( function( $ ) { "use strict";
    $( '#publish' ).on( 'click', function( e ) {
        var id = $( '#dss_direktt_subscription_id_input' ).val();
        console.log( id );
        var ids = $( '#dss_all_ids' ).val();
        ids = JSON.parse(ids);
        console.log( ids );
        if ( ids.includes( id ) ) {
            console.log( "ID is in the array." );
        } else {
            e.preventDefault();
            console.log( "ID is NOT in the array." );
        }
    });
});