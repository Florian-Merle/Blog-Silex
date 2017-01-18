$(document).ready(function() {
    /* fade In */
    $('.container').fadeIn(750);
    
    /* top btn */   
	$('.top-btn').click(function(){
		$('html,body').animate({scrollTop: '0'}, 400,'easeOutQuint');
		return false;
	});
});