<?php
/*
Plugin Name: Google Map Shortcode
Plugin URI: http://web-argument.com/google-map-shortcode-2-0-total-solution/
Description: Include Google Map in your blogs with just one click. 
Version: 2.2.1
Author: Alain Gonzalez
Author URI: http://web-argument.com/
*/

define('GMSC_PLUGIN_DIR', WP_PLUGIN_DIR."/".dirname(plugin_basename(__FILE__)));
define('GMSC_PLUGIN_URL', WP_PLUGIN_URL."/".dirname(plugin_basename(__FILE__)));
define('GMSHC_VERSION_CURRENT','2.2');
define('GMSHC_VERSION_CHECK','2.2');

require(GMSC_PLUGIN_DIR."/include/functions.php");
require(GMSC_PLUGIN_DIR."/include/class.gmshc_point.php");
require(GMSC_PLUGIN_DIR."/include/class.gmshc_post_points.php");

/**
 * Default Options
 */
function get_gmshc_options ($default = false){


	$gmshc_default = array(
							'zoom' => '10',
							'width' => '450',
							'height' => '450',
							'margin' => '10',
							'align' => 'center',									
							'language' => 'en',
							'windowhtml' => gmshc_defaul_windowhtml(),
							'icons' => array(),
							'default_icon' => GMSC_PLUGIN_URL.'/images/icons/marker.png',
							'interval' => 5000,
							'focus' => '0',
							'type' => 'ROADMAP',
							'animate' => true,
 							'focus_type' => 'open',
							'version' => GMSHC_VERSION_CURRENT
							);
							
    	
	if ($default) {
	update_option('gmshc_op', $gmshc_default);
	return $gmshc_default;
	}
	
	$options = get_option('gmshc_op');
	if (isset($options)){
	    if (isset($options['version'])) {
			$chk_version = version_compare(GMSHC_VERSION_CHECK,$options['version']);
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
	
	$gmshc_header =  "\n<!-- Google Map Shortcode Version ".GMSHC_VERSION_CHECK."-->\n";		
	$gmshc_header .= "<script src=\"http://maps.google.com/maps/api/js?sensor=false";
	if(isset($language)) 
	$gmshc_header .= "&language=".$language;
	$gmshc_header .="\" type=\"text/javascript\"></script>\n";	
	$gmshc_header .= "<script type=\"text/javascript\" src=\"".GMSC_PLUGIN_URL."/js/gmshc.2.2.js\"></script>\n";	
	$gmshc_header .=  "\n<!-- /Google Map Shortcode Version ".$options['version']."-->\n";		
		
	print($gmshc_header);

}

add_action('wp_head', 'gmshc_head');

/**
 * Default Open Window Html
 *
 * Allows a plugin to replace the html that would otherwise be returned. The
 * filter is 'gmshc_get_windowhtml' and passes the point.

 * add_filter('gmshc_defaul_windowhtml','default_html',1,2);
 * 
 * function default_html($windowhtml,$point){
 * 	return "this is the address".$point->address;
 * } 
 */
 function gmshc_defaul_windowhtml(){
    
	$defaul_gmshc_windowhtml = "";
	$output = apply_filters('gmshc_defaul_windowhtml',$defaul_gmshc_windowhtml);

	if ( $output != '' )
		return $output;		 
 
	$defaul_gmshc_windowhtml = "<div style='margin:0; padding:0px; height:125px; width:%width%; overflow:hidden; font-size:11px; clear:both; line-height:13px;'>\n";
	$defaul_gmshc_windowhtml .= "<div style='float:left; width:200px'>\n";
	$defaul_gmshc_windowhtml .= "<a class='title' href='%link%' style='clear:both; display:block; font-size:12px; line-height: 18px; font-weight:bold;'>%title%</a>\n";
	$defaul_gmshc_windowhtml .= "<div><strong style='font-size:9px'>%address%</strong></div>\n";
	$defaul_gmshc_windowhtml .= "<div style='font-size:10px'>%description%</div>\n";
	$defaul_gmshc_windowhtml .= "<a href='%link%' style='font-size:11px; float:left; display:block'>more &raquo;</a>\n";
	$defaul_gmshc_windowhtml .= "<img src='".GMSC_PLUGIN_URL."/images/open.jpg' style='float: right; margin-right:5px'/> \n";
	$defaul_gmshc_windowhtml .= "<a href='%open_map%' target='_blank' style='font-size:11px; float: right; display:block;'>Open Map</a>\n";
	$defaul_gmshc_windowhtml .= "</div>\n";
	$defaul_gmshc_windowhtml .= "<div style='float:left'><a title='%link%' href='%link%'>%thubnail%</div></a>\n";	
	$defaul_gmshc_windowhtml .= "</div>\n";
	
	return $defaul_gmshc_windowhtml;

}

/**
  * The Sortcode
  *
*/  
add_shortcode('google-map-sc', 'gmshc_sc');

function gmshc_sc($atts) {
	
	global $post;
	$options = get_gmshc_options();	
	
	$width = $options['width'];
	$height = $options['height'];
	$margin = $options['margin'];
	$align = $options['align'];
	 
	$zoom = $options['zoom']; 
	$icon = $options['default_icon'];
	$language = $options['language'];
	$type = $options['type'];
	$interval = $options['interval'];
	$focus = $options['focus'];
	$animate = $options['animate'];	
	$focus_type = $options['focus_type'];		

	// First Point in the post
	$address_meta = get_post_meta($post -> ID, 'google-map-sc-address');
	$point_meta = get_post_meta($post -> ID, 'google-map-sc-latlng');			
	
	$the_address = isset($address_meta[0]) ? $address_meta[0] : '';
	$point = isset($point_meta[0]) ? $point_meta[0] : '';
	$the_items = array();	
	
	$final_atts = shortcode_atts(array(
										'address' => '',
										'title' =>'',
										'description' => '',
										'icon' => $icon,
										'thumbnail' => '',	
										'id' => '',
										'cat' => '',
										'zoom' => $zoom,
										'width' => $width,
										'height' => $height,
										'margin' => $margin,
										'align' => $align,		
										'language' => $language,
										'type' => $type,
										'interval' => $interval,
										'focus' => $focus,
										'animate' => $animate,
										'focus_type' => $focus_type, 
										'canvas' => ''	
										), $atts);	
	extract($final_atts);	

	$map_points = array();

	// When address is set
	 if (!empty($address)) {
			 
		//create single point object id = -1
		$new_point = new GMSHC_Point();	
		if($new_point -> create_point($address,"",$title,$description,$icon,$thumbnail,-1,true)) $map_points[0]=$new_point;	
	
	 // When id is set
	 } else if (!empty($id)) {
		$post_points = new GMSHC_Post_Map();
		$post_points -> create_post_map($id);
		if ($post_points->points_number > 0) $map_points = $post_points->points;	
		 
	} else if ($cat != '') {
	
		$categories = split (",",$cat); 
		$j = 0;
		
		$post_obj = get_posts(array('category__in'=>$categories,'numberposts'=>-1));
		foreach ($post_obj as $post_item) {	
		  //create points object by cat
		  $post_points = new GMSHC_Post_Map();

		  $post_points -> create_post_map($post_item->ID);
		  if ($post_points->points_number >0) {
			foreach ($post_points->points as $point) { 			  
			  array_push($map_points,$point);  
			}
		  }
		}			
	
	 }  else {
	
		//create points for the current post_id	
		$post_points = new GMSHC_Post_Map();
		$post_points -> create_post_map($post->ID);	
		$map_points = $post_points->points;	 
	
	}

	//Map Point array filled		
	if ( count($map_points) > 0 ) {

		//Generate Map form points   
		$output = gmshc_generate_map($map_points, $final_atts);					
    } else { 
		$output = __("There is not points to locate on the map");
	}

	return $output;	
}


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
	$custom_fieds = get_post_custom($post_id);
	
	$address = isset($_REQUEST['new_address'])? stripslashes($_REQUEST['new_address']) : "";
	$ltlg = isset($_REQUEST['new_ltlg'])?$_REQUEST['new_ltlg'] : "";
	$title = isset($_REQUEST['new_title'])? stripslashes($_REQUEST['new_title']) : get_the_title($post_id);
	$description = isset($_REQUEST['new_description'])? stripslashes($_REQUEST['new_description']) : "";
	$icon = isset($_REQUEST['default_icon'])?stripslashes($_REQUEST['default_icon']) : "";
	$selected_thumbnail = isset($_REQUEST['selected_thumbnail'])? stripslashes($_REQUEST['selected_thumbnail']) : "";

	$add_point = isset($_REQUEST['add_point']) ? $_REQUEST['add_point'] : '';
	$del_point = isset($_REQUEST['delp']) ? $_REQUEST['delp'] : '';
	$update_point = isset($_REQUEST['update']) ? $_REQUEST['update'] : '';
	
	$width = isset($_REQUEST['width']) ? $_REQUEST['width'] : $options['width'];
	$height = isset($_REQUEST['height']) ? $_REQUEST['height'] : $options['height'];
	$margin = isset($_REQUEST['margin']) ? $_REQUEST['margin'] : $options['margin'];
	$align = isset($_REQUEST['align']) ? $_REQUEST['align'] : $options['align'];
	
	$zoom = isset($_REQUEST['zoom']) ? $_REQUEST['zoom'] : $options['zoom'];	
	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : $options['type'];
	
	$focus = isset($_REQUEST['focus']) ? $_REQUEST['focus'] : $options['focus'];
	$focus_type = isset($_REQUEST['focus_type']) ? $_REQUEST['focus_type'] : $options['focus_type'];	
	
	$address_list = isset($_REQUEST['addr']) ? gmshc_stripslashes_deep($_REQUEST['addr']) : "";
	$title_list = isset($_REQUEST['title']) ? gmshc_stripslashes_deep($_REQUEST['title']) : "";	
	$desc_list = isset($_REQUEST['desc']) ? gmshc_stripslashes_deep($_REQUEST['desc']) : "";	
	$ltlg_list = isset($_REQUEST['ltlg']) ? $_REQUEST['ltlg'] : "";	
	$icon_list = isset($_REQUEST['icon']) ? gmshc_stripslashes_deep($_REQUEST['icon']) : "";	
	$thumb_list = isset($_REQUEST['thumb'])? gmshc_stripslashes_deep($_REQUEST['thumb']) : "";
	
    $post_points = new GMSHC_Post_Map();
	$post_points -> create_post_map($post_id);

	if (!empty($add_point)) {	        		
			$new_point = new GMSHC_Point();
	        if($new_point -> create_point($address,"",$title,$description,$icon,$selected_thumbnail,$post_id,true)){
				$post_points -> add_point($new_point);
			}
			else 
				echo "<div class='error'><p>".__("The Address can't be located.")."</p></div>";
	}

	else if (!empty($update_point)) {
	
		if ( $post_points -> update_points($address_list,$ltlg_list,$title_list,$desc_list,$icon_list,$thumb_list))
			echo "<div class='updated'><p>".__("The Point was updated.")."</p></div>";
	    else echo "<div class='error'><p>".__("The Points can't be updated.")."</p></div>";
	}
	
	else if ($del_point != "") {
		if($post_points -> delete_point($del_point))		
		echo "<div class='updated'><p>".__("The Point was deleted.")."</p></div>";
	}	

	?>
    
    <script type="text/javascript" src="<?php echo GMSC_PLUGIN_URL ?>/js/gmshc-admin.js"></script>
    <?php gmshc_head() ?>
    <link href="<?php echo GMSC_PLUGIN_URL ?>/styles/gmshc-admin-styles.css" rel="stylesheet" type="text/css"/>
    
        <div style="width:620px; margin:10px auto">
        
        <?php echo gmshc_plugin_menu(); ?>
    
        <form  action="#" method="post">
           <input id="default_width" type="hidden" value="<?php echo $width ?>"/>
           <input id="default_height" type="hidden" value="<?php echo $height ?>"/>
           <input id="default_margin" type="hidden" value="<?php echo $margin ?>"/>
           <input id="default_align" type="hidden" value="<?php echo $align ?>"/>
           <input id="default_zoom" type="hidden" value="<?php echo $zoom ?>"/>
           <input id="default_focus" type="hidden" value="<?php echo $focus ?>"/>
           <input id="default_focus_type" type="hidden" value="<?php echo $focus_type ?>"/>           
           <input id="default_type" type="hidden" value="<?php echo $type ?>"/>
           
           <table width="620" border="0" cellspacing="5" cellpadding="5">
            <tr>
                <td colspan="2">
                <h3 class="gmshc_editor"><?php _e("Add New Point"); ?></h3>
                </td>
           </tr>  
            <tr>
                <td align="right" valign="top">
                <strong><?php _e("Title"); ?></strong>
                </td>
				<td valign="top">    
				<input name="new_title"  size="55" id="new_title" value="<?php echo $title ?>" />
				</td>
            </tr> 
            <tr>
                <td align="right" valign="top">
                <strong><?php _e("Description"); ?></strong>
                </td>
				<td valign="top">    
				<textarea name="new_description" cols="50" rows="2" id="new_description"></textarea>
				</td>
            </tr> 			
            <tr>
                <td align="right" valign="top">
                <strong><?php _e("Full Address"); ?></strong>
                </td>
				<td valign="top">    
				<textarea name="new_address" cols="50" rows="2" id="new_address"></textarea>
				</td>
            </tr> 
            <tr>
				<td align="right" valign="top" colspan="2">
					<?php gmshc_deploy_icons() ?>
				</td>
            </tr> 
            <tr>
				<td align="center" valign="top" colspan="2">
                	<?php 
					$thumbnail_list = gmshc_all_post_thumb($post_id);
					if (count($thumbnail_list) > 0) { 
					?>
                        <div class="gmshc_label">
                            <?php _e("Select the thumbnail by clicking on the images"); ?>
                        </div>
                        <div id="gmshc_thumb_cont">
                        <input type="hidden" name="selected_thumbnail" value="<?php echo $default_icon ?>" id="selected_thumbnail" />
                        <?php foreach ($thumbnail_list as $thumbnail) { ?>
                            <div class="gmshc_thumb">
                                <img src="<?php echo $thumbnail ?>" width="40" height="40" />
                            </div>
                        <?php  } ?>
                        </div>
					<?php } else { ?>
                        <div class="gmshc_label">
                            <strong><?php _e("Thumbnail: "); ?></strong><?php _e("If you want to attach an image to the point you need to upload it first to the post gallery"); ?>
                        </div> 
                    <?php  } ?> 
                    <p align="left"><a class="button" href = "?post_id=<?php echo $post_id ?>&type=image" title="Upload Images"><?php _e("Upload Images") ?></a></p>                    <p align="left"><input class="button-primary" value="<?php _e("Add Point") ?>" name="add_point" type="submit"></p> 
				</td>
            </tr>
            <?php if ($post_points->points_number > 0) { ?>            
            <tr>
                <td colspan="2">
                <h3 class="gmshc_editor"><?php _e("Map Configuration"); ?></h3>
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
              <td align="right"><strong><?php _e("Margin") ?></strong></td>
              <td><input name="margin" id="margin" type="text" size="6" value="<?php echo $margin ?>" /></td>
            </tr>  
            <tr>
              <td align="right"><strong><?php _e("Align") ?></strong></td>
              <td>
                  <input name="align" type="radio" id="aleft" value="left" <?php echo ($align == "left" ? "checked = 'checked'" : "") ?> /> <?php _e("left") ?>
                  <input name="align" type="radio" id="acenter" value="center" <?php echo ($align == "center" ? "checked = 'checked'" : "") ?> /> <?php _e("center") ?>
                  <input name="align" type="radio" id="aright" value="right" <?php echo ($align == "right" ? "checked = 'checked'" : "") ?> /> <?php _e("right") ?>        
            </tr>            
            <tr>
              <td align="right"><?php _e("Zoom") ?></td>
              <td>              
              <select name="zoom" id="zoom">              
                  <?php for ($i = 0; $i <= 20; $i ++){ ?>
                      <option value="<?php echo $i ?>" <?php echo ($i == $zoom ? "selected" : "") ?> ><?php echo $i ?></option>
                  <?php } ?>
              </select>         
            </tr> 
            <tr>
              <td align="right"><?php _e("Maps Type") ?></td>
              <td>
                  <select name="type" id="type">
                      <option value="ROADMAP" <?php if ($type == "ROADMAP") echo "selected" ?>><?php _e("ROADMAP - Displays a normal street map") ?></option>
                      <option value="SATELLITE" <?php if ($type == "SATELLITE") echo "selected" ?>><?php _e("SATELLITE - Displays satellite images") ?></option>
                      <option value="TERRAIN" <?php if ($type == "TERRAIN") echo "selected" ?>><?php _e("TERRAIN - Displays maps with physical features such as terrain and vegetation") ?></option>
                      <option value="HYBRID" <?php if ($type == "HYBRID") echo "selected" ?>><?php _e("HYBRID - Displays a transparent layer of major streets on satellite images") ?></option>
                  </select>
              </td>        
            </tr>  
            <tr>
              <td align="right"><?php _e("Focus") ?></td>
              <td>
                  <select name="focus" id="focus">
                      <option value="0" <?php if ($focus == 0) echo "selected" ?>><?php _e("None") ?></option>
                      <?php if ($post_points->points_number > 1) { ?>
                      <option value="all" <?php if ($focus == "all") echo "selected" ?>><?php _e("All") ?></option>
                      <?php } ?>
                      <?php for ($i = 1; $i <= $post_points->points_number; $i ++){ ?>
                      	<?php $number = $i; ?>
                      	<option value="<?php echo $number ?>" <?php if ($focus == $number) echo "selected" ?>><?php echo $number ?></option>
                      <?php } ?>
                  </select>
                  <em><?php _e("Select the point to be focused after loading the map") ?></em>
              </td>        
            </tr> 
            <tr>
              <td align="right" valign="top"><?php _e("Focus Type") ?></td>
              <td>
              <select name="focus_type" id="focus_type">
                 <option value="open" <?php echo ($focus_type == "open" ? "selected" : "") ?> ><?php _e("Open Markers") ?></option>
                 <option value="center" <?php echo ($focus_type == "center" ? "selected" : "") ?> ><?php _e("Center Markers") ?></option>
              </select>
              </td>        
            </tr>                                   
            <?php } ?>			
            </table>
            
        	<p>
            	<?php if ($post_points->points_number > 0) { ?>
                	<input class="button-primary" value="<?php _e("Add Point") ?>" name="add_point" type="submit">                
					<input class="button-primary insert_map" value="<?php _e('Insert Map'); ?>" type="button" \>
					<?php echo $post_points->points_number.__(" Points Added") ?>                    
                <?php } ?>
                
            </p>
            
			<?php
            if ( count($post_points -> points) > 0 ){
            ?>
     
            <table class="widefat" cellspacing="0">
                <thead>
                <tr>
                <th><?php _e("Marker"); ?></th>
                <th><?php _e("Thumbnail"); ?></th>
                <th><?php _e("Title/Description"); ?></th>
                <th width="140"><?php _e("Address/LtLg"); ?></th>
                </tr>
                </thead>
                <tbody class="media-item-info">   
                
                <?php 
				$i = 0;
				foreach ($post_points->points as $point ) {				 
				?>                     
                    <tr>
                      <td>
                      	<img src="<?php echo $point->icon ?>" atl="<?php _e("Icon") ?>" />
                        <input name="icon[]" type="hidden" id="icon_<?php echo $i ?>" size="30" value = "<?php echo $point->icon ?>"/>
                      </td>                    
                      <td>
                      	<div class="gmshc_thumb gmshc_selected">
                        <?php if ($point->thumbnail != "") { ?>						       
                      	<img src="<?php echo $point->thumbnail ?>" atl="<?php _e("Thumbnail") ?>" width = "40" height="40" />
                        <input name="thumb[]" type="hidden" id="thumb_<?php echo $i ?>" size="30" value = "<?php echo $point->thumbnail ?>"/> 
                         <?php } ?>
                         </div>                  
                      </td>
                      <td>
						<input name="title[]" type="text" id="title_<?php echo $i ?>" size="40" value = "<?php echo $point->title ?>"/>
                        <textarea name="desc[]" cols="40" rows="2" id="desc_<?php echo $i ?>"><?php echo $point->description ?></textarea>							
                      </td>
                      <td>
						<input name="ltlg[]" type="hidden" id="ltlg_<?php echo $i ?>" size="30" value = "<?php echo $point->ltlg ?>"/>
                        <textarea name="addr[]" cols="30" rows="2" id="addr_<?php echo $i ?>" style="display:none"><?php echo $point->address ?></textarea>
                        <p><?php echo $point->address ?></p>	
                        <div>
                        <input class="button" value="<?php _e("Update"); ?>" name="update" type="submit"> 
                        <a href="?post_id=<?php echo $post_id ?>&tab=gmshc&delp=<?php echo $i ?>" class="delete_point" onclick="if(confirm('<?php _e("You will not be able to roll back deletion. Are you sure?") ?>')) return true; else return false"><?php _e("Delete"); ?></a>
                        </div>
                      </td>
                    </tr>
                 <?php 
				 	$i++;
				}
				?>          
                </tbody> 	    
            </table>
        
   	    <p><input class="button-primary insert_map" value="<?php _e("Insert Map"); ?>" type="button" \> <a class="button" href="#gmshc_map" id="gmshc_show" show="<?php _e("Show Map") ?>" hide="<?php _e("Hide Map") ?>"><?php _e("Show Map") ?></a></p>        
        </div>
        <div id="gmshc_map" style="height:1px; overflow:hidden;">
        <?php echo do_shortcode("[google-map-sc id=".$post_id." width=600 height=420 type=".$type." focus=".$focus."]"); ?>
        </div>
        <br />        
			<?php  } ?>
		</form>

   
<?php
}

/**
 * Settings
 */  

add_action('admin_menu', 'gmshc_set');

function gmshc_set() {
		$plugin_page = add_options_page('Google Map Shortcode', 'Google Map Shortcode', 'administrator', 'google-map-shortcode', 'gmshc_options_page');	 
		add_action( 'admin_head-'.$plugin_page, 'gmshc_admin_script' );	
	 }

/**
 * Inserting files on the admin header
 */
function gmshc_admin_script() {

	$gmshc_admin_header =  "\n<!-- Google Map Shortcode -->\n";		
	$gmshc_admin_header .= "<script type=\"text/javascript\" src=\"".GMSC_PLUGIN_URL."/js/gmshc-admin.js\"></script>\n";
	$gmshc_admin_header .= "<link href=\"".GMSC_PLUGIN_URL."/styles/gmshc-admin-styles.css\" rel=\"stylesheet\" type=\"text/css\"/>\n";
	$gmshc_admin_header .= "\n<!-- /Google Map Shortcode -->\n";		
		
	print($gmshc_admin_header);

}

function gmshc_options_page() {

	$options = get_gmshc_options();
	
    if(isset($_POST['Restore_Default']))	$options = get_gmshc_options(true);	?>

	<div class="wrap">   
	
	<h2><?php _e("Google Map Shortcode Settings") ?></h2>

	<?php echo gmshc_plugin_menu(); ?>
	
	<?php 

	if(isset($_POST['Submit'])){
	
     		$newoptions['width'] = isset($_POST['width'])?$_POST['width']:$options['width'];
			$newoptions['height'] = isset($_POST['height'])?$_POST['height']:$options['height'];
			$newoptions['margin'] = isset($_POST['margin'])?$_POST['margin']:$options['margin'];
			$newoptions['align'] = isset($_POST['align'])?$_POST['align']:$options['align'];
			
			$newoptions['zoom'] = isset($_POST['zoom'])?$_POST['zoom']:$options['zoom'];
			$newoptions['language'] = isset($_POST['language'])?$_POST['language']:$options['language'];
			$newoptions['type'] = isset($_POST['type'])?$_POST['type']:$options['type'];
			$newoptions['interval'] = isset($_POST['interval'])?$_POST['interval']:$options['interval'];
			$newoptions['focus'] = isset($_POST['focus'])?$_POST['focus']:"0";
			$newoptions['animate'] = isset($_POST['animate'])?$_POST['animate']:false;
			$newoptions['focus_type'] = isset($_POST['focus_type'])?$_POST['focus_type']:"open";
		
			$newoptions['windowhtml'] = isset($_POST['windowhtml'])? $_POST['windowhtml']:$options['windowhtml'];	

			$newoptions['default_icon'] = isset($_POST['default_icon'])?$_POST['default_icon']:$options['default_icon'];
			$newoptions['icons'] = $options['icons'];	
					
			$newoptions['version'] = $options['version'];

			if ( $options != $newoptions ) {
				$options = $newoptions;
				update_option('gmshc_op', $options);			
			}			
	    
 	} 

	if(isset($_POST['Use_Default'])){

		$options['windowhtml'] = gmshc_defaul_windowhtml();
        update_option('gmshc_op', $options);
	
    }

	$upload_icons = $options['icons'];

	if(isset($_POST['upload'])) {
		if ($_FILES['datafile']['error'] == 0){

		   $filename = $_FILES["datafile"]["name"];
	 
		   $upload = wp_upload_bits($filename, NULL, file_get_contents($_FILES["datafile"]["tmp_name"]));

			if ( ! empty($upload['error']) ) {
				$errorString = sprintf(__('Could not write file %1$s (%2$s)'), $filename, $upload['error']);
				echo "<div class='error'><p><strong>".$errorString."</strong></p></div>";
			}  else {		
				array_unshift($upload_icons,$upload['url']);
				$options['icons'] = $upload_icons;
				update_option('gmshc_op', $options);		
			}
		
		} else {
			echo "<div class='error'><p><strong>".__("Please upload a valid file")."</strong></p></div>";
		}
	}

	$width = $options['width'];
	$height = $options['height'];
	$margin = $options['margin'];
	$align = $options['align'];
	
	$zoom = $options['zoom'];
	$language = $options['language'];
	$type = $options['type'];
	
	$interval = $options['interval'];
	$focus = $options['focus'];
	$animate = $options['animate'];
	$focus_type = $options['focus_type'];

	$windowhtml = $options['windowhtml'];	
	$default_icon = $options['default_icon'];

	?>  
	
	<form method="POST" name="options" target="_self" enctype="multipart/form-data">
	
	<h3><?php _e("Maps Default Configuration") ?></h3>
	
	<p><?php _e("The shortcode attributes overwrites these options.") ?></p>
	
    <table width="80%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td width="200" align="right" height="40"><strong><?php _e("Width") ?></strong></td>
        <td><input name="width" type="text" size="6" value="<?php echo $width ?>" /></td>
      </tr>
      <tr>
        <td align="right"><strong><?php _e("Height") ?></strong></td>
        <td><input name="height" type="text" size="6" value="<?php echo $height ?>" /></td>
      </tr>
      <tr>
        <td align="right"><strong><?php _e("Margin") ?></strong></td>
        <td><input name="margin" type="text" size="6" value="<?php echo $margin ?>" /></td>
      </tr>  
      <tr>
        <td align="right"><strong><?php _e("Align") ?></strong></td>
        <td>
        	<input name="align" type="radio" value="left" <?php echo ($align == "left" ? "checked = 'checked'" : "") ?> /> <?php _e("left") ?>
            <input name="align" type="radio" value="center" <?php echo ($align == "center" ? "checked = 'checked'" : "") ?> /> <?php _e("center") ?>
            <input name="align" type="radio" value="right" <?php echo ($align == "right" ? "checked = 'checked'" : "") ?> /> <?php _e("right") ?>        
      </tr>          
      <tr>
        <td align="right"><strong><?php _e("Zoom") ?></strong></td>
        <td>
        <select name="zoom" id="zoom">
            <?php for ($i = 0; $i <= 20; $i ++){ ?>
                <option value="<?php echo $i ?>" <?php echo ($i == $zoom ? "selected" : "") ?> ><?php echo $i ?></option>
            <?php } ?>
        </select>         
      </tr>  
      <tr>
        <td align="right"><strong><?php _e("Maps Default Type") ?></strong></td>
        <td>
        	<select name="type">
        		<option value="ROADMAP" <?php if ($type == "ROADMAP") echo "selected" ?>><?php _e("ROADMAP - Displays a normal street map") ?></option>
            	<option value="SATELLITE" <?php if ($type == "SATELLITE") echo "selected" ?>><?php _e("SATELLITE - Displays satellite images") ?></option>
                <option value="TERRAIN" <?php if ($type == "TERRAIN") echo "selected" ?>><?php _e("TERRAIN - Displays maps with physical features such as terrain and vegetation") ?></option>
                <option value="HYBRID" <?php if ($type == "HYBRID") echo "selected" ?>><?php _e("HYBRID - Displays a transparent layer of major streets on satellite images") ?></option>
            </select>
        </td>        
      </tr>      
      <tr>
        <td align="right" valign="top"><strong><?php _e("Select Language") ?></strong></td>
        <td>  
        <?php 
        $lang_array = array(
							"ar"=>__("ARABIC"),
							"eu"=>__("BASQUE"),
							"bg"=>__("BULGARIAN"),
							"bn"=>__("BENGALI"),
							"ca"=>__("CATALAN"),
							"cs"=>__("CZECH"),
							"da"=>__("DANISH"),
							"de"=>__("GERMAN"),
							"el"=>__("GREEK"),
							"en"=>__("ENGLISH"),
							"en-AU"=>__("ENGLISH (AUSTRALIAN)"),
							"en-GB"=>__("ENGLISH (GREAT BRITAIN)"),
							"es"=>__("SPANISH"),
							"eu"=>__("BASQUE"),
							"fa"=>__("FARSI"),
							"fi"=>__("FINNISH"),
							"fil"=>__("FILIPINO"),
							"fr"=>__("FRENCH"),
							"gl"=>__("GALICIAN"),
							"gu"=>__("GUJARATI"),
							"hi"=>__("HINDI"),
							"hr"=>__("CROATIAN"),
							"hu"=>__("HUNGARIAN"),
							"id"=>__("INDONESIAN"),
							"it"=>__("ITALIAN"),
							"iw"=>__("HEBREW"),
							"ja"=>__("JAPANESE"),
							"kn"=>__("KANNADA"),
							"ko"=>__("KOREAN"),
							"lt"=>__("LITHUANIAN"),
							"lv"=>__("LATVIAN"),
							"ml"=>__("MALAYALAM"),
							"mr"=>__("MARATHI"),
							"nl"=>__("DUTCH"),
							"no"=>__("NORWEGIAN"),
							"or"=>__("ORIYA"),
							"pl"=>__("POLISH"),
							"pt"=>__("PORTUGUESE"),
							"pt-BR"=>__("PORTUGUESE (BRAZIL)"),
							"pt-PT"=>__("PORTUGUESE (PORTUGAL)"),
							"ro"=>__("ROMANIAN"),
							"ru"=>__("RUSSIAN"),
							"sk"=>__("SLOVAK"),
							"sl"=>__("SLOVENIAN"),
							"sr"=>__("SERBIAN"),
							"sv"=>__("SWEDISH"),
							"tl"=>__("TAGALOG"),
							"ta"=>__("TAMIL"),
							"te"=>__("TELUGU"),
							"th"=>__("THAI"),
							"tr"=>__("TURKISH"),
							"uk"=>__("UKRAINIAN"),
							"vi"=>__("VIETNAMESE"),
							"zh-CN"=>__("CHINESE (SIMPLIFIED)"),
							"zh-TW"=>__("CHINESE (TRADITIONAL)")
                                                
        ); 
        ?> 
        <select name="language" id="language">
            <?php foreach($lang_array  as $lg => $lg_name){ ?>
                <option value="<?php echo $lg ?>" <?php echo ($lg == $language ? "selected" : "") ?> ><?php echo $lg_name ?></option>
            <?php } ?>
        </select>   
        </td>
      </tr>       
      <tr>
        <td align="right" valign="top"><strong><?php _e("Circle") ?></strong></td>
        
        <td><input name="focus" type="checkbox" value="all" <?php if ($focus == "all") echo "checked = \"checked\"" ?> /> <?php _e(" Check if you want to focus all the map's points automatically with an interval of <br /><br />") ?><input name="interval" type="text" size="6" value="<?php echo $interval ?>" /> <?php _e("milliseconds.") ?></td>
      </tr>
      <tr>
        <td align="right" valign="top"> <strong><?php _e("Focus Type") ?></strong></td>
        <td>
        <select name="focus_type" id="focus_type">
           <option value="open" <?php echo ($focus_type == "open" ? "selected" : "") ?> ><?php _e("Open Markers") ?></option>
           <option value="center" <?php echo ($focus_type == "center" ? "selected" : "") ?> ><?php _e("Center Markers") ?></option>
        </select>
        </td>        
      </tr>        
      <tr>
        <td align="right" valign="top"> <strong><?php _e("Animation") ?></strong></td>
        <td><input name="animate" type="checkbox" value="true" <?php if ($animate) echo "checked = \"checked\"" ?> /> <?php _e(" Check if you want to animate the markes.") ?></td>        
      </tr>        
    </table> 
         
    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" />
    </p>
    
    <h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;"><?php _e("Markers") ?></h3>
      
    <table width="80%" border="0" cellspacing="10" cellpadding="0">             
      <tr>
        <td align="right" valign="top" colspan="2">

		<?php gmshc_deploy_icons(); ?>
		
        </td>
      </tr>
      <tr>
        <td align="left" valign="top" colspan="2">
            <?php _e("To include new icons just specify the file location:") ?><br />
            <input type="file" name="datafile" size="40" /> <input type="submit" name="upload" value="Upload" class="button" />
        </td>
      </tr> 
    </table>
    
    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" />
    </p>
    
    <h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;"><?php _e("Info Windows") ?></h3>
    
    <p><?php _e("This is the html of the Info Window opened from the markers.") ?></p>

    <div id="gmshc_html">
        <div id="gmshc_previews">
            <p><strong><?php _e("Previews") ?></strong></p> 
            <div id="gmshc_html_previews">       
            <?php echo gmshc_defaul_windowhtml(); ?>
            </div>
        </div>
        <div id="gmshc_html_cont">
            <p><strong><?php _e("Custom Html") ?></strong></p>
            <textarea name="windowhtml" cols="60" rows="12" id="windowhtml">
            <?php  
            if  (empty($windowhtml)) echo gmshc_defaul_windowhtml(); 
            else {
                echo str_replace("\\", "",$windowhtml);
            }
            ?>
            </textarea>
        </div>        
    </div>
    
    <p><?php _e("The following tags can be used.") ?></p>    
    <table width="80%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td width="60" align="right"><strong>%title%</strong></td>
        <td><?php _e("Custom title of the point") ?></td>
      </tr>
      <tr>
         <td align="right"><strong>%link%</strong></td>
        <td><?php _e("Permanet Link of the post where the point is attached") ?></td>
      </tr>
      <tr>
        <td align="right"><strong>%thubnail%</strong></td>
        <td><?php _e("Thubnail attached to the point") ?></td>
      </tr>
      <tr>
        <td align="right"><strong>%description%</strong></td>
        <td><?php _e("Description of the point") ?></td>
      </tr>      
      <tr>
        <td align="right"><strong>%excerpt%</strong></td>
        <td><?php _e("Excerpt of the post where the point is attached") ?></td>
      </tr>  
      <tr>
        <td align="right"><strong>%address%</strong></td>
        <td><?php _e("The address of this point") ?></td>
      </tr>
      <tr>
        <td align="right"><strong>%open_map%</strong></td>
        <td><?php _e("Open this point on Google Map") ?></td>
      </tr> 
      <tr>
        <td align="right"><strong>%width%</strong></td>
        <td><?php _e("Info Html width") ?></td>
      </tr>           
    </table>


    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" /><input type="submit" name="Restore_Default" value="<?php _e("Restore Default") ?>" class="button" />
    </p>
    </form>
    
    <?php echo gmshc_plugin_menu(); ?>
    
    </div>


<?php } 

/**
 * Adding media tab
 */
function gmshc_media_menu($tabs) {
$newtab = array('gmshc' => __('Google Map Shortcode', 'gmshc'));
return array_merge($tabs, $newtab);
}

add_filter('media_upload_tabs', 'gmshc_media_menu');

function gmshc_plugin_menu(){ 

   $links_arr = array(
   						array("text"=>__("Plugin Page"),"url"=>"http://web-argument.com/google-map-shortcode-wordpress-plugin/"),
						array("text"=>__("How To Use"),"url"=>"http://web-argument.com/google-map-shortcode-how-to-use/"),
						array("text"=>__("Shortcode Reference"),"url"=>"http://web-argument.com/google-map-shortcode-reference/"),						
						array("text"=>__("Examples"),"url"=>"http://web-argument.com/google-map-shortcode-wordpress-plugin/#examples"),
						array("text"=>__("Donate"),"url"=>"https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=support%40web%2dargument%2ecom&lc=US&item_name=Web%2dArgument%2ecom&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted")
						);
						
   $output = "<p align='center' style='font-size:14px;'>";
   						
   foreach ($links_arr as $link){
	   $output .= "<a href=".$link['url']." target='_blank'>".$link['text']."</a> &nbsp; ";	   
   }
   
   $output .= "</p>";
   
   return $output;   	

}

?>
