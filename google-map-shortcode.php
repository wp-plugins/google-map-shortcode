<?php
/*
Plugin Name: Google Map Shortcode
Plugin URI: http://web-argument.com/google-map-shortcode-2-0-total-solution/
Description: Include Google Map in your blogs with just one click. 
Version: 2.1.1
Author: Alain Gonzalez
Author URI: http://web-argument.com/
*/

define('GMSC_PLUGIN_DIR', WP_PLUGIN_DIR."/".dirname(plugin_basename(__FILE__)));
define('GMSC_PLUGIN_URL', WP_PLUGIN_URL."/".dirname(plugin_basename(__FILE__)));
define('GMSHC_VERSION_CHECK','2.1');

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
							'language' => 'en',
							'windowhtml' => gmshc_defaul_windowhtml(),
							'icons' => array(),
							'default_icon' => GMSC_PLUGIN_URL.'/images/icons/marker.png',
							'version' => '2.1.1'
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
	$gmshc_header .= "<script type=\"text/javascript\" src=\"".GMSC_PLUGIN_URL."/js/gmshc-render.js\"></script>\n";	
	$gmshc_header .=  "\n<!-- /Google Map Shortcode Version ".$options['version']."-->\n";		
		
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
	$zoom = isset($_REQUEST['zoom']) ? $_REQUEST['zoom'] : $options['zoom'];
	
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
	        if($new_point -> create_point($address,"",$title,$description,$icon,$selected_thumbnail,$post_id)){
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
    <link href="<?php echo GMSC_PLUGIN_URL ?>/styles/gmshc-admin-styles.css" rel="stylesheet" type="text/css"/>
    
        <div style="width:620px; margin:10px auto">
    
        <form  action="#" method="post">
           <textarea name = "post_data" id="post_data" style="display:none"><?php echo $post_points->post_data ?></textarea>
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
                            <strong><?php _e("Thumbnail: "); ?></strong><?php _e("Select by clicking on the images"); ?>
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
                    <p align="left"><a class="button" href = "?post_id=<?php echo $post_id ?>&type=image" title="Upload Images"><?php _e("Upload Images") ?></a></p>                  
				</td>
            </tr>             			
            </table>
            
        	<p><input class="button-primary" value="<?php _e("Add Point") ?>" name="add_point" type="submit"></p>
            
			<?php
            if ( count($post_points -> points) > 0 ){
            ?>
     
            <table class="widefat" cellspacing="0">
                <thead>
                <tr>
                <th><?php _e("Marker"); ?></th>
                <th><?php _e("Thumbnail"); ?></th>
                <th><?php _e("Title/Description"); ?></th>
                <th><?php _e("Address/LtLg"); ?></th>
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
        
   	    <p><input class="button-primary" value="<?php _e("Insert Map"); ?>" type="button" id="insert_map"></p>
        <br />
        
			<?php  } ?>
		</form>
	</div>

   
<?php
}

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
	$defaul_gmshc_windowhtml .= "<div style='float:left'>%thubnail%</div>\n";	
	$defaul_gmshc_windowhtml .= "</div>\n";
	
	return $defaul_gmshc_windowhtml;

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
	$icon = $options['default_icon'];
	$language = $options['language'];	

	// First Point in the post
	$address_meta = get_post_meta($post -> ID, 'google-map-sc-address');
	$point_meta = get_post_meta($post -> ID, 'google-map-sc-latlng');			
	
	$the_address = isset($address_meta[0]) ? $address_meta[0] : '';
	$point = isset($point_meta[0]) ? $point_meta[0] : '';
	$the_items = array();	
	
	extract(shortcode_atts(array(
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
		'language' => $language	
	), $atts));

	$map_points = array();

	// When address is set
	 if (!empty($address)) {
			 
		//create single point object id = -1
		$new_point = new GMSHC_Point();	
		if($new_point -> create_point($address,"",$title,$description,$icon,$thumbnail,-1)) $map_points[0]=$new_point;	
	
	 // When id is set
	 } else if (!empty($id)) {
		$post_points = new GMSHC_Post_Map();
		$post_points -> create_post_map($id);
		if (count($post_points -> points) > 0) $map_points = $post_points->points;	
		 
	} else if ($cat != '') {
	
		$categories = split (",",$cat); 
		$j = 0;
		
		$post_obj = get_posts(array('category__in'=>$categories,'numberposts'=>-1));
		foreach ($post_obj as $post_item) {	
		  //create points object by cat
		  $post_points = new GMSHC_Post_Map();
		  $post_points -> create_post_map($post_item->ID);
		  if (count($post_points->points) >0) {
			foreach ($post_points->points as $point) { 			  
			  if (count($post_points -> points) > 0) array_push($map_points,$point);  
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

		//Generate Map form points, width, height, zoom
	    
		return gmshc_generate_map($map_points, $width, $height, $zoom);					
} 
	else return __("There is not points to locate on the map");
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
	
	<?php 

	if(isset($_POST['Submit'])){
	
     		$newoptions['width'] = isset($_POST['width'])?$_POST['width']:$options['width'];
			$newoptions['height'] = isset($_POST['height'])?$_POST['height']:$options['height'];
			$newoptions['zoom'] = isset($_POST['zoom'])?$_POST['zoom']:$options['zoom'];
			$newoptions['language'] = isset($_POST['language'])?$_POST['language']:$options['language'];
			$newoptions['windowhtml'] = isset($_POST['windowhtml'])? $_POST['windowhtml']:$options['windowhtml'];	

			$newoptions['default_icon'] = isset($_POST['default_icon'])?$_POST['default_icon']:$options['default_icon'];
			$newoptions['icons'] = $options['icons'];	
					
			$newoptions['version'] = GMSHC_VERSION_CHECK;
	
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
	$zoom = $options['zoom'];
	$language = $options['language'];
	$windowhtml = $options['windowhtml'];	
	$default_icon = $options['default_icon'];
	
	?>  
	
	<form method="POST" name="options" target="_self" enctype="multipart/form-data">
	
	<h3><?php _e("Maps Parameters") ?></h3>
	
	<p><?php _e("The shortcode attributes overwrite these options.") ?></p>
	
    <table width="80%%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td colspan="2"><strong><?php _e("Dimensions") ?></strong></td>
      </tr>  
      <tr>
        <td width="60" align="right" height="40"><?php _e("Width") ?></td>
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
        <td colspan="2"><strong><?php _e("Select Default Icon") ?></strong></td>
      </tr>   
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
    
    <p><?php _e("This is the html inside of the Map Info Window opened after clicking on the markers.") ?></p>

    <div id="gmshc_html">
        <textarea name="windowhtml" cols="50" rows="12" id="windowhtml">
        <?php  
        if  (empty($windowhtml)) echo gmshc_defaul_windowhtml(); 
        else {
			echo str_replace("\\", "",$windowhtml);
		}
        ?>
        </textarea>
        <div id="gmshc_previews">
            <strong><?php _e("Previews") ?></strong> 
            <div id="gmshc_html_previews">       
            <?php echo gmshc_defaul_windowhtml(); ?>
            </div>
        </div>
    </div>
    
    <p><?php _e("You can include the following tags.") ?></p>    
    <table width="80%%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td width="60" align="right"><strong>%title%</strong></td>
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
        <td align="right"><strong>%description%</strong></td>
        <td><?php _e("The excerpt of your post") ?></td>
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
      <tr>
        <td align="right"><strong>%width%</strong></td>
        <td><?php _e("Info Html width") ?></td>
      </tr>           
    </table>


    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" /><input type="submit" name="Use_Default" value="<?php _e("Restore Default Html") ?>"/>
    </p>
    
    <h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;"><?php _e("How to Use") ?></h3>
    <p><?php _e("You can include a Google Map Shortcode everywhere") ?></p>
    
    <p><?php _e("In your post using: ") ?><strong>[google-map-sc option = "option value"]</strong></p>
    <p><?php _e("In your theme files using: ") ?><strong>  echo do_shortcode ('[google-map-sc option = "option value"]') </strong></p>
 
    
    <h3 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;"><?php _e("Feedback") ?></h3>
    
    <p><?php _e('For more details and examples visite the <a href="http://web-argument.com/2011/07/18/google-map-shortcode-plugin-version-2-1">Plugin Page</a>. All the comments are welcome.') ?></p>
    
    
    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" /><input type="submit" name="Restore_Default" value="<?php _e("Restore Default") ?>" class="button" />
    </p>
    </form>
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


?>
