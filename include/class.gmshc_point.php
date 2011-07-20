<?php
/**
 * Google Map Shortcode 
 * Version: 2.1
 * Author: Alain Gonzalez
 * Author URI: http://web-argument.com/
*/

class GMSHC_Point {

	var $address;
	var $ltlg;
	var $title;
	var $description;
	var $icon;
	var $thumbnail;
	var $post_id;

    function create_point($address,$ltlg,$title,$description,$icon,$thumbnail,$post_id,$check = true){ 

		if(empty($address)) return false;
		if ($check)	{
			$temp_point = gmshc_point($address,$ltlg);
			if (count($temp_point) > 0) {
				$temp_address = $temp_point['address'];
				$temp_ltlg = $temp_point['point'];
			} else return false;				
		}
		else {
			$temp_address = $address;
			$temp_ltlg = $ltlg;		
		}
			$this->address = $temp_address;
			$this->ltlg = $temp_ltlg;
			$this->title = $title;
			$this->description = $description;	
			$this->icon = $icon;
			$this->thumbnail = $thumbnail;
			$this->post_id = $post_id;
			return true;
    }
	
}

?>