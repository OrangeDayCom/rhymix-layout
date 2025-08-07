(function($){

	$(document).on('click', '.ap_profile nav ul .ap_profile_option', function() {
		var idx = $(this).index();

		$(this).parent().children('.active').removeClass('active');
		$(this).addClass('active');

		$(this).closest('nav').next('main').children('.active').removeClass('active');
		$(this).closest('nav').next('main').children('section').eq(idx).addClass('active');
	});
	
})(jQuery);