$(function(){
	$getColor = XE.cookie.get('setOdayColor');
	if( $getColor != undefined ) {
		$('body').addClass("color_theme_"+$getColor);
	} else {
		setTheme_color('orange');
	}
	$getTheme = XE.cookie.get('setOdayDarkmode');	
	if( $getTheme != undefined ) {
		if ( $getTheme == "dark" ){
			doThemeDark();
		} else if ( $getTheme == "light" ) {
			doThemeLight();	
		} else {
			doThemeLight();
		}	
	} else {
		doThemeLight();
		setColorScheme("light");
	}
});

function setCookie( name, value, expiredays ) {
	var todayDate = new Date();
	todayDate.setDate( todayDate.getDate() + expiredays ); 
	document.cookie = name + "=" + escape( value ) + "; path=/; expires=" + todayDate.toGMTString() + ";"
}
function setThemeDark() {
	setCookie( "setOdayDarkmode", "dark" , 365 );
	doThemeDark();
	/*라이믹스 다크모드 전환*/
	setColorScheme("dark");
	$('iframe.cke_wysiwyg_frame').contents().find('body').removeClass("color_scheme_light");
	$('iframe.cke_wysiwyg_frame').contents().find('body').addClass("color_scheme_dark");
} 
function setThemeLight() {
	setCookie( "setOdayDarkmode", "light" , 365 );
	doThemeLight();
	/*라이믹스 다크모드 전환*/
	setColorScheme("light");
	$('iframe.cke_wysiwyg_frame').contents().find('body').removeClass("color_scheme_dark");
	$('iframe.cke_wysiwyg_frame').contents().find('body').addClass("color_scheme_light");
} 
function doThemeDark() {
	document.documentElement.setAttribute('color-theme', 'dark');
	$(".viewDark").hide();
	$(".viewLight").show();
}
function doThemeLight() {
	document.documentElement.setAttribute('color-theme', 'light');
	$(".viewDark").show();
	$(".viewLight").hide();
}
/* 주요 색상 변경 */
function setTheme_remove(dColor) {
	if( dColor != "white" ) { $('body').removeClass("color_theme_white"); }
	if( dColor != "orange" ) { $('body').removeClass("color_theme_orange"); }
	if( dColor != "blue" ) { $('body').removeClass("color_theme_blue"); }
	if( dColor != "green" ) { $('body').removeClass("color_theme_green"); }
	if( dColor != "red" ) { $('body').removeClass("color_theme_red"); }
}
function setTheme_color(tColor) {
	setTheme_remove(tColor);
	$('body').addClass("color_theme_"+tColor);
	setCookie( "setOdayColor", tColor , 365 );
}
