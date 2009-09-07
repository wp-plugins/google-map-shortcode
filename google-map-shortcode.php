<?php
/*
Plugin Name: Google Map Shortcode
Plugin URI: http://web-argument.com/google-map-shortcode-version-11/
Description: Include Google Map in your blog displaying your post address using differents parameters. 
Version: 1.1
Author: Alain Gonzalez
Author URI: http://web-argument.com/
*/


// to find latitud longitud http://www.batchgeocode.com/lookup/


/**
 * Inserting files on the header
 */
function gmshc_head() {

     $options = get_option('gmshc_op');
	 $gmshc_key = $options['gmshc_key'];

    $wpchkt_header =  "\n<!-- Google Map Shortcode -->\n";		
    $wpchkt_header .= "<script src=\"http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=".$gmshc_key."\" type=\"text/javascript\"></script>\n"; 
	$wpchkt_header .= "<script type=\"text/javascript\" src=\"".get_bloginfo('url')."/wp-content/plugins/google-map-shortcode/google-map-sc.js\"></script>\n";	
	$wpchkt_header .= "<link href=\"".get_bloginfo('url')."/wp-content/plugins/google-map-shortcode/google-map-sc-style.css\" rel=\"stylesheet\" type=\"text/css\" />\n";
	$wpchkt_header .=  "\n<!-- Google Map Shortcode-->\n";	
            
	print($wpchkt_header);

}

add_action('wp_head', 'gmshc_head');
	

/**
 * Default Open Window Html
 */

$defaul_gmshc_windowhtml  = "<div class='gm_info_cont'>\n";
$defaul_gmshc_windowhtml .= "<p class='gm_info_title'><a class='title' href='%link%'>%title%</a></p>\n";
$defaul_gmshc_windowhtml .= "<p class='gm_info_address'>%address%</p>\n";
$defaul_gmshc_windowhtml .= "<table border='0' cellspacing='0' cellpadding='5' height='80'>\n";
$defaul_gmshc_windowhtml .= "<tr>\n";
$defaul_gmshc_windowhtml .= "<td valign='top' align='left'><a href='%link%'><img src='%thubnail%' class='gm_info_img' /></a></td>\n";
$defaul_gmshc_windowhtml .= "<td valign='top' align='left'>\n";
$defaul_gmshc_windowhtml .= "%excerpt%\n";
$defaul_gmshc_windowhtml .= "<p><a href='%link%'>more &raquo;</a></p>\n";
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

	$address_meta = get_post_meta(get_the_ID(), 'google-map-sc-address');
	$point_meta = get_post_meta(get_the_ID(), 'google-map-sc-latlng');			
	
	$the_address = $address_meta[0];
	$point = $point_meta[0];
	$the_item = '';  
	
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


 if (!empty($address)) {
 
	 $the_item = gmshc_fill_items('',$address, '');
	 if (!empty($the_item)) $gm_item[0] = $the_item;


 }
 
 else if ( 
      
	  (
	  	
		(!empty($id))  ||   (!empty($point)) || (!empty($the_address))
		
	  )
		
		& (empty($cat)) 
		& (empty($number)) 
		& (!($external_links))	  
	  
	  ) 
	
	{
	
	if (empty($id)) $id = $post->ID;

		$gm_item = gmshc_items($id,'', '');
	
}
	
else {


		$gm_item = gmshc_items('',$number, $cat);


}

	$canvas = "canvas_".wp_generate_password(4, false);

	
	$i = 0;

	
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
			
			$find = array("\r","\n","\\","\"","%title%","%link%","%thubnail%", "%excerpt%","%address%");
			$replace  = array("","","","'",$item['title'],$item['link'],$item['img'],$item['excerpt'],$item['address']);
			
			$info = str_replace( $find,$replace, $gmshc_windowhtml);
			
			
			list($lat, $long) = split(",",$item['point']);

		
		if ( (empty($item['title'])) & (empty($item['link'])) & (empty($item['img'])) & (empty($item['excerpt'])) & (empty($item['address']))  )
		
		$output .= "map_points_".$canvas."[".$i."] = {'point':{'lat':\"".$lat."\",'long':\"".$long."\"}};\n";
		
		else 	
		
		$output .= "map_points_".$canvas."[".$i."] = {'point':{'lat':\"".$lat."\",'long':\"".$long."\"},'info':\"".$info."\"};\n";		

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
		$output .= "</ul>";
		$output .= "</div";		
	
	}		
		
	}
	

	
return $output;

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



/**
 * Get the items
 */


function gmshc_items($id,$number, $categories){

		$i = 0;
		$the_item = '';	
		
		//to avoid exceed the google request limit
		if (empty($number)) $number = 100;	
		
		if (!empty($id)) {
		$my_query = get_posts(array('include'=>$id));	
		$number = 1;
		}
		
		

		else if (empty($categories)){
		
			$my_query = get_posts(array('numberposts'=>-1));		
		}
		
		else {
		
			$cat = split (",",$categories); 

			$my_query = get_posts(array('category__in'=>$cat,'numberposts'=>-1));		
		
		}	
				
	foreach ($my_query  as $post) {
        
		if ($i == $number) break; 
	
		$address_meta = get_post_meta($post -> ID, 'google-map-sc-address');
		$point_meta = get_post_meta($post -> ID, 'google-map-sc-latlng');			
			

		if ($number > 1){
		
			$address = $address_meta[0];
			$point = $point_meta[0];
			
			$the_item = gmshc_fill_items($post,$address,$point);
			
			if (!empty($the_item)) {
				$item[$i] = $the_item;
				$i ++;
			}

		
		} else {
		
			if (count($address_meta) > 1) {
			
				$j = 0;
			
				foreach ($address_meta as $address) {
			
					$the_item = gmshc_fill_items('',$address,'');
					
					if (!empty($the_item)) {
						$item[$j] = $the_item;
						$j ++;
					}
			    }
				 $i ++;
			
			
			} else if (count($point_meta) > 1) {
			
				$j = 0;
			
				foreach ($point_meta as $point) {
				
					$the_item = gmshc_fill_items('','',$point);
			
					if (!empty($the_item)) {
						$item[$j] = $the_item;
						$j ++;
					}
			     
				}
				 $i ++;
			
			
			} else {
			
				$address = $address_meta[0];
				$point = $point_meta[0];
				
				$the_item = gmshc_fill_items($post,$address,$point);
				if (!empty($the_item)) {
					$item[0] = $the_item;
				}	
			
			}
		
		}
	    

}

     		return $item;

}



function gmshc_fill_items($post,$address, $point){

		if ((empty($point))	& (!empty($address))){
				
				$response = gmshc_point($address);
				
				$point = $response['point'];
				$address = $response['address'];
		
				if (!empty($point)) {		
                    if (!empty($post)){
						update_post_meta($post -> ID, 'google-map-sc-latlng', $point);
						update_post_meta($post -> ID, 'google-map-sc-address', $address);
                    }
				}						
			
		}
		
		if (!empty($point))	{
							
			if (!empty($post)){
				$the_image = gmshc_post_thumb($post -> ID);
				$the_title = $post -> post_title;
				$the_link = $post -> guid;
				
				$find = array("\"", "[", "]", "\n","\r");
				$replace  = array("'","","","","");
				
	
				$the_excerpt = str_replace( $find,$replace, gmshc_get_excerpt($post -> post_content));					
				
				$item = array("point"=>$point,"img" => $the_image,"title" => $the_title,"link" => $the_link, "excerpt" => $the_excerpt, "address" => $address);

			
			} else {
			
				$item = array("point"=>$point);
			
			}
			
			return $item;
		
		}
	
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
		$meta_key2 = get_post_meta(1, 'google-map-sc-latlng');
		
		if(empty($meta_key) || (empty($meta_key2))) {

			add_post_meta(1, 'google-map-sc-address', '');
			add_post_meta(1, 'google-map-sc-latlng', '');
			
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

<?php } 


function gmshc_point ($address){


    $find = array("\n","\r"," ");
	$replace = array("","","+");
					
    $address = str_replace( $find,$replace, $address);

	$url = 'http://maps.google.com/maps/geo?q='.$address.'&key='.$gmshc_key.'&sensor=false&output=xml&oe=utf8';
	
		
	$response = gmshc_xml2array($url);
	
	$coordinates = $response['kml']['Response']['Placemark']['Point']['coordinates'];
	$address = $response['kml']['Response']['Placemark']['address'];
	
	if (!empty($coordinates)) {
	
	$point_array = split(",",$coordinates);
	
	$point = $point_array[1].",".$point_array[0];
	
	$response = array('point'=>$point,'address'=>$address);
	
	return  $response;
	
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
