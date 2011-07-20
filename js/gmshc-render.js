/**
 * Google Map Shortcode 
 * Version: 2.1
 * Author: Alain Gonzalez
 * Author URI: http://web-argument.com/
*/

function gmshc_render(id,GMpointsArray,zoom) {
	
  var myOptions = {
    zoom: zoom,
    center: new google.maps.LatLng(GMpointsArray[0].lat,GMpointsArray[0].long),
    mapTypeId: google.maps.MapTypeId.ROADMAP
  }
  var map = new google.maps.Map(document.getElementById(id),myOptions);

  gmshc_placing(map,GMpointsArray);

}

function gmshc_placing (map, locations){
	 
	//var infowindow;

	for (var i = 0; i < locations.length; i++){		
   
		var location = locations[i];
	 
		   var marker = new google.maps.Marker({
												position: new google.maps.LatLng(location.lat, location.long),
												map: map,
												icon: new google.maps.MarkerImage(location.icon),
												title:location.address
			});
		

		gmshc_addListener(map, marker, location.info);																		   
       
  	}
}


function gmshc_addListener(map, marker, info){
	
		var infowindow = new google.maps.InfoWindow({
													maxWidth:340,
													content: info
													});		
	
		google.maps.event.addListener(marker, 'click', function() { infowindow.open(map,marker);  });	
	
}

function addLoadEvent(func) {
	var oldonload = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = func;
		}
		else {
		window.onload = function() {
		oldonload();
		func();
		}
	}
}
