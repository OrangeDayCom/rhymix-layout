$(function(){
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

