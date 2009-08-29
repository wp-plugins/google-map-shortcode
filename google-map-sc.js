/**
 * Google map shortcode class v.1.0.0
 */
 
function gmshc_render(id,GMpointsArray,zoom) {

	var map = new GMap2(document.getElementById(id));
	
	var customUI = map.getDefaultUI();
	
	// Remove MapType.G_HYBRID_MAP
	customUI.maptypes.hybrid = false;
	
	map.setUI(customUI);
	
	var geocoder = new GClientGeocoder();
	
	this.findPoint = findPoint;
	this.placing = placing;
	for (var i = 0; i <= GMpointsArray.length - 1; i++){
  
	 	this.placing(GMpointsArray[i]);


	}

	 function placing (single, findpoint){
		 
  
			geocoder.getLocations(single.name, 
			      function (response) {
    
					  if (response && response.Status.code != 200) {
						alert("Unable to locate " + decodeURIComponent(response.name));
					  } else {
						var place = response.Placemark[0];
						
						var point = new GLatLng(place.Point.coordinates[1],
												place.Point.coordinates[0]);
						map.setCenter(point, zoom);
		
						if (GMpointsArray.length == 1){

						// Set up our GMarkerOptions object
						var baseIcon = new GIcon(baseIcon);
						baseIcon.image = "http://maps.google.com/mapfiles/arrow.png";
						baseIcon.shadow = "http://maps.google.com/mapfiles/arrowshadow.png";
						baseIcon.iconSize = new GSize(39, 34);
						baseIcon.shadowSize = new GSize(39, 34);
						baseIcon.iconAnchor = new GPoint(9, 34);
						baseIcon.infoWindowAnchor = new GPoint(9, 2);	  
						  
						markerOptions = { icon:baseIcon };
						var marker = new GMarker(point, markerOptions);						
                        map.addOverlay(marker);
						
						GEvent.addListener(marker, "click", function() {
						map.openInfoWindowHtml(point, "<div style='padding:0 10px; width:200px''>"+place.address+"</div>");
						});						
                        
						} else {
						
							var marker = new GMarker(point);		  
							
							var htmlBox = single.info;
							var NewhtmlBox = htmlBox.replace("%address%", place.address);
							
							map.addOverlay(marker);
							
							if(findpoint){
								
								map.openInfoWindowHtml(point, NewhtmlBox);
								
							}
							
							GEvent.addListener(marker, "click", function() {
								map.openInfoWindowHtml(point, NewhtmlBox);
							});
						
						}
					  }
				  });
			
	}			  
				  

	function findPoint(which) { 
	
		this.placing(GMpointsArray[which], true);
		
	}

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


function addEvent(elm, evType, fn, useCapture) {
	if (elm.addEventListener) {
		elm.addEventListener(evType, fn, useCapture);
		return true;
	}
	else if (elm.attachEvent) {
		var r = elm.attachEvent('on' + evType, fn);
		return r;
	}
	else {
		elm['on' + evType] = fn;
	}
}