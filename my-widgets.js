function emw_uptime(){
    $('.eelv_widget_uptime').unbind('click').click(function(){
        id = $(this).data('id');
        var cible = $(this).parent().parent();
        if(!isNaN(id)){
            $(this).hide(500);
            $.get(ajaxurl,{action:'eelv_widget_uptime',widget_id:id},function(retour){
                cible.html(retour); 
                emw_uptime();
            },'html');
        }
        return false;
    });
}
jQuery(document).ready(function($){
	emw_uptime();
	if($('body').hasClass('widgets-php')){
		//Widget page
	}
});
