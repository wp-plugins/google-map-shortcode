<?php
/**
 * Google Map Shortcode 
 * Version: 2.1
 * Author: Alain Gonzalez
 * Author URI: http://web-argument.com/
*/

class GMSHC_Post_Map
{
	var $post_id;
	var $points = array();
	var $post_data;
	
	function create_post_map($id) {
		$this->post_id = $id;		
		$this->load_data();
	}
	
	function add_point($single_point){
		 array_unshift($this -> points,$single_point);
		 $saved = gmshc_save_points($this->post_id,$this -> points);
		 if($saved) {
			 $this->load_data();			 
		 }	
		 return $saved;
	}


	function delete_point($point_id){
	
        unset($this->points[$point_id]);
		$saved = gmshc_save_points($this->post_id,$this->points);
		if($saved) {
		   $this->load_data();			 
		}
		return $saved;		
	}
	
	function update_points($address_list,$ltlg_list,$title_list,$desc_list,$icon_list,$thumb_list){
		$new_points_array = array();
		$point = array();
		foreach ($address_list as $id => $address){
			$new_point = new GMSHC_Point();
			if($new_point->create_point($address,$ltlg_list[$id],$title_list[$id],$desc_list[$id],$icon_list[$id],$thumb_list[$id],$this->post_id,false)) {
				array_push($new_points_array,$new_point);
			}
			else return false;			 
		}
		
		
		 $saved = gmshc_save_points($this->post_id,$new_points_array);
		 if($saved) {
			 $this->load_data();			 
		 }
		 return $saved;
	}
	
	
	
	function load_data(){
		$this->points = gmshc_get_points($this->post_id);
		$this->post_data = get_post_meta($this->post_id,'google-map-sc',true);
	}
	
}
?>