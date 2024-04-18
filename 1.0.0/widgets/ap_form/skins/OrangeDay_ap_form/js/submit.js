var values = [];

function sendMessage(f)
{
	// 라이믹스에서 구글 리캡챠 사용시 유효성 검사
	if ( typeof(grecaptcha) != 'undefined' )
	{
		if ( grecaptcha.getResponse() == "" )
		{
			var error_msg = ( xe.current_lang == 'ko' ) ? 'reCAPTCHA 스팸방지 기능을 체크해 주십시오.' : 'Please check reCAPTCHA.';
			alert(error_msg);
			return false;
		}
	}

	var nick_name = f.find('input[name="nick_name"]'),
		email_address = f.find('input[name="email_address"]'),
		phone_country = f.find('select[name="phone_country"]'),
		phone_number = f.find('input[name="phone_number"]'),
		title = f.find('input[name="title"]'),
		category = f.find('select[name="category"]'),
		message = f.find('textarea[name="message"]');

	// 로그인시 인풋창의 값을 취득할 수 있도록 함
	if ( nick_name.attr('disabled') ) nick_name.removeAttr('disabled');
	if ( email_address.attr('disabled') ) email_address.removeAttr('disabled');

	// 게시판 본문에 삽입할 내용을 취합
	var content = '';
	content = '<p><b>' + f.find('label[for="nick_name"]').text() + '</b> : ' + nick_name.val() + '</p>';
	if ( email_address.val() != '' )
	{
		content += '<p><b>' + f.find('label[for="email_address"]').text() + '</b> : ' + email_address.val() + '</p>';
	}
	else
	{
		var guest_email = 'guest@' + location.hostname.replace('www', '');
		email_address.val(guest_email);
		content += '<p><b>' + f.find('label[for="email_address"]').text() + '</b> : ' + email_address.val() + '</p>';
	}
	if ( phone_number.length > 0 && phone_number.val() != '' )
	{
		var phone_international = '';
		if ( phone_country.length > 0 && phone_country.val() != '' )
		{
			phone_international = phone_country.children('option[value='+ phone_country.val() +']').text().replace(/[^\(\+\-0-9\)]/g, '') + ' ';
			if ( phone_country.attr('disabled') )
			{
				phone_country.attr('disabled', false);
			}
		}
		content += '<p><b>' + f.find('label[for="phone_number"]').text() + '</b> : ' + phone_international + phone_number.val() + '</p>';
		phone_number.val(phone_international + phone_number.val());
		if ( phone_number.attr('disabled') )
		{
			phone_number.attr('disabled', false);
		}
	}
	if ( message.length > 0 && message.val() != '' )
	{
		content += '<p>&nbsp;</p>' +
			'<p><b>' + f.find('label[for="message"]').text() + '</b> : </p>' +
			'<p>&nbsp;</p>' +
			'<p style="margin-left:32px;">' + message.val().replace(/\n/g, "<br>") + '</p>';
	}

	// 제목, 카테고리, 내용 취합
	if ( title.val() == '' )
	{
		( xe.current_lang == 'ko' ) ? title.val(nick_name.val() + '님으로부터의 메시지') : title.val('Message From ' + nick_name.val());
	}
	if ( category.length > 0 )
	{
		f.children('input[name="category_srl"]').val(category.val());
		f.children('input[name="category_title"]').val(category.children('option:selected').text());
	}
	f.children('input[name="content"]').val(content);

	// 입력값을 전역 변수 values로 전달하고, 문서 삽입 시도
	values = f.serializeArray();
	return procFilter(f[0], insert);
}

function completeDocumentInserted(ret_obj)
{
	alert(ret_obj.message);

	var redirect = jQuery('.ap_form input[name="redirect"]').val();
	if ( redirect == 'M' )
	{
		location.href = default_url.setQuery('mid', ret_obj.mid);
	}
	else if ( redirect == 'D' )
	{
		location.href = default_url.setQuery('mid', ret_obj.mid).setQuery('document_srl', ret_obj.document_srl);
	}
	else if ( redirect == 'N' )
	{
		var f = jQuery('.ap_form');
		var result = jQuery('.ap_result');

		f.closest('.xe-widget-wrapper').hide();

		// 전달 변수값 삽입
		var idx = '', type = '';
		var this_idx = '', this_value = '';
		jQuery.each(values, function(i, val) {
			var name = val.name,
				value = val.value,
				target = result.find('[rel="'+ name +'"]');
			if ( value != '' )
			{
				if ( name.indexOf('extra_vars') == -1 )
				{
					if ( jQuery.inArray( name, ['title', 'nick_name', 'category_title', 'password'] ) != -1 )
					{
						target.text(value);
					}
					else if ( jQuery.inArray( name, ['email_address', 'phone_number', 'message'] ) != -1 )
					{
						if ( name == 'email_address' )
						{
							value = '<a href="mailto:'+ value +'">'+ value +'</a>';
						}
						else if ( name == 'phone_number' )
						{
							var _value = value;
							if ( value.indexOf('+') != -1 )
							{
								var full_num = value.split(' '),
									country = full_num[0],
									domestic = full_num[1];
								country = country.replace(/\(|\)/g, '');
								if ( $.inArray(country.replace(/[^0-9]/g, ''), ['39', '378', '379']) !== -1 )
								{
									domestic = domestic.replace(/^0/g, '');
								}
								value = '<a href="tel:'+ country + '-' + domestic +'">'+ value +'</a>';
							}
							else
							{
								value = '<a href="tel:'+ value +'">'+ _value +'</a>';
							}
						}
						else if ( name == 'message' )
						{
							value = value.replace(/\n/g, '<br />');
						}
						target.html(value);
					}
				}
				else
				{
					idx = name.replace(/[^0-9]/g, '');
					type = f.find('.ap_extra[data-idx="'+ idx +'"]').attr('rel');
					if ( jQuery.inArray( type, ['text', 'select', 'radio', 'date', 'password'] ) != -1 )
					{
						if ( type == 'date' ) value = value.replace(/(\d{4})(\d{2})(\d{2})/g, '$1-$2-$3');
						target.text(value);
					}
					else if ( jQuery.inArray( type, ['homepage', 'email_address', 'textarea'] ) != -1 )
					{
						if ( type == 'homepage' )
						{
							value = '<a href="'+ value +'" target="_blank">'+ value +'</a>';
						}
						else if ( type == 'email_address' )
						{
							value = '<a href="mailto:'+ value +'">'+ value +'</a>';
						}
						else if ( type == 'textarea' )
						{
							value = value.replace(/\n/g, '<br />');
						}
						target.html(value);
					}
					else if ( jQuery.inArray( type, ['tel', 'checkbox', 'kr_zip'] ) != -1 )
					{
						if ( type == 'tel' )
						{
							if ( this_idx != idx )
							{
								this_idx = idx;
								this_value = value;
							}
							else this_value += '-' + value;
							result.find('[rel="extra_vars'+ this_idx +'"]').html('<a href="tel:'+ this_value +'">'+ this_value +'</a>');
						}
						else if ( type == 'checkbox' )
						{
							if ( this_idx != idx )
							{
								this_idx = idx;
								this_value = value;
							}
							else this_value = ', ' + value;
							result.find('[rel="extra_vars'+ this_idx +'"]').append(this_value);
						}
						else if ( type == 'kr_zip' )
						{
							if ( this_idx != idx )
							{
								this_idx = idx;
								this_value = '[' + value + ']';
							}
							else this_value = ' ' + value;
							result.find('[rel="extra_vars'+ this_idx +'"]').append(this_value);
						}
					}
				}
			}
		});

		// 출력 요소 정리 : 빈 값이 존재하면 상위 요소 삭제
		result.find('[rel]').each(function() {
			var el = jQuery(this);
			if ( el.html() == '' )
			{
				el.parent().remove();
			}
		});
		// 출력 요소 정리 : 그룹에 값이 없으면 섹션 요소 삭제
		if ( result.find('.ap_result_basic').length < 1 ) result.children('section').eq(0).remove();
		if ( result.find('.ap_result_extra tr').length < 1 ) result.children('section').eq(1).remove();
		if ( result.find('.ap_result_content').length < 1 ) result.children('section').eq(2).remove();

		// TERMINAL ASSIGNMENT
		f.remove();
		result.show();
		result.closest('.xe-widget-wrapper').fadeIn();
		jQuery('html, body').animate({
			scrollTop: result.offset().top
		}, 400);
		setTimeout(function() {
			result.children('header').removeClass('is_blink');
		}, 3000);
	}
	else
	{
		var redirect_url = jQuery('.ap_form input[name="redirect_url"]').val();
		location.href = redirect_url;
	}
}