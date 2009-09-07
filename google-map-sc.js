/**
 * Google Map Shortcode 
 * Version: 1.1
 * Author: Alain Gonzalez
 * Author URI: http://web-argument.com/
*/
 
function gmshc_render(id,GMpointsArray,zoom) {
	
	var map = new GMap2(document.getElementById(id));
	
	
	var customUI = map.getDefaultUI();
	
	// Remove MapType.G_HYBRID_MAP
	customUI.maptypes.hybrid = false;
	
	map.setUI(customUI);	

	
	this.findPoint = findPoint;
	this.placing = placing;
	

	  
	for (var i = 0; i <= GMpointsArray.length - 1; i++){
 
	 	this.placing(GMpointsArray[i]);

	}

	 function placing (single, showinfo){

          var latlng = new GLatLng(single.point.lat,single.point.long);
		  map.setCenter(latlng, zoom);
		  var marker = new GMarker(latlng);
          map.addOverlay(marker);

			if(showinfo){
				
				map.openInfoWindowHtml(latlng, single.info);
				
			}
			if 	(single.info != null){
				GEvent.addListener(marker, "click", function() {
														 
					map.openInfoWindowHtml(latlng, single.info);
					
				});
			}
			
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