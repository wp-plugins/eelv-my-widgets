function emw_uptime(){
    jQuery('.eelv_widget_uptime').unbind('click').click(function(){
        id = jQuery(this).data('id');
        var cible = jQuery(this).parent().parent();
        if(!isNaN(id)){
            jQuery(this).hide(500);
            jQuery.get(ajaxurl,{action:'eelv_widget_uptime',widget_id:id},function(retour){
                cible.html(retour); 
                emw_uptime();
            },'html');
        }
        return false;
    });
}
jQuery(document).ready(function(){
	emw_uptime();
	if(jQuery('body').hasClass('widgets-php')){
		//Widget page
	}
});
