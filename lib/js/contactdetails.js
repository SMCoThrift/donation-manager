jQuery(document).ready(function($){
  $( '.different-pickup-address' ).hide();
  $( '[name=donor\\[different_pickup_address\\]]' ).change( function(){
    var val = $( this ).val();
    if( 'Yes' == val ){
      $( '.different-pickup-address' ).slideDown();
      //  required="required" aria-required="true"
      $( '#donor-pickup-address-address' ).attr( 'required', 'required' ).attr( 'aria-required', 'true' );
      $( '#donor-pickup-address-city' ).attr( 'required', 'required' ).attr( 'aria-required', 'true' );
      $( '#donor-pickup-address-zip' ).attr( 'required', 'required' ).attr( 'aria-required', 'true' );
    } else {
      $( '.different-pickup-address' ).slideUp();
      $( '#donor-pickup-address-address' ).attr( 'required', null ).attr( 'aria-required', 'false' );
      $( '#donor-pickup-address-city' ).attr( 'required', null ).attr( 'aria-required', 'false' );
      $( '#donor-pickup-address-zip' ).attr( 'required', null ).attr( 'aria-required', 'false' );
    }
  });

  $('#donor_phone').mask('(000) 000-0000');
});
