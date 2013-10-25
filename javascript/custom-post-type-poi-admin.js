var cptPOI;

jQuery(document).ready(function(){

	cptPOI = {

		init:function(){

			cptPOI.getLatLngButton = jQuery('.get-lat-lng');
			cptPOI.street = jQuery('#poi-street');
			cptPOI.number = jQuery('#poi-street-number');
			cptPOI.zip = jQuery('#poi-zip');
			cptPOI.city = jQuery('#poi-city');
			cptPOI.country = jQuery('#poi-country');
			cptPOI.latitude = jQuery('#poi-lat');
			cptPOI.longitude = jQuery('#poi-lng');

			cptPOI.bindEvents();
		},

		bindEvents:function(){

			cptPOI.getLatLngButton.click(function(e){
				e.preventDefault();
				cptPOI.getLatLng();
			});

		},

		getLatLng:function(){

			var data = {
				action: 'get_lat_lng',
				street: cptPOI.street.val(),
				number: cptPOI.number.val(),
				zip: cptPOI.zip.val(),
				city: cptPOI.city.val(),
				country: cptPOI.country.val()
			};

			jQuery.post( ajaxurl, data, function(response){

				response = jQuery.parseJSON( response );

				if ( response.latitude ) {
					cptPOI.latitude.val( response.latitude );
				}

				if ( response.longitude ) {
					cptPOI.longitude.val( response.longitude );
				}
			});

		}

	};

	cptPOI.init();

});