(function($){

	$(document).on('click', '.ap_profile a', function() {
		var $this = $(this);
		var href = $this.attr('href');
		if(!href || /^(?:javascript|mailto):|#/.test(href)) {
			return;
		}
		if (!$this.attr("target") && !isSameOrigin(location.href, href)) {
			$this.attr("target", "_blank");
		}
	});
	
})(jQuery);