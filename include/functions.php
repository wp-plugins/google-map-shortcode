<?php 
/**
 * Google Map Shortcode 
 * Version: 2.1
 * Author: Alain Gonzalez
 * Author URI: http://web-argument.com/
*/

function gmshc_generate_map($map_points, $width, $height, $zoom) {				
	
	  $canvas = "canvas_".wp_generate_password(4, false);
  
	  $output ='<div id="'.$canvas.'" class = "gmsc" style="width:'.$width.'px; height:'.$height.'px; margin:10px auto"></div>';
	  $output .= "<script type=\"text/javascript\">\n";
		  
	  $output .= "var map_".$canvas.";\n";		
	  $output .= "var map_points_".$canvas." =  new Array();\n";
	  
	  $i = 0;
	  			  
	  foreach ($map_points as $point){	  
		  
		  list($lat,$long) = explode(",",$point->ltlg);		  
		  $output .= "map_points_".$canvas."[".$i."] = \n";
		  $output .= "{\"address\":\"".$point->address."\",\n";
		  $output .= "\"lat\":\"".$lat."\",\n";
		  $output .= "\"long\":\"".$long."\",\n";
		  $output .= "\"info\":\"".gmshc_get_windowhtml($point)."\",\n";
		  $output .= "\"icon\":\"".$point->icon."\"};\n";
		  $i ++;
		  
	  }	  
	  $output .= "addLoadEvent(function(){\n";
	  $output .= "gmshc_render(\"".$canvas."\",map_points_".$canvas.", ".$zoom.");\n";	
	  $output .= "});\n";
	  $output .= "</script>\n";		
  
	  return $output;
}


/**
 * Get the html info
 *  
 * Allows a plugin to replace the html that would otherwise be returned. The
 * filter is 'gmshc_get_windowhtml' and passes the point.

 * add_filter('gmshc_get_windowhtml','default_html',1,2);
 * 
 * function default_html($windowhtml,$point){
 * 	return "this is the address".$point->address;
 * }
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
	$point_img_url = ($point->thumbnail != "")? $point->thumbnail : gmshc_post_thumb($point->post_id);
	$point_excerpt = gmshc_get_excerpt($post_id);
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
function gmshc_post_thumb($the_parent){
	
	if( function_exists('has_post_thumbnail') && has_post_thumbnail($the_parent)) {
	    $thumbnail_id = get_post_thumbnail_id( $the_parent );
		if(!empty($thumbnail_id))
		$img = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );	
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
			$img = wp_get_attachment_image_src($id, 'thumbnail');			
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
        	<strong><?php _e("Marker: "); ?></strong><?php _e("Select by clicking on the images"); ?>
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

		$json_answ = file_get_contents($api_url);
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