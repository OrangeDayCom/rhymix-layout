$(function(){
	$getColor = XE.cookie.get('setOdayColor');
	if( $getColor != undefined ) {
		$('body').addClass("color_theme_"+$getColor);
		$('.mod-theme.color .bx.'+$getColor).addClass("active");
	} else {
		setTheme_color('orange');
		$('.mod-theme.color .bx.orange').addClass("active");
	}
	$getBright = XE.cookie.get('setOdayBright');
	if( $getBright != undefined ) {
		$('body').addClass("bright_theme_"+$getBright);
		$('.mod-theme.bright .bx.'+$getBright).addClass("active");
	} else {
		setTheme_bright('d2');
		$('.mod-theme.bright .bx.d2').addClass("active");
	}
});
/* 주요 색상 변경 
function setTheme_color_remove(dColor) {
	if( dColor != "white" ) { $('body').removeClass("color_theme_white"); }
	if( dColor != "gray" ) { $('body').removeClass("color_theme_gray"); }
	if( dColor != "orange" ) { $('body').removeClass("color_theme_orange"); }
	if( dColor != "blue" ) { $('body').removeClass("color_theme_blue"); }
	if( dColor != "green" ) { $('body').removeClass("color_theme_green"); }
	if( dColor != "red" ) { $('body').removeClass("color_theme_red"); }
}*/
function setTheme_color(tColor) {
	$('body').removeClass("color_theme_white color_theme_gray color_theme_orange color_theme_blue color_theme_green color_theme_red"); 
	$('body').addClass("color_theme_"+tColor);
	$('.mod-theme.color .bx').removeClass("active");
	$('.mod-theme.color .bx.'+tColor).addClass("active");
	/*
	$('iframe.cke_wysiwyg_frame').contents().find('body').removeClass("color_theme_white color_theme_gray color_theme_orange color_theme_blue color_theme_green color_theme_red");
	$('iframe.cke_wysiwyg_frame').contents().find('body').addClass("color_theme_"+tColor);
	*/
	setCookie( "setOdayColor", tColor , 365 );
}
/*
function setTheme_bright_remove(dBright) {
	if( dBright != "d1" ) { $('body').removeClass("bright_theme_d1"); }
	if( dBright != "d2" ) { $('body').removeClass("bright_theme_d2"); }
	if( dBright != "d3" ) { $('body').removeClass("bright_theme_d3"); }
}
*/
function setTheme_bright(tBright) {
	$('body').removeClass("bright_theme_d1 bright_theme_d2 bright_theme_d3");
	$('body').addClass("bright_theme_"+tBright);
	$('.mod-theme.bright .bx').removeClass("active");
	$('.mod-theme.bright .bx.'+tBright).addClass("active");
	/*
	$('iframe.cke_wysiwyg_frame').contents().find('body').removeClass("cbright_theme_d1 bright_theme_d2 bright_theme_d3");
	$('iframe.cke_wysiwyg_frame').contents().find('body').addClass("bright_theme_"+tBright);
	*/
	setCookie( "setOdayBright", tBright , 365 );
}