/**
 * Google Map Shortcode 
 * Version: 2.1
 * Author: Alain Gonzalez
 * Author URI: http://web-argument.com/
*/

(function ($) {

	 $(window).load(function(){     
	 
		var iconSelect = "";
		
		$(".gmshc_icon,.gmshc_thumb").click(function(){
			gmshc_switchImg($(this)); 
		}).mouseover(function(){
			$(this).css({"border":"solid #cccccc 1px"})
		}).mouseout(function(){
			$(this).css({"border":"solid #ffffff 1px"})
		});         
		
		$("#insert_map").click(function(){
		
			gmshc_add_map();
			parent.tb_remove();
			
		});
		
		$("#gmshc_show").click(function(){
			var mapDiv = $("#gmshc_map");
			var mapBtn = $(this);
			if (mapDiv.height() >1) {
				mapDiv.height("1");				
				mapBtn.text(mapBtn.attr("show"));
			} else {
				mapDiv.height("440");				
				mapBtn.text(mapBtn.attr("hide"));								
			}
		});	
		
     	gmshc_update_editor_custom_field();
	
	 });

	function gmshc_switchImg(obj) {		
		var iconSrc = obj.children("img").attr("src");
		obj.siblings().removeClass('gmshc_selected');			
		obj.addClass('gmshc_selected');		
		obj.siblings("input").val(iconSrc);
		//$("#default_icon").val(iconSrc);
	}
	
     function gmshc_add_map(){
        var width = $("#width").val();
        var height = $("#height").val();
        var zoom = $("#zoom").val();
        
        str = "[google-map-sc";
		if (width != '')
		str += " width="+width;
		if (height != '')
		str += " height="+height;
		if (zoom != '')
		str += " zoom="+zoom;				
		str +="]"; 
        
		var win = window.dialogArguments || opener || parent || top;
		win.send_to_editor(str);		
   
    }
	
	function gmshc_update_editor_custom_field(){
		var mapData = $("#post_data").val();
		jQueryParent = parent.jQuery;
		var gmshcDivPostCustomStuff = jQueryParent("#postcustom input:[value=google-map-sc]").parents("tr");
		if (gmshcDivPostCustomStuff.length > 0)	jQueryParent("textarea",gmshcDivPostCustomStuff).val(mapData);
	}
    
    function gmshc_delete_point(id,msg){
        var answer = confirm(msg);
		alert(answer);
        if (answer) {
        var width = $("#width").val();
        var height = $("#height").val();
        var zoom = $("#zoom").val();        
        var url = "?post_id=<?php echo $post_id ?>&tab=gmshc&delp="+id+"&width="+width+"&height="+height+"&zoom="+zoom;
        window.location = url;
        } else {
        return false;
        }	
    }
	
	   
	 
})(jQuery);
	
	
	