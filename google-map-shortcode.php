<?php
/*
Plugin Name: Google Map Shortcode
Plugin URI: http://web-argument.com/google-map-shortcode/
Description: Include Google Map in your blog displaying your post address using differents parameters. 
Version: 1.0
Author: Alain Gonzalez
Author URI: http://web-argument.com/
*/


/**
 * Inserting files on the header
 */
function gmshc_head() {

     $options = get_option('gmshc_op');
	 $gmshc_key = $options['gmshc_key'];

    $wpchkt_header =  "\n<!-- Google Map Shortcode -->\n";		
    $wpchkt_header .= "<script src=\"http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=".$gmshc_key."\" type=\"text/javascript\"></script>\n"; 
	$wpchkt_header .= "<script type=\"text/javascript\" src=\"".get_bloginfo('url')."/wp-content/plugins/google-map-shortcode/google-map-sc.js\"></script>\n";	
	$wpchkt_header .=  "\n<!-- Google Map Shortcode-->\n";	
            
print($wpchkt_header);

}

add_action('wp_head', 'gmshc_head');
	

/**
 * Default Open Window Html
 */

$defaul_gmshc_windowhtml  = "<div style='padding:0 10px 20px; width:250px; height:150px'>\n";
$defaul_gmshc_windowhtml .= "<p style='margin:0'><strong><a class='title' href='%link%'>%title%</a></strong></p>\n";
$defaul_gmshc_windowhtml .= "<p align='left' style='font-size:10px'><strong>%address%</strong></p>\n";
$defaul_gmshc_windowhtml .= "<table border='0' cellspacing='0' cellpadding='5' height='80'>\n";
$defaul_gmshc_windowhtml .= "<tr>\n";
$defaul_gmshc_windowhtml .= "<td valign='top' align='left'><a class='title' href='%link%'><img src='%thubnail%' style='width:100px; padding-right:15px'/></a></td>\n";
$defaul_gmshc_windowhtml .= "<td valign='top' align='left'>\n";
$defaul_gmshc_windowhtml .= "%excerpt%\n";
$defaul_gmshc_windowhtml .= "<p><a class='title' href='%link%'>more &raquo;</a></p>\n";
$defaul_gmshc_windowhtml .= "</td>\n";
$defaul_gmshc_windowhtml .= "</tr>\n";
$defaul_gmshc_windowhtml .= "</table>\n";
$defaul_gmshc_windowhtml .= "</div>\n";	

/**
 * The Sortcode
 */
 
add_shortcode('google-map-sc', 'gmshc_sc');

function gmshc_sc($atts) {
	global $post;
	$the_address = "";
	
	extract(shortcode_atts(array(
		'address' => '',	
		'id' => '',
		'cat' => '',
		'number' => '',
		'zoom' => '10',
		'width' => '400',
		'height' => '400',
		'external_links' => false		
	), $atts));

	$canvas = "canvas_".wp_generate_password(4, false);
	
	$the_address = get_post_meta($post->ID, 'google-map-sc-address');
	
	$find = array("\n","\r");
	$replace = array("","",);
					
    $the_address = str_replace( $find,$replace, $the_address);

 if (!empty($address)){
 
 	$output ='<div id="'.$canvas.'" style="width:'.$width.'px; height:'.$height.'px" class="google-map-sc-canvas"></div>';
	$output .= "<script type=\"text/javascript\">\n";
	$output .= "var map_".$canvas.";\n";
	
	$output .= "var map_points_".$canvas." = [{name:\"".$address."\"}];\n";
	
	$output .= "addLoadEvent(function(){\n";
	$output .= "map_".$canvas." = new gmshc_render(\"".$canvas."\",map_points_".$canvas.", ".$zoom.");\n";	
	$output .= "});\n";
	$output .= "addEvent(window,'unload',GUnload,false);\n";	
	$output .= "</script>\n";

	
}
	
else if (!empty($id)){
	
	$address = get_post_meta($id, 'google-map-sc-address');
	
	if(!empty($address[0])){
	
	$find = array("\n","\r");
	$replace = array("","",);
					
    $address[0] = str_replace( $find,$replace, $address[0]);	
	
 	$output ='<div id="'.$canvas.'" style="width:'.$width.'px; height:'.$height.'px" class="google-map-sc-canvas"></div>';
	$output .= "<script type=\"text/javascript\">\n";
	$output .= "var map_".$canvas.";\n";
	
	$output .= "var map_points_".$canvas." = [{name:\"".$address[0]."\"}];\n";
	
	$output .= "addLoadEvent(function(){\n";
	$output .= "map_".$canvas." = new gmshc_render(\"".$canvas."\",map_points_".$canvas.", ".$zoom.");\n";	
	$output .= "});\n";
	$output .= "addEvent(window,'unload',GUnload,false);\n";	
	$output .= "</script>\n";	
	
	}

}

else if ( (empty($cat)) & (empty($number)) & (!($external_links)) ) {

	if(!empty($the_address[0])){
	
 	$output ='<div id="'.$canvas.'" style="width:'.$width.'px; height:'.$height.'px" class="google-map-sc-canvas"></div>';
	$output .= "<script type=\"text/javascript\">\n";
	$output .= "var map_".$canvas.";\n";

	$output .= "var map_points_".$canvas." = [{name:\"".$the_address[0]."\"}];\n";
	
	$output .= "addLoadEvent(function(){\n";
	$output .= "map_".$canvas." = new gmshc_render(\"".$canvas."\",map_points_".$canvas.", ".$zoom.");\n";	
	$output .= "});\n";
	$output .= "addEvent(window,'unload',GUnload,false);\n";	
	$output .= "</script>\n";	
	
	}
	
} 	
	
else {
  
	$old_post = $post;
	
	$gm_item = gmshc_items($number, $cat);
	
	$i = 0;
	
	$external_links_items = '';
	
	if (!empty($gm_item	)){
	
		$output ='<div id="'.$canvas.'" style="width:'.$width.'px; height:'.$height.'px" class="google-map-sc-canvas"></div>';
		$output .= "<script type=\"text/javascript\">\n";
			
		$output .= "var map_".$canvas.";\n";		
		$output .= "var map_points_".$canvas." =  new Array();\n";
					
		foreach ($gm_item as $item){

		    global $defaul_gmshc_windowhtml;
			
			$options = get_option('gmshc_op');
			$gmshc_windowhtml = $options['gmshc_windowhtml'];
			
			if (empty($gmshc_windowhtml)) $gmshc_windowhtml = $defaul_gmshc_windowhtml;
			
			$find = array("\r","\n","\\","\"","%title%","%link%","%thubnail%", "%excerpt%");
			$replace  = array("","","","'",$item['title'],$item['link'],$item['img'],$item['excerpt']);
			
			$info = str_replace( $find,$replace, $gmshc_windowhtml);
			
			
			
			
			$output .= "map_points_".$canvas."[".$i."] = {name:\"".$item['address']."\",info:\"".$info."\"};\n";

	
			if ($external_links){
	
					$external_links_items .= "<li><a href='#' onclick='map_".$canvas.".findPoint(".$i."); return false'>".$item['title']."</a></li>";
			}
			
			$i ++;
		}
		
		$output .= "addLoadEvent(function(){\n";
		$output .= "map_".$canvas." = new gmshc_render(\"".$canvas."\",map_points_".$canvas.", ".$zoom.");\n";	
		$output .= "});\n";
		$output .= "addEvent(window,'unload',GUnload,false);\n";	
		$output .= "</script>\n";	
					
	if ($external_links){
	
		$output .= "<div class='map_links'>";
		$output .= "<ul>";
		$output .= $external_links_items; 	
		$output .= "<ul>";
		$output .= "</div";		
	
	}		
		
	}
	
} 
	

$post = $old_post;

     
return $output;
	

}

function gmshc_get_excerpt() { // Fakes an excerpt if needed
	if ( '' == $text ) {
		$text = get_the_content('');
		
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



/**
 * Get the items
 */

// Eliminate query limits
function gmshc_limit_post($limits){

return  '';

} 
 
function gmshc_items($number, $categories){

add_filter('post_limits', 'gmshc_limit_post');
       
		//to avoid exceed the google request limit
		if  ( (empty($number))  ||  ($number > 10) ) $number = 10;

			$i = 0;	

		if (empty($categories)){

			$my_query = new WP_Query(array('showposts'=>$limits));
			
		}
		
		else {
		
			$cat = split (",",$categories); 
			
			$my_query = new WP_Query(array('category__in'=>$cat,'showposts'=>$limits));		
		
		}	
					

	
			while ($my_query->have_posts()) : $my_query->the_post();
			
				if ($i == $number) break; 
			
				$address_meta = get_post_meta(get_the_ID(), 'google-map-sc-address');			
				
				$address = $address_meta[0];
					
					if(!empty($address)){
					
					$find = array("\n","\r");
					$replace = array("","",);
				
					$address = str_replace( $find,$replace, $address);					
					
					
					$the_image = gmshc_post_thumb(get_the_ID());
					$the_title = get_the_title();
					$the_link = get_permalink(get_the_ID());
					
					$find = array("\"", "[", "]", "\n","\r");
					$replace  = array("'","","","","");
					
	
					$the_excerpt = str_replace( $find,$replace, gmshc_get_excerpt());					
					
					$item[$i] = array("address"=>$address,"img" => $the_image,"title" => $the_title,"link" => $the_link, "excerpt" => $the_excerpt);
					 
					$i ++;					
					
				}		

			endwhile; 
			
     		return $item;

}



/**
 * Get the thumbnail
 */
function gmshc_post_thumb($the_parent){

$attachments = get_children( array(
				'post_parent' => $the_parent, 
				'post_type' => 'attachment', 
				'post_mime_type' => 'image', 
				'order' => 'DESC', 
				'numberposts' => 1) );
				
				if($attachments == true) :
					foreach($attachments as $id => $attachment) :
						$img = wp_get_attachment_image_src($id, 'thumbnail');
					endforeach;		
				endif;
								
				return $img[0]; 

}


/**
 * Settings
 */  

add_action('admin_menu', 'gmshc_set');

function gmshc_set() {
    add_options_page('Google Map Shortcode', 'Google Map Shortcode', 10, 'google-map-shortcode', 'gmshc_options_page');	 
}

function gmshc_options_page() {

global $defaul_gmshc_windowhtml;

$options = get_option('gmshc_op');


	if(isset($_POST['Submit'])){

?>
    <script type="text/javascript">
      var KillAlerts = true;
      var realAlert = alert;
      var alert = new Function('a', 'if(!KillAlerts){realAlert(a)}');
    </script>

<script src="http://maps.google.com/maps?file=api&v=2.105&key=<?php echo $_POST['gmshc_key'] ?>" type="text/javascript"></script>


 <script type="text/javascript">
    KillAlerts = false;

    if (GBrowserIsCompatible()) {
		document.write("<div class='updated'><p><strong><?php _e('Options saved.', 'mt_trans_domain' ); ?></strong></p></div>");
		
		<?php 
		$newoptions['gmshc_key'] = $_POST['gmshc_key'];
		$newoptions['gmshc_windowhtml'] = $_POST['gmshc_windowhtml'];

		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('gmshc_op', $options);			
		}		
	    
		//Adding Meta Key
		$meta_key = get_post_meta(1, 'google-map-sc-address');
		
		if(empty($meta_key)) {

			add_post_meta(1, 'google-map-sc-address', '', true);
		} 		
		?>
		
    } else {
      if (G_INCOMPAT) {
		document.write("<div class='error'><p><strong><?php _e('Error: Bad Google API Key.', 'mt_trans_domain' ); ?></strong></p></div>");
		<?php 
		$newoptions['gmshc_key'] = "";
		?>
      } else {
        alert("Incompatible browser");
      }
    } 
</script>

<?php
		
	     
 } 

	if(isset($_POST['Use_Default'])){
	

	
		$newoptions['gmshc_key'] = $_POST['gmshc_key'];
		$newoptions['gmshc_windowhtml'] = $defaul_gmshc_windowhtml;


		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('gmshc_op', $options);			
		}
		
	
  } 

		$gmshc_key = $options['gmshc_key'];
		$gmshc_windowhtml = $options['gmshc_windowhtml'];		 
?>	 	         


<div class="wrap">   

<form method="post" name="options" target="_self">

<h2>Google Map Shortcode Settings</h2>

<h3>Enter your Google Map Api Key. You can get it <a href="http://code.google.com/apis/maps/signup.html" target="_blank">here</a></h3>

<p><input name="gmshc_key" type="text" value="<?php echo $gmshc_key ?>" size="105"/>

<p class="submit">
<input type="submit" name="Submit" value="Update" class="button-primary" />
</p>

<h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;">Open Window Html</h3>

<p>This is the html inside of the Map Info Window opened after clicking on the markers, you can include the following tags.</p>

<table width="80%%" border="0" cellspacing="10" cellpadding="0">
  <tr>
    <td width="150" align="right"><strong>%title%</strong></td>
    <td>The title of your post</td>
  </tr>
  <tr>
     <td align="right"><strong>%link%</strong></td>
    <td>The link to your post</td>
  </tr>
  <tr>
    <td align="right"><strong>%thubnail%</strong></td>
    <td>The thubnail of the last image attached to your post</td>
  </tr>
  <tr>
    <td align="right"><strong>%excerpt%</strong></td>
    <td>The excerpt of your post</td>
  </tr>  
  <tr>
    <td align="right"><strong>%address%</strong></td>
    <td>The address of this point in the map</td>
  </tr>
</table>
<br />


<textarea name="gmshc_windowhtml" cols="110" rows="12" id="gmshc_windowhtml">
<?php  
if  (empty($gmshc_windowhtml)) echo $defaul_gmshc_windowhtml; 
else echo str_replace("\\", "",$gmshc_windowhtml);

?>
</textarea>

<p align="right" style="width:800px; padding:0">
<input type="submit" name="Use_Default" value="Restore Default"/>
</p>

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
  <tr>
    <td><div align="right"><strong>external_links</strong></div></td>
    <td>Include the links to the map points outside of the map</td>
  </tr>  
        
</table>


<h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;">Feedback</h3>

<p>For more details and examples visite the <a href="http://web-argument.com/google-map-shortcode/">Plugin Page</a>. All the comments are welcome.</p>


<p class="submit">
<input type="submit" name="Submit" value="Update" class="button-primary" />
</p>
</form>
</div>

<?php } ?>
