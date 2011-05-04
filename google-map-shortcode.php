<?php
/*
Plugin Name: Google Map Shortcode
Plugin URI: http://web-argument.com/google-map-shortcode-2-0-total-solution/
Description: Include Google Map in your blogs with just one click. 
Version: 2.0
Author: Alain Gonzalez
Author URI: http://web-argument.com/
*/

define('GMSC_PLUGIN_DIR', WP_PLUGIN_DIR."/".dirname(plugin_basename(__FILE__)));
define('GMSC_PLUGIN_URL', WP_PLUGIN_URL."/".dirname(plugin_basename(__FILE__)));
define('GMSHC_VERSION','2.0');

/**
 * Default Options
 */
function get_gmshc_options ($default = false){

	$gmshc_default = array(
							'zoom' => '10',
							'width' => '400',
							'height' => '400',
							'number' => 50,							
							'language' => 'en',
							'windowhtml' => gmshc_defaul_windowhtml(),
							'icon' => '',
							'use_icon' => 'default',
							'default_icon' => 'marker.png',
							'custom_icon' => '',
							'version' => GMSHC_VERSION
							);

    	
	if ($default) {
	update_option('gmshc_op', $gmshc_default);
	return $gmshc_default;
	}
	
	$options = get_option('gmshc_op');
	if (isset($options)){
	    if (isset($options['version'])) {	
			$chk_version = version_compare(GMSHC_VERSION,$options['version']);
			if ($chk_version == 0) 	return $options;
			else if ($chk_version > 0) $options = $gmshc_default;
        } else {
		$options = $gmshc_default;
		}
	}	
	update_option('gmshc_op', $options);
	return $options;
}


/**
 * Inserting files on the header
 */
function gmshc_head() {

	$options = get_gmshc_options();
	$language = $options['language'];
	$key = get_option('gmshc_key');
	
	$gmshc_header =  "\n<!-- Google Map Shortcode Version ".GMSHC_VERSION."-->\n";		
	$gmshc_header .= "<script src=\"http://maps.google.com/maps/api/js?sensor=false";
	if(isset($key)) 
	$gmshc_header .= "&key=".$key;
	if(isset($language)) 
	$gmshc_header .= "&language=".$language;
	$gmshc_header .="\" type=\"text/javascript\"></script>\n"; 
	$gmshc_header .= "<script type=\"text/javascript\" src=\"".GMSC_PLUGIN_URL."/google-map-sc-v3.js\"></script>\n";	
	$gmshc_header .=  "<!-- /Google Map Shortcode Version ".GMSHC_VERSION."-->\n";		
		
	print($gmshc_header);

}

add_action('wp_head', 'gmshc_head');


/**
 * Google Map SC Editor Button
 */
 
add_action('media_buttons', 'gmshc_media_buttons', 20);
function gmshc_media_buttons($admin = true)
{
	global $post_ID, $temp_ID;

	$media_upload_iframe_src = get_option('siteurl').'/wp-admin/media-upload.php?post_id=$uploading_iframe_ID';

	$iframe_title = __('Google Map Shortcode');

	echo "<a class=\"thickbox\" href=\"media-upload.php?post_id={$post_ID}&amp;tab=gmshc&amp;TB_iframe=true&amp;height=500&amp;width=680\" title=\"$iframe_title\"><img src=\"".GMSC_PLUGIN_URL."/images/marker.png\" alt=\"$iframe_title\" /></a>";
	
}

add_action('media_upload_gmshc', 'gmshc_tab_handle');
function gmshc_tab_handle() {
	return wp_iframe('gmshc_tab_process');
}

function gmshc_tab_process(){

	$options = get_gmshc_options();	

	$post_id = $_REQUEST["post_id"];
	$custum_fieds = get_post_custom($post_id);
	$points_addr = isset($custum_fieds['google-map-sc-address'])?$custum_fieds['google-map-sc-address'] : array();
	$points_ltlg = isset($custum_fieds['google-map-sc-latlng'])?$custum_fieds['google-map-sc-latlng'] : array();
	$add_point = isset($_REQUEST['add_point']) ? $_REQUEST['add_point'] : '';
	$del_point = isset($_REQUEST['delp']) ? $_REQUEST['delp'] : '';
	$update_point = isset($_REQUEST['update']) ? $_REQUEST['update'] : '';
	$width = isset($_REQUEST['width']) ? $_REQUEST['width'] : $options['width'];
	$height = isset($_REQUEST['height']) ? $_REQUEST['height'] : $options['height'];
	$zoom = isset($_REQUEST['zoom']) ? $_REQUEST['zoom'] : $options['zoom'];
	
	if (!empty($add_point)) {
	
		if (isset($_REQUEST['full_address'])){
			if ($gmshc_point = gmshc_point ($_REQUEST['full_address'],"")) {
	
					add_post_meta($post_id , "google-map-sc-address", $gmshc_point['address'], false);
					add_post_meta($post_id , "google-map-sc-latlng", $gmshc_point['point'], false);
				
			} else {
			
			echo "<div class='error'><p>".__("The Address can't be located.")."</p></div>";
			
			}
		}
	
	}

	if ($del_point != '') {
	
		foreach ($points_addr as $id => $single_addr) {
		
			if ($del_point == $id) delete_post_meta($post_id, "google-map-sc-address", $single_addr);
			
		}
			
		foreach ($points_ltlg as $id => $single_ltlg) {
			if ($del_point == $id) delete_post_meta($post_id, "google-map-sc-latlng", $single_ltlg);
		
		}
		
		echo "<div class='updated'><p>".__("The Point was deleted.")."</p></div>";
	
	}

	if (!empty($update_point)) {
	
		$posted_addr = $_REQUEST['addr']; 
		$posted_ltlg = $_REQUEST['ltlg'];
		
		for ($j = 0; $j < count($posted_ltlg); $j++ ) {
				
				update_post_meta($post_id, "google-map-sc-address", $posted_addr[$j], $points_addr[$j]);
	
				update_post_meta($post_id, "google-map-sc-latlng", $posted_ltlg[$j], $points_ltlg[$j]);
				
		}
		
		echo "<div class='updated'><p>".__("The Point was updated.")."</p></div>";
	}

	$custum_fieds = get_post_custom($post_id);
	$points_addr = isset($custum_fieds['google-map-sc-address'])?$custum_fieds['google-map-sc-address'] : array();
	$points_ltlg = isset($custum_fieds['google-map-sc-latlng'])?$custum_fieds['google-map-sc-latlng'] : array();
	
	?>
    <div style="width:620px; margin:10px auto">
    
        <form  action="#" method="post">
        
           <table width="620" border="0" cellspacing="10" cellpadding="10">
            <tr>
                <td colspan="2">
                <h3><?php _e("Map Dimensions"); ?></h3>
                </td>
            </tr>  
            <tr>
                <td align="right"><?php _e("Width"); ?></td>
                <td valign="top"><input name="width" type="text" id="width" size="10" value = "<?php echo $width ?>"/></td>
            </tr>  
            <tr>
                <td align="right"><?php _e("Height"); ?></td>
                <td valign="top"><input name="height" type="text" id="height" size="10" value = "<?php echo $height ?>" /></td>
            </tr>
            <tr>
                <td align="right"><?php _e("Zoom"); ?></td>
                <td valign="top"><input name="zoom" type="text" id="zoom" size="10" value = "<?php echo $zoom ?>" /></td>
            </tr>
            <tr>
                <td colspan="2">
                <h3><?php _e("Add New Point"); ?></h3>
                </td>
           </tr>        
            <tr>
                <td align="right" valign="top">
                <?php _e("Full Address"); ?>
                </td>
            <td valign="top">    
            <textarea name="full_address" cols="50" rows="4" id="full_address"></textarea>
            </td>
            </tr>      
            </table>	
                
        	<p><input class="button" value="<?php _e("Add Point") ?>" name="add_point" type="submit"></p>
            
			<?php
            if (count($points_addr) > 0 || count($points_ltlg) > 0 ){
            ?>
                    
            <table class="widefat" cellspacing="0">
                <thead>
                <tr>
                <th><?php _e("Address"); ?></th>
                <th><?php _e("Latitude/Longitude"); ?></th>		
                </tr>
                </thead>
                <tbody class="media-item-info">
                    <?php 
                    $i = 0;
                    while ($i < count($points_addr) || $i < count($points_ltlg)) {
					if (!isset( $points_ltlg[$i]) ){
						$this_ltlg = gmshc_point ($points_addr[$i],"");
						$points_ltlg[$i] = $this_ltlg['point'];
						update_post_meta($post_id, "google-map-sc-latlng", $points_ltlg[$i], "");
					} 	
					if (!isset( $points_addr[$i]) ){
						$this_addr = gmshc_point ("",$points_ltlg[$i]);
						$points_addr[$i] = $this_addr['address'];
						update_post_meta($post_id, "google-map-sc-address", $points_addr[$i], "");
					} 					
                    ?>            
                    <tr>
                        <td>
                        <textarea name="addr[]" cols="30" rows="3" id="addr_<?php echo $i ?>"><?php echo $points_addr[$i] ?></textarea>
                        </td>
                        <td> 
                        <input name="ltlg[]" type="text" id="ltlg_<?php echo $i ?>" size="30" value = "<?php echo $points_ltlg[$i] ?>"/>
                        <div style="padding:15px 0;">
                        <input class="button" value="<?php _e("Update"); ?>" name="update" type="submit"> 
                        <a href="" class="thickbox" onclick="delete_point(<?php echo $i ?>); return false"><?php _e("Delete"); ?></a>
                        </div>
                        </td>
                    </tr>	
                    <?php  	
                    $i ++;
                    }
                    ?>           
                </tbody> 	    
            </table>
        
        	<p><input class="button-primary" value="<?php _e("Insert Map"); ?>" type="button" onclick="add_map(); return false;"></p>
        
			<?php } ?>
		</form>
	</div>


	<script type="text/javascript">
    
    function add_map (){
        var width = jQuery("#width").val();
        var height = jQuery("#height").val();
        var zoom = jQuery("#zoom").val();
        
        str = "[google-map-sc";
		if (width != '')
		str += " width="+width;
		if (height != '')
		str += " height="+height;
		if (zoom != '')
		str += " zoom="+zoom;				
		str +="]"; 
        
        if (parent.tinyMCE){
            parent.tinyMCE.activeEditor.setContent(parent.tinyMCE.activeEditor.getContent() + str);
        }else{
            parent.document.getElementById("content").value = parent.document.getElementById("content").value + "\r\n" +str;
        }
    }
    
    function delete_point(id){
        var answer = confirm ('<?php _e("You will not be able to roll back deletion. Are you sure?"); ?>');
        if (answer) {
        var width = jQuery("#width").val();
        var height = jQuery("#height").val();
        var zoom = jQuery("#zoom").val();
        
        var url = "?post_id=<?php echo $post_id ?>&tab=gmshc&delp="+id+"&width="+width+"&height="+height+"&zoom="+zoom;
        window.location = url;
        } else {
        return false;
        }	
    }
    
    </script>

<?php
}


/**
 * Default Open Window Html
 */
 function gmshc_defaul_windowhtml(){
 
	$defaul_gmshc_windowhtml = "<div style='margin:0; padding:0px; height:110px; width:310px; overflow:hidden'>\n";
	$defaul_gmshc_windowhtml .= "<div style='float:left; width:200px'>\n";
	$defaul_gmshc_windowhtml .= "<a class='title' href='%link%' style='clear:both; display:block'>%title%</a>\n";
	$defaul_gmshc_windowhtml .= "<div style='font-size:11px; clear:both'><strong>%address%</strong></div>\n";
	$defaul_gmshc_windowhtml .= "<div style='font-size:11px; clear:both; line-height:16px'>%excerpt%</div>\n";
	$defaul_gmshc_windowhtml .= "<a href='%link%' style='font-size:11px; float:left; display:block'>more &raquo;</a>\n";
	$defaul_gmshc_windowhtml .= "<img src='".GMSC_PLUGIN_URL."/images/open.jpg\' style='float: right; margin-right:5px'/> \n";
	$defaul_gmshc_windowhtml .= "<a href='%open_map%' target='_blank' style='font-size:11px; float: right; display:block;'>Open Map</a>\n";
	$defaul_gmshc_windowhtml .= "</div>\n";
	$defaul_gmshc_windowhtml .= "<img src='%thubnail%' style='float:left; margin:8px 0 0 8px; width:90px; height:90px'/>\n";	
	$defaul_gmshc_windowhtml .= "</div>\n";
	
	return $defaul_gmshc_windowhtml;

}


/**
 * Get the thumbnail
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
 * The Sortcode
 */
 
add_shortcode('google-map-sc', 'gmshc_sc');

function gmshc_sc($atts) {

	global $post;
	$options = get_gmshc_options();	
	
	$width = $options['width'];
	$height = $options['height']; 
	$zoom = $options['zoom']; 
	$number = $options['number'];
	$icon = $options['icon'];
	$language = $options['language'];	

	// First Point in the post
	$address_meta = get_post_meta($post -> ID, 'google-map-sc-address');
	$point_meta = get_post_meta($post -> ID, 'google-map-sc-latlng');			
	
	$the_address = isset($address_meta[0]) ? $address_meta[0] : '';
	$point = isset($point_meta[0]) ? $point_meta[0] : '';
	$the_items = array();
	
	extract(shortcode_atts(array(
		'address' => '',	
		'id' => '',
		'cat' => '',
		'number' => $number,
		'zoom' => $zoom,
		'width' => $width,
		'height' => $height,
		'icon' => $icon,
		'language' => $language	
	), $atts));


// When address is set
 if (!empty($address)) {
 
		 if( $item = gmshc_fill_item('','',$address) ) {
		 $the_items[0] = $item; 
		 } else {	 	
			return __("The Address can't be located.");
		 } 
 // When id is set
 } else if (!empty($id)) {

    $post_obj = get_posts(array('include'=>$id,'numberposts'=>1));

	if(isset($post_obj)) {
	 
		if ($post_points = gmshc_retrive_point($post_obj[0])){
		
			for ($i = 0; $i < count($post_points); $i++) {
			     
				 if ($i == $number) break;
				 
				 if($item = gmshc_fill_item($post_obj[0], $post_points[$i]['point'],$post_points[$i]['address'])) {
				 $the_items[$i] = $item; 
				 }

			 
			}		
		
		}	   
	
	}
	 
} else if ($cat != '') {

	$categories = split (",",$cat); 
	$j = 0;
	
	$post_obj = get_posts(array('category__in'=>$categories,'numberposts'=>-1));

	foreach ($post_obj as $post_item) {
	

		if ($j < $number) {
		
			if ($post_points = gmshc_retrive_point($post_item)){
			
				for ($i = 0; $i < count($post_points); $i++) {
					 
					 if ($i == $number) break;
					 
					 if($item = gmshc_fill_item($post_item, $post_points[$i]['point'],$post_points[$i]['address'])) {
					 $the_items[$j] = $item; 
					 $j++;
	                 } 
				 
				}		
			
			}
			
				  										
		
		}
	
	}			
			

 }  else {

	if ($post_points = gmshc_retrive_point($post)){
	
		for ($i = 0; $i < count($post_points); $i++) {
			 
			 if ($i == $number) break;
			 
			 if( $item = gmshc_fill_item($post, $post_points[$i]['point'],$post_points[$i]['address']) ) {
			 	$the_items[$i] = $item; 
             }
		 
		}		
	
	}

}

		
	if ( count($the_items) > 0 ) {
	
	
	$canvas = "canvas_".wp_generate_password(4, false);

	
	$i = 0;

	
		$output ='<div id="'.$canvas.'" class = "gmsc" style="width:'.$width.'px; height:'.$height.'px; margin:10px auto"></div>';
		$output .= "<script type=\"text/javascript\">\n";
			
		$output .= "var map_".$canvas.";\n";		
		$output .= "var map_points_".$canvas." =  new Array();\n";
					
		foreach ($the_items as $single_point){

			$options = get_option('gmshc_op');
			$windowhtml = $options['windowhtml'];
			
			list($lat, $long) = split(",",$single_point['point']);
			
			if (isset($single_point['address'])) {

				if (empty($windowhtml)) $windowhtml = gmshc_defaul_windowhtml();

				$open_map = "http://maps.google.com/?q=".str_replace(" ","%20",$single_point['address']);
				$point_title = isset($single_point['title'])?$single_point['title']:"";
				$point_link = isset($single_point['link'])?$single_point['link']:"";
				$point_img = isset($single_point['img'])?$single_point['img']:"";
				$point_excerpt = isset($single_point['excerpt'])?$single_point['excerpt']:"";
				
				
				$find = array("\f","\v","\t","\r","\n","\\","\"","%title%","%link%","%thubnail%", "%excerpt%","%address%","%open_map%");
				$replace  = array("","","","","","","'",$point_title,$point_link,$point_img,$point_excerpt,$single_point['address'],$open_map);
				
				$info = str_replace( $find,$replace, $windowhtml);
			
		         //open map
			
				$output .= "map_points_".$canvas."[".$i."] = {\"address\":\"".$single_point['address']."\",\"point\":{\"lat\":\"".$lat."\",\"long\":\"".$long."\"},\"info\":\"".$info."\",\"icon\":\"".$icon."\"};\n";
			
			}	else {
			
				$output .= "map_points_".$canvas."[".$i."] = {\"address\":\"".$single_point['address']."\",\"point\":{\"lat\":\"".$lat."\",\"long\":\"".$long."\"},\"icon\":\"".$icon."\"};\n";
			
			}	
		
			$i ++;
		}
		
		$output .= "addLoadEvent(function(){\n";
		$output .= "gmshc_render(\"".$canvas."\",map_points_".$canvas.", ".$zoom.");\n";	
		$output .= "});\n";
		$output .= "</script>\n";	
					
		
		return $output;	
	
	
} 

	else return __("There is not points to locate on the map");


}


function gmshc_get_excerpt($text) { // Fakes an excerpt if needed

	if ( '' != $text ) {

		$text = strip_shortcodes( $text ); 
		
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = 10;
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '[...]');
			$text = implode(' ', $words);
		}
	}
	return $text;
}



function gmshc_fill_item($post,$point,$address){

		if (empty($point) && empty($address)) return false;
		
		if (empty($point) && !empty($address)) {
		
			 if ($the_point = gmshc_point($address,"")) {
				 $address = $the_point['address'];
				 $point = $the_point['point'];		
		     } else {
			 	return false;
			 }
		}		
			
			if (!empty($post)){
			
				$the_image = gmshc_post_thumb($post -> ID);
				$the_title = $post -> post_title;
				$the_link = get_permalink($post -> ID);
				
				$find = array("\"", "[", "]", "\n","\r");
				$replace  = array("'","","","","");
				
				$the_excerpt = str_replace( $find,$replace, gmshc_get_excerpt($post -> post_content));
				
				if (!empty($address)) {					
				
				$item = array("point"=>$point,"address" => $address,"img" => $the_image,"title" => $the_title,"link" => $the_link, "excerpt" => $the_excerpt);
				
				} else {
				
				$item = array("point"=>$point,"img" => $the_image,"title" => $the_title,"link" => $the_link, "excerpt" => $the_excerpt);
				
				}

			
			} else {
			
				$item = array("point"=>$point,"address" => $address);
			
			}
			
			return $item;	
}

function gmshc_retrive_point($post) {

	$post_points = array();
	$custum_fieds = get_post_custom($post->ID);
	$points_addr = isset($custum_fieds['google-map-sc-address'])?$custum_fieds['google-map-sc-address'] : array();
	$points_ltlg = isset($custum_fieds['google-map-sc-latlng'])?$custum_fieds['google-map-sc-latlng'] : array();
	
	$num_addr = count($points_addr);
	$num_ltlg = count($points_ltlg ); 	
	
	if ($num_ltlg > 0 && $num_ltlg==$num_addr){
	
		for ($i = 0; $i < $num_ltlg; $i ++){
			$post_points[$i] = array('point'=>$points_ltlg[$i],'address'=>$points_addr[$i]);		
		}
		
	} else {
	
		for ($i = 0; $i < $num_ltlg; $i ++){
		    if($point = gmshc_point ('',$points_ltlg[$i])){
			$address = $point['address'];
			$post_points[$i] = array('point'=>$points_ltlg[$i],'address'=>$address);
			}		
		}	
	
	}


	if(count($post_points) > 0) return $post_points;
	else return false;

} 


/**
 * Settings
 */  

add_action('admin_menu', 'gmshc_set');

function gmshc_set() {
    add_options_page('Google Map Shortcode', 'Google Map Shortcode', 'administrator', 'google-map-shortcode', 'gmshc_options_page');	 
}

function gmshc_options_page() {

	$options = get_gmshc_options();
    $gmshc_key = get_option('gmshc_key');
	$icon_path = GMSC_PLUGIN_URL.'/images/icons/';
	$icon_dir = GMSC_PLUGIN_DIR.'/images/icons/';

	?>
	
	<div class="wrap">   
	
	<h2><?php _e("Google Map Shortcode Settings") ?></h2>
	
	<?php 

	if(isset($_POST['Submit'])){
	
		if ($_POST['use_icon'] == "custom" && $_POST['custom_icon'] == "") {
		
			echo "<div class='error'><p>".__("Please upload a custom icon.")."</p></div>";
		
		} else {

			$new_gmshc_key = $_POST['gmshc_key'];
			$newoptions['width'] = $_POST['width'];
			$newoptions['height'] = $_POST['height'];
			$newoptions['zoom'] = $_POST['zoom'];
			$newoptions['number'] = $_POST['number'];
			$newoptions['language'] = $_POST['language'];
			$newoptions['windowhtml'] = $_POST['windowhtml'];
			
			$newoptions['use_icon'] = $_POST['use_icon'];
			$newoptions['default_icon'] = $_POST['default_icon'];
			$newoptions['custom_icon'] = $_POST['custom_icon'];
			
			if ($_POST['use_icon'] == 'default')
			$newoptions['icon'] = $icon_path .$_POST['default_icon'];
			else $newoptions['icon'] = $_POST['custom_icon'];
			
			$newoptions['version'] = GMSHC_VERSION;
	
			if ( $options != $newoptions ) {
				$options = $newoptions;
				update_option('gmshc_op', $options);			
			}
			if ( $gmshc_key != $new_gmshc_key ) {
				$gmshc_key = $new_gmshc_key;
				update_option('gmshc_key', $options);			
			}
		
		}			
	    
 	} 

	if(isset($_POST['Use_Default'])){

		$options['windowhtml'] = gmshc_defaul_windowhtml();
        update_option('gmshc_op', $options);
	
    } 

	if(isset($_POST['upload']) && isset($_FILES) ){

       $filename = $_FILES["datafile"]["name"];
 
       $upload = wp_upload_bits($filename, NULL, file_get_contents($_FILES["datafile"]["tmp_name"]));

		if ( ! empty($upload['error']) ) {
			$errorString = sprintf(__('Could not write file %1$s (%2$s)'), $filename, $upload['error']);
			echo "<div class='error'><p><strong>".$errorString."</strong></p></div>";
		}  else {     
		
		$options['custom_icon'] = $upload['url'];
		update_option('gmshc_op', $options);
		
		}
		
    }

	$width = isset ($_POST['width'])? $_POST['width']: $options['width'];
	$height = isset ($_POST['height'])? $_POST['height'] : $options['height'];
	$zoom = isset ($_POST['zoom'])? $_POST['zoom']: $options['zoom'];
	$number = isset ($_POST['number'])? $_POST['number']: $options['number'];
	$language = isset ($_POST['language'])? $_POST['language']: $options['language'];
	$windowhtml = $options['windowhtml'];
	$icon = $options['width'];
	$use_icon = isset ($_POST['use_icon'])? $_POST['use_icon']: $options['use_icon'];
	$default_icon = isset ($_POST['default_icon'])? $_POST['default_icon']: $options['default_icon'];
	$custom_icon = $options['custom_icon'];

	?>  
	
	<form method="POST" name="options" target="_self" enctype="multipart/form-data">
	
	<h3><?php _e("Maps Parameters") ?></h3>
	
	<p><?php _e("The shortcode attributes overwrite these options.") ?></p>
	
	<?php 
	
	$icons_array = array();
	if ($handle = opendir($icon_dir)) {
		$i = 0;
		while (false !== ($file = readdir($handle))) {
	
			$file_type = wp_check_filetype($file);
	
			$file_ext = $file_type['ext'];
		
			if ($file != "." && $file != ".." && ($file_ext == 'gif' || $file_ext == 'jpg' || $file_ext == 'png') ) {
				$icons_array[$i] = $file;
				$i ++;
			}
		}
	}
	
	?>

    <table width="80%%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td colspan="2"><strong><?php _e("Dimensions") ?></strong></td>
      </tr>  
      <tr>
        <td width="150" align="right" height="40"><?php _e("Width") ?></td>
        <td><input name="width" type="text" size="6" value="<?php echo $width ?>" /></td>
      </tr>
      <tr>
        <td align="right"><?php _e("Height") ?></td>
        <td><input name="height" type="text" size="6" value="<?php echo $height ?>" /></td>
      </tr>
      <tr>
        <td align="right"><?php _e("Zoom") ?></td>
        <td><input name="zoom" type="text" size="6" value="<?php echo $zoom ?>" /></td>
      </tr> 
      <tr>
        <td align="right"><?php _e("Maximum number of point in a single map") ?></td>
        <td><input name="number" type="text" size="6" value="<?php echo $number ?>" /></td>
      </tr>       
      <tr>
        <td colspan="2"><strong><?php _e("Select Icon") ?></strong></td>
      </tr>   
      <tr>
        <td width="150" align="right" height="40">
        <input name="use_icon" type="radio" value="default" style="margin-right:10px;" <?php echo ($use_icon == "default" ? "checked=\"checked\"" : "") ?>/><?php _e("Use default icons") ?>
        </td>
        <td>
        <div style="width:30px; height:30px; float:left; margin:0 15px">
        <img src="<?php echo $icon_path.$default_icon ?>" alt="icon" id="icon_img" />
        </div>
        <select name="default_icon" id = "default_icon">
        <?php foreach ($icons_array as $icon){ ?>
          <option value="<?php echo $icon ?>" <?php echo ($icon == $default_icon ? "selected" : "") ?>><?php echo $icon ?></option>
        <?php } ?>
        </select> 
                
        </td>
      </tr>
      <tr>
        <td align="right" valign="top"><input name="use_icon" type="radio" value="custom" style="margin-right:10px;" <?php echo ($use_icon == "custom" ? "checked=\"checked\"" : "") ?> /><?php _e("Use custom icons") ?>
        </td>
        <td>
            <div style="width:30px; height:30px; float:left; margin:0 15px">
                <?php if ($custom_icon != "") { ?>
                <img src="<?php echo $custom_icon ?>" alt="icon" id="icon_img" />
                <?php } ?>
            </div>    
            <?php _e("Please specify a file:") ?><br />
            <input type="file" name="datafile" size="40" />
            <p>
            <input type="submit" name="upload" value="Upload" class="button"  style="margin-left:50px" />
            </p>
            <input name="custom_icon" type="hidden" size="18" value="<?php echo $custom_icon ?>" /> 
        </td>
      </tr>
      <tr>
        <td colspan="2"><strong><?php _e("Language") ?></strong></td>
      </tr>   
      <tr>
        <td align="right" valign="top"><?php _e("Select") ?>
        </td>
        <td>  
        <?php 
        $lang_array = array(
                            "zh" => __("Chinese"),
                            "nl" => __("Dutch"),
                            "en" => __("English"),
                            "fr" => __("French"),
                            "de" => __("German"),
                            "it" => __("Italian"),
                            "pl" => __("Polish"),
                            "ja" => __("Japanese"),
                            "es" => __("Spanish"),
                            "ca" => __("Catalan"),
                            "gl" => __("Galego"),
                            "eu" => __("Euskara")
                                                
        ); 
        ?> 
        <select name="language" id="language">
            <?php foreach($lang_array  as $lg => $lg_name){ ?>
                <option value="<?php echo $lg ?>" <?php echo ($lg == $language ? "selected" : "") ?> ><?php echo $lg_name ?></option>
            <?php } ?>
        </select>   
        </td>
      </tr>   
    </table>
    
    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" />
    </p>
    
    <h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;"><?php _e("Info Windows") ?></h3>
    
    <p><?php _e("This is the html inside of the Map Info Window opened after clicking on the markers, you can include the following tags.") ?></p>
    
    <table width="80%%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td width="150" align="right"><strong>%title%</strong></td>
        <td><?php _e("The title of your post") ?></td>
      </tr>
      <tr>
         <td align="right"><strong>%link%</strong></td>
        <td><?php _e("The link to your post") ?></td>
      </tr>
      <tr>
        <td align="right"><strong>%thubnail%</strong></td>
        <td><?php _e("The thubnail of the last image attached to your post") ?></td>
      </tr>
      <tr>
        <td align="right"><strong>%excerpt%</strong></td>
        <td><?php _e("The excerpt of your post") ?></td>
      </tr>  
      <tr>
        <td align="right"><strong>%address%</strong></td>
        <td><?php _e("The address of this point in the map") ?></td>
      </tr>
      <tr>
        <td align="right"><strong>%open_map%</strong></td>
        <td><?php _e("Open this point on Google Map") ?></td>
      </tr>      
    </table>
    <br />
    
    <textarea name="windowhtml" cols="110" rows="12" id="windowhtml">
    <?php  
    if  (empty($windowhtml)) echo gmshc_defaul_windowhtml(); 
    else echo str_replace("\\", "",$windowhtml);
    ?>
    </textarea>
    
    <p align="right" style="width:800px; padding:0">
    <input type="submit" name="Use_Default" value="Restore Default"/>
    </p>
    
    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" />
    </p>
    
    <h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;"><?php _e("Google Map Api Key") ?></h3>
    
    <p><?php _e("Enter your Google Map Api Key. You can get it <a href='http://code.google.com/apis/maps/signup.html' target='_blank'>here</a>. This is not required. Google Maps JavaScript API V3 no longer needs API keys!") ?></p>
    <p><input name="gmshc_key" type="text" value="<?php echo $gmshc_key ?>" size="105"/>
    
    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" />
    </p>
    
    <h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;">Use</h3>
    <p>You can include a Google Map Shortcode everywhere</p>
    
    <p>In your post using: <strong>[google-map-sc option = "option value"]</strong></p>
    <p>In your theme files using: <strong> < ?php echo do_shortcode [google-map-sc option = "option value"] ? ></strong></p>
    
    <h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;">Options</h3>
    
    <table width="80%%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td width="150"><div align="right"><strong>address</strong></div></td>
        <td>Specific address</td>
      </tr>
      <tr>
        <td><div align="right"><strong>id</strong></div></td>
        <td>Specific post ID</td>
      </tr>
      <tr>
        <td><div align="right"><strong>cat</strong></div></td>
        <td>Include post under this categories. (category number separated by comma)</td>
      </tr>
      <tr>
        <td><div align="right"><strong>number</strong></div></td>
        <td>Number of points/post on your map (Default 10)</td>
      </tr>
      <tr>
        <td><div align="right"><strong>zoom</strong></div></td>
        <td>Inicial zoom (Default 10)</td>
      </tr>
      <tr>
        <td><div align="right"><strong>width</strong></div></td>
        <td>Width of your map</td>
      </tr> 
      <tr>
        <td><div align="right"><strong>height</strong></div></td>
        <td>Height of your map</td>
      </tr>        
    </table>
    
    <h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;">Feedback</h3>
    
    <p>For more details and examples visite the <a href="http://web-argument.com/2011/05/04/google-map-shortcode-2-0-total-solution/">Plugin Page</a>. All the comments are welcome.</p>
    
    
    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" />
    </p>
    </form>
    </div>

	<script type="text/javascript">
    
    (function ($) {
    
         $(document).ready(function(){    
         
            $("#default_icon").click(function(){
                switchImg(this); 
            });
            
            var iconSelect = "";
            function switchImg (obj) {
                var iconName = $(obj).val();			
                var imgUrl = '<?php echo $icon_path ?>'+iconName;
                $("#icon_img").attr('src',imgUrl);
            }		
        
         });
		 
    })(jQuery);
    
    </script>

<?php } 

function gmshc_point ($address,$ltlg){

	if (!empty($ltlg)) $query = $ltlg;
	else if (!empty($address)) { 
	
		$find = array("\n","\r"," ");
		$replace = array("","","+");					
		$address = str_replace( $find,$replace, $address);
			
		$query = $address;
	}
	else return false;	

	$gmshc_key = get_option('gmshc_key');
	
	$url = 'http://maps.google.com/maps/geo?q='.$query.'&key='.$gmshc_key.'&sensor=false&output=xml&oe=utf8';
	
	$response = gmshc_xml2array($url);
	
	if (isset($response['kml']['Response']['Placemark']['Point'])) {
	
		$coordinates = $response['kml']['Response']['Placemark']['Point']['coordinates'];
		$address = $response['kml']['Response']['Placemark']['address'];
		
		if (!empty($coordinates)) {
		
		$point_array = split(",",$coordinates);
		
		$point = $point_array[1].",".$point_array[0];
		
		$response = array('point'=>$point,'address'=>$address);
		
		return  $response;
		
		}
	
	} else {
	
	return  false;
	
	}

}

//from http://us3.php.net/manual/en/function.xml-parse.php
function gmshc_xml2array($url, $get_attributes = 1, $priority = 'tag')
{
    $contents = "";
    if (!function_exists('xml_parser_create'))
    {
        return array ();
    }
    $parser = xml_parser_create('');
    if (!($fp = @ fopen($url, 'rb')))
    {
        return array ();
    }
    while (!feof($fp))
    {
        $contents .= fread($fp, 8192);
    }
    fclose($fp);
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);
    if (!$xml_values)
        return; //Hmm...
    $xml_array = array ();
    $parents = array ();
    $opened_tags = array ();
    $arr = array ();
    $current = & $xml_array;
    $repeated_tag_index = array ();
    foreach ($xml_values as $data)
    {
        unset ($attributes, $value);
        extract($data);
        $result = array ();
        $attributes_data = array ();
        if (isset ($value))
        {
            if ($priority == 'tag')
                $result = $value;
            else
                $result['value'] = $value;
        }
        if (isset ($attributes) and $get_attributes)
        {
            foreach ($attributes as $attr => $val)
            {
                if ($priority == 'tag')
                    $attributes_data[$attr] = $val;
                else
                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }
        if ($type == "open")
        {
            $parent[$level -1] = & $current;
            if (!is_array($current) or (!in_array($tag, array_keys($current))))
            {
                $current[$tag] = $result;
                if ($attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                $current = & $current[$tag];
            }
            else
            {
                if (isset ($current[$tag][0]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    );
                    $repeated_tag_index[$tag . '_' . $level] = 2;
                    if (isset ($current[$tag . '_attr']))
                    {
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset ($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        }
        elseif ($type == "complete")
        {
            if (!isset ($current[$tag]))
            {
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if ($priority == 'tag' and $attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
            }
            else
            {
                if (isset ($current[$tag][0]) and is_array($current[$tag]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data)
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    );
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes)
                    {
                        if (isset ($current[$tag . '_attr']))
                        {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                        if ($attributes_data)
                        {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        }
        elseif ($type == 'close')
        {
            $current = & $parent[$level -1];
        }
    }
    return ($xml_array);
}

?>
