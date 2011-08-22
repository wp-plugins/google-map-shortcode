<?php 
/**
 * Google Map Shortcode 
 * Version: 2.2.1
 * Author: Alain Gonzalez
 * Author URI: http://web-argument.com/
*/

/**
  * Generating Map 
  *
  */  
function gmshc_generate_map($map_points, $atts) {
	
	  extract($atts);				
	  if ($canvas == "") $canvas = "canvas_".wp_generate_password(4, false);

	  $output ='<div id="'.$canvas.'" class = "gmsc" style="width:'.$width.'px; height:'.$height.'px; ';
	  switch ($align) {
		  case "left" :		  
	  	  $output .= 'float:left; margin:'.$margin.'px;"';
		  break;
		  case "right" :		  
	  	  $output .= 'float:right; margin:'.$margin.'px;"';
		  break;
		  case "center" :		  
	  	  $output .= 'clear:both; overflow:hidden; margin:'.$margin.'px auto;"';
		  break;	  
	  }

	  $output .= "></div>";
	  $output .= "<script type=\"text/javascript\">\n";
	  $output .= "var map_points_".$canvas." =  new Array();\n";
	  
	  $i = 0;
	  			  
	  foreach ($map_points as $point){	
	  
	  	  $post_categories = wp_get_post_categories( $point->post_id );  
		  $terms = implode(",",$post_categories);
		  
		  list($lat,$lng) = explode(",",$point->ltlg);		  
		  $output .= "map_points_".$canvas."[".$i."] = \n";
		  $output .= "{\"address\":\"".$point->address."\",\n";
		  $output .= "\"lat\":\"".$lat."\",\n";
		  $output .= "\"lng\":\"".$lng."\",\n";
		  $output .= "\"info\":\"".gmshc_get_windowhtml($point)."\",\n";
		  $output .= "\"cat\":\"".$terms."\",\n";
		  $output .= "\"icon\":\"".$point->icon."\"};\n";
		  $i ++;
		  
	  }	  
	  
	  $output .= "var options_".$canvas." = {\n";
	  $output .= "'zoom':".$zoom.",\n";
	  $output .= "'markers':map_points_".$canvas.",\n";
	  $output .= "'mapContainer':'".$canvas."',\n";
	  $output .= "'focusType':'".$focus_type."',\n";			  
	  $output .= "'type':'".$type."',\n";
	  
	  switch ($focus) {
		case "all" :  
		$output .= "'circle':true,\n";
		break;
		case "0" : 
		break;
		default:
		$output .= "'focusPoint':".($focus-1).",\n";
	  }  
	    
	  $output .= "'animateMarkers':".$animate.",\n";
	  $output .= "'interval':'".$interval."'\n";
	  $output .= "};\n"; 
		  
	  $output .= "var map_".$canvas." = new gmshc.Map(options_".$canvas.");\n";
	  $output .= "var trigger_".$canvas." = function(){map_".$canvas.".init()};\n";
	  $output .= "gmshc.addLoadEvent(trigger_".$canvas.");\n";  
	  $output .= "</script>\n";	
	
	  $output = apply_filters('gmshc_generate_map',$output,$map_points,$atts);
	    
	  return $output;
}


/**
 * Get the html info
 *  
 */
 
function gmshc_get_windowhtml(&$point) {
    
	$windowhtml = "";
	$output = apply_filters('gmshc_get_windowhtml',$windowhtml,$point);

	if ( $output != '' )
		return $output;	

	$options = get_gmshc_options();	
	$windowhtml_frame = $options['windowhtml'];	

	$open_map_url = "http://maps.google.com/?q=".urlencode($point->address);
	$point_title = $point->title;
	if (($point->post_id) > 0)	$point_link = get_permalink($point->post_id);
	else $point_link = "";
	$point_img_url = ($point->thumbnail != "")? $point->thumbnail : gmshc_post_img($point->post_id);
	$point_excerpt = gmshc_get_excerpt($point->post_id);
	$point_description = ($point->description != "") ? $point->description : $point_excerpt;
	$point_address = $point->address;

	if(isset($point_img_url)) {
		$point_img = "<img src='".$point_img_url."' style='margin:8px 0 0 8px; width:90px; height:90px'/>";
		$html_width = "310px";
	} else {
		$point_img = "";
		$html_width = "auto";
	}				
				
	$find = array("%title%","%link%","%thubnail%", "%excerpt%","%description%","%address%","%open_map%","%width%","\f","\v","\t","\r","\n","\\","\"");
	$replace  = array($point_title,$point_link,$point_img,$point_excerpt,$point_description,$point_address,$open_map_url,$html_width,"","","","","","","'");
	
	$windowhtml = str_replace( $find,$replace, $windowhtml_frame);
				
	return $windowhtml;
	
}

function gmshc_stripslashes_deep($value)
{
    $value = is_array($value) ?
                array_map('stripslashes_deep', $value) :
                stripslashes($value);

    return $value;
}

/**
 * Get all the thumbnails from post
 */
function gmshc_all_post_thumb($the_parent){

	$images_url = array();
	$attachments = get_children( array(
										'post_parent' => $the_parent, 
										'post_type' => 'attachment', 
										'post_mime_type' => 'image',
										'orderby' => 'menu_order', 
										'order' => 'ASC',
										'numberposts' => 10) );
											
	if($attachments == true) :
		foreach($attachments as $id => $attachment) :
			$img = wp_get_attachment_image_src($id, 'thumbnail');
		    array_push($images_url,$img[0]);
		endforeach;		
	endif;

	return $images_url; 
}

/**
 * Get the thumbnail from post
 */
function gmshc_post_img($the_parent,$size = 'thumbnail'){
	
	if( function_exists('has_post_thumbnail') && has_post_thumbnail($the_parent)) {
	    $thumbnail_id = get_post_thumbnail_id( $the_parent );
		if(!empty($thumbnail_id))
		$img = wp_get_attachment_image_src( $thumbnail_id, $size );	
	} else {
	$attachments = get_children( array(
										'post_parent' => $the_parent, 
										'post_type' => 'attachment', 
										'post_mime_type' => 'image',
										'orderby' => 'menu_order', 
										'order' => 'ASC', 
										'numberposts' => 1) );
	if($attachments == true) :
		foreach($attachments as $id => $attachment) :
			$img = wp_get_attachment_image_src($id, $size);			
		endforeach;		
	endif;
	}
	if (isset($img[0])) return $img[0]; 
}

/**
 * Get the excerpt from content
 */
function gmshc_get_excerpt($post_id) { // Fakes an excerpt if needed

	$content_post = get_post($post_id);
	$content = $content_post->post_content;

	if ( '' != $content ) {

		$content = strip_shortcodes( $content ); 
		
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		$content = strip_tags($content);
		$excerpt_length = 10;
		$words = explode(' ', $content, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '[...]');
			$content = implode(' ', $words);
		}
	}
	return $content;
}

/**
 * Deploy the icons list to select one
 */
function gmshc_deploy_icons(){ 
	
	$options = get_gmshc_options();
	$icon_path = GMSC_PLUGIN_URL.'/images/icons/';
	$icon_dir = GMSC_PLUGIN_DIR.'/images/icons/';	
	
	$icons_array = $options['icons'];
	$default_icon = $options['default_icon'];
	
	if ($handle = opendir($icon_dir)) {
		
		while (false !== ($file = readdir($handle))) {
	
			$file_type = wp_check_filetype($file);
	
			$file_ext = $file_type['ext'];
		
			if ($file != "." && $file != ".." && ($file_ext == 'gif' || $file_ext == 'jpg' || $file_ext == 'png') ) {
				array_push($icons_array,$icon_path.$file);
			}
		}
	}
	?>
        <div class="gmshc_label">
        	<?php _e("Select the marker by clicking on the images"); ?>
        </div>	   
		<div id="gmshc_icon_cont">
        <input type="hidden" name="default_icon" value="<?php echo $default_icon ?>" id="default_icon" />			
		<?php foreach ($icons_array as $icon){ ?>
		  <div class="gmshc_icon <?php if ($default_icon == $icon) echo "gmshc_selected" ?>">
		  <img src="<?php echo $icon ?>" /> 
		  </div>
		<?php } ?>
		 </div>  	
	<?php
}


/**
 * Get post points form the post custom field 'google-map-sc'
 */
function gmshc_get_points($post_id) {

	$post_data = get_post_meta($post_id,'google-map-sc',true);
	$post_points = array();
	if($post_data != ""){
		$points = json_decode(urldecode($post_data), true);
		if(is_array($points)){
			foreach($points as $point){
				$point_obj = new GMSHC_Point();
				if ($point_obj -> create_point($point['address'],$point['ltlg'],$point['title'],$point['description'],$point['icon'],$point['thumbnail'],$post_id))
				array_push($post_points,$point_obj);
			}
		}
	
	} else {
		
	/**  checking for old custom fields **/
	$post_data_address = get_post_meta($post_id,'google-map-sc-address');	
	
	if (count($post_data_address) > 0) {
		$options = get_gmshc_options();		
		$default_icon = $options['default_icon'];	
		$post_title = get_the_title($post_id);
		foreach ($post_data_address as $point_address){
			$point_obj = new GMSHC_Point();
			if ($point_obj -> create_point($point_address,"",$post_title,"",$default_icon,"",$post_id)){
		
			array_push($post_points,$point_obj);
			}
		}
		if (count($post_points) > 0)		
		gmshc_save_points($post_id,$post_points);
	}
	
	}

	return $post_points;	
}


/**
 * Save the json data into the post custom field 'google-map-sc'
 */
function gmshc_save_points($post_id,$points) {
	$post_data = get_post_meta($post_id,'google-map-sc',true);

	$new_post_data = json_encode($points);

    if ($post_data == "null")  {
		delete_post_meta($post_id, 'google-map-sc');
		return add_post_meta($post_id, 'google-map-sc', $new_post_data, true);
	}
	else return update_post_meta($post_id,'google-map-sc',$new_post_data, $post_data);
   	
}


/**
 * Get the point from geocoding from address or latitude,longitude
 * http://code.google.com/apis/maps/documentation/geocoding/
 */
 
function gmshc_point ($address,$ltlg){

	$formatted_address = "";
	$point = "";
	$response = false;
	
	if (!empty($ltlg)) {
		$query = $ltlg;
		$type = "latlng";
	} else if (!empty($address)) { 
	
		$find = array("\n","\r"," ");
		$replace = array("","","+");					
		$address = str_replace( $find,$replace, $address);
			
		$query = $address;
		$type = "address";
	}
	
	else return false;	
	    
		$options = get_gmshc_options();
		$api_url = "http://maps.googleapis.com/maps/api/geocode/json?".$type."=".$query."&sensor=false&language=".$options['language'];
              
		$json_answ = @file_get_contents($api_url);
 
		if (empty($json_answ)) {
			if(function_exists('curl_init')){	
				$ch = curl_init();
				curl_setopt ($ch, CURLOPT_URL, $api_url);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				$json_answ = curl_exec($ch);
				curl_close($ch);
			} else {		
				echo "<div class='error'><p>".__("The Point can't be added, <strong>php_curl.dll</strong> is not installed on your server and <strong>allow_url_fopen</strong> is disabled.")."</p></div>";
				return false;						
			}
		}	
		 
		$answ_arr = json_decode($json_answ,true);
		
		if (isset($answ_arr["status"]) && $answ_arr["status"] == "OK"){		
			$formatted_address = $answ_arr["results"]["0"]["formatted_address"];
			$point = $answ_arr["results"]["0"]["geometry"]["location"]["lat"].",".$answ_arr["results"]["0"]["geometry"]["location"]["lng"];
		}
			
	if (!empty($point) && !empty($formatted_address)){
	
		$response = array('point'=>$point,'address'=>$formatted_address);
		
	}
	
	return $response;	

}

?>