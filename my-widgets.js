jQuery(document).ready(function($){
	$('.eelv_widget_uptime').click(function(){
		id = $(this).data('id');
		var cible = $(this).parent().parent();
		if(!isNaN(id)){
			$(this).hide(500);
			$.get(ajaxurl,{action:'eelv_widget_uptime',widget_id:id},function(retour){
				cible.html(retour);	
			},'html');
		}
		return false;
	});
	if($('body').hasClass('widgets-php')){
		//Widget page
	}
});
