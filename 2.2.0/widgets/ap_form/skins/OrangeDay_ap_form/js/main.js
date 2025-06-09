jQuery(document).ready(function($) {

	// Basic setting //

	if ( $('.floating-labels').length > 0 )
	{
		floatLabels();
	}
	if ( $('.ap_extra_info').length > 0 )
	{
		if ( $('.ap_extra[rel="homepage"]').length > 0 )
		{
			$('.ap_extra').children('input.homepage').attr('type', 'url');
		}
		if ( $('.ap_extra[rel="email_address"]').length > 0 )
		{
			$('.ap_extra').children('input.email_address').attr('type', 'email');
		}
		if ( $('.ap_extra[rel="tel"]').length > 0 )
		{
			$('.ap_extra[rel="tel"]').each(function() {
				setCustomValidityForPhoneNumber($(this));
			});
		}
		if ( $('.ap_form select').length > 0 )
		{
			$('.ap_form select').each(function() {
				if ( $(this).children('option').eq(0).val() === '' ) {
					$(this).children('option').eq(0).remove();
				}

				if ( $(this).next('input').attr('type') === 'tel' ) {
					$(this).prepend('<option value="">국제번호</option>');
				} else {
					$(this).prepend('<option value=""></option>');
				}

				if ( $(this).attr('name') !== 'phone_country' ) {
					$(this).val('').trigger('change');
				}
			});
		}
		if ( $('.ap_extra[rel="date"]').length > 0 )
		{
			$('.ap_extra[rel="date"]').children('input.date').attr('maxlength', 10).attr('autocomplete', 'off');
			$('.ap_extra[rel="date"]').each(function() {
				setCustomValidityForDatePicker($(this));
			});
		}
		if ( $('.ap_extra[rel="kr_zip"]').length > 0 )
		{
			$('.ap_extra[rel="kr_zip"]').find('input.krzip-postcode').removeAttr('disabled');
		}
	}
	if ( $('.ap_item[rel="password"]').length > 0 )
	{
		$('.ap_item[rel="password"]').children('input').attr('autocomplete', 'new-password').after('<i class="ap_eye xi xi-eye"></i>');
	}


	// Validaion for required items //

	if ( $('.ap_extra.is_required').length > 0 )
	{
		$('.ap_extra.is_required').each(function() {
			var rel = $(this).attr('rel');
			if ( rel === 'checkbox' )
			{
				if ( $(this).find('input[type="checkbox"]').length === 1 )
				{
					$(this).find('input[type="checkbox"]').attr('required', true);
				}
				else
				{
					var error = ( xe.current_lang === 'ko' ) ? '1개 이상의 항목을 선택하세요.' : 'At least one checkbox must be selected.';
					if ( $(this).find('input[type="checkbox"]:checked').length < 1 )
					{
						$(this).find('input[type="checkbox"]')[0].setCustomValidity(error);
					}
					$(this).find('input[type="checkbox"]').on('change', function()
					{
						if ( $(this).closest('.ap_extra').find('input[type="checkbox"]:checked').length < 1 )
						{
							$(this).closest('.ap_extra').find('input[type="checkbox"]')[0].setCustomValidity(error);
						}
						else
						{
							$(this).closest('.ap_extra').find('input[type="checkbox"]')[0].setCustomValidity('');
						}
					});
				}
			}
			else if ( rel === 'radio' )
			{
				$(this).find('input[type="radio"]').first().attr('required', true);
			}
			else if ( rel === 'date' )
			{
				$(this).children('input.date').attr('required', true);
			}
			else if ( rel === 'kr_zip' )
			{
				var error = ( xe.current_lang === 'ko' ) ? '우편번호를 검색하세요.' : 'Search for Korean zip-code.';
				if ( $(this).find('input.krzip-hidden-postcode').val() === '' )
				{
					$(this).find('input.krzip-postcode')[0].setCustomValidity(error);
				}
				setCustomValidityForZipCode($('.ap_extra[rel="kr_zip"] input.krzip-postcode'), function() {});
				$(this).find('input.krzip-detailAddress').attr('required', true);
			}
			else
			{
				$(this).children().not('label, div, p').attr('required', true);
			}
		});
	}


	// Events //

	if ( $('.ap_basic[rel="phone_number"]').length > 0 )
	{
		var phone_number = 'input[name="phone_number"]';
		var state = $('.ap_basic[rel="phone_number"]').attr('class').replace(/[^A-Z]/g, '');
		if ( state === 'KOR' || ( !state && xe.current_lang === 'ko' ) )
		{
			$(phone_number).attr('maxlength', '13').on('input', function() {
				autoCorrect($(this), $(this).val());
			});
		}
		else
		{
			$(document).on('input', phone_number, function() {
				$(this).val($(this).val().replace(/[^0-9-]/g, ''));
			});
		}

		if ( $(phone_number).hasClass('use_international') )
		{
			if ( $('.ap_form').hasClass('rx_ajax') )
			{
				$(document).on('change', 'select[name="phone_country"]', function() {
					if ( $(this).val() === 'KOR' )
					{
						$(this).next(phone_number).off('input').attr('maxlength', '13').on('input', function() {
							autoCorrect($(this), $(this).val());
						});
					}
					else
					{
						$(this).next(phone_number).off('input').removeAttr('maxlength', '13').on('input', function() {
							$(this).val($(this).val().replace(/[^0-9-]/g, ''));
						});
					}
				});
			}
			else
			{
				$(phone_number).off('input').on('input', phone_number, function() {
					$(this).val($(this).val().replace(/[^0-9-\+\(\)]/g, ''));
				});
			}
		}
	}

	$('.ap_extra[rel="date"] input.date').on('input', function() {
		var label = $(this).parent().children('label');
		if ( $(this).val() !== '' && !label.hasClass('float') )
		{
			label.addClass('float');
		}
		else if ( $(this).val() === '' && label.hasClass('float') )
		{
			label.removeClass('float');
		}
	});

	$('.ap_extra[rel="kr_zip"] input.krzip-postcode').on('input', function() {
		$(this).val($(this).closest('.krZip').children('.krzip-hidden-postcode').val());
		$(this).select();
		return false;
	});

	$('.ap_item[rel="password"] input').on('input', function() {
		var input = $(this);
		var eye = $(this).next('.ap_eye');
		if ( !eye.is(':visible') )
		{
			eye.show();
		}
		if ( input.val() === '' )
		{
			eye.hide();
		}
	});
	$('.ap_eye').on('click', function() {
		var eye = $(this);
		var input = eye.prev('input');
		if ( input.attr('type') === 'password' )
		{
			input.attr('type', 'text');
			eye.removeClass('xi-eye').addClass('xi-eye-slash');
		}
		else
		{
			input.attr('type', 'password');
			eye.removeClass('xi-eye-slash').addClass('xi-eye');
		}
	});

	$('.ap_section label').on('click', function() {
		var input = $(this).prev();
		var type = input.attr('type');
		if ( type === 'checkbox' )
		{
			if ( input.prop('checked') )
			{
				input.prop('checked', false);
			}
			else
			{
				input.prop('checked', true);
			}
		}
		else if ( type === 'radio' )
		{
			if ( input.prop('checked') )
			{
				return false;
			}
			else
			{
				input.prop('checked', true);
				$(this).closest('.ap_section').find('input').not(input).prop('checked', false);
			}
		}
	});

	var apc = '.ap_privacy_content',
		v = 'is_visible';
	$('.ap_privacy').find('.ap_popup_trigger').on('click', function(e) {
		e.stopPropagation();
		$(this).next(apc).find('input').prop('checked', false);
		$(this).next(apc).addClass(v);
		return false;
	});
	$('.ap_privacy').find('.ap_popup_close').on('click', function() {
		$(this).closest(apc).removeClass(v);
		return false;
	});
	$(apc).find('label').on('click', function() {
		$(this).prev('input').trigger('change');
		return false;
	});
	$(apc).find('input').on('change', function() {
		if ( $(this).is(':checked') )
		{
			$(this).closest(apc).removeClass(v);
			$(this).closest(apc).siblings('input').prop('checked', true);
		}
	});
	$(document).on('click', function(e) {
		var container = $(apc + '.' + v);
		if ( !container.is(e.target) && container.has(e.target).length === 0 )
		{
			container.removeClass(v);
		}
	});


	// Functions //

	function floatLabels()
	{
		var inputFields = $('.floating-labels label').next();
		inputFields.each(function() {
			var singleInput = $(this);
			//check if user is filling one of the form fields
			checkVal(singleInput);
			singleInput.on('change keyup', function() {
				checkVal(singleInput);
			});
		});
	}

	function autoCorrect(obj, val)
	{
		var str = val.replace(/[^0-9]/g, '');
		var tmp = '';
		// 0으로 시작해야 함
		if ( str.substring(0, 1) != '0' )
		{
			obj.val(tmp);
		}
		else
		{
			// 서울 지역번호를 사용할 경우
			if ( str.substring(0, 2) == '02' )
			{
				// 길이 2까지는 그대로 출력
				if ( str.length < 3 )
				{
					return str;
				}
				// 지역번호 02로 시작하면 최대 10자리까지만 입력 제한
				if ( str.length > 10 )
				{
					str = str.substring(0, 10);
				}
				if ( str.length < 6 )
				{
					tmp += str.substr(0, 2); tmp += '-'; tmp += str.substr(2); obj.val(tmp);
				}
				else if ( str.length < 10 )
				{
					tmp += str.substr(0, 2); tmp += '-'; tmp += str.substr(2, 3); tmp += '-'; tmp += str.substr(5); obj.val(tmp);
				}
				else
				{
					tmp += str.substr(0, 2); tmp += '-'; tmp += str.substr(2, 4); tmp += '-'; tmp += str.substr(6); obj.val(tmp);
				}
			}
			else
			{ // 서울 지역번호 외의 경우
				// 길이 3까지는 그대로 출력
				if ( str.length < 4 )
				{
					return str;
				}
				// 서울 지역번호 외로 시작하면 최대 11자리까지만 입력 제한
				if ( str.length > 11 )
				{
					str = str.substring(0, 11);
				}
				if ( str.length < 7 )
				{
					tmp += str.substr(0, 3); tmp += '-'; tmp += str.substr(3); obj.val(tmp);
				}
				else if ( str.length < 11 )
				{
					tmp += str.substr(0, 3); tmp += '-'; tmp += str.substr(3, 3); tmp += '-'; tmp += str.substr(6); obj.val(tmp);
				}
				else
				{
					tmp += str.substr(0, 3); tmp += '-'; tmp += str.substr(3, 4); tmp += '-'; tmp += str.substr(7); obj.val(tmp);
				}
			}
		}
	}

	function checkVal(inputField)
	{
		if ( inputField.val() === '' )
		{
			inputField.prev('label').removeClass('float')
		}
		else
		{
			inputField.prev('label').addClass('float');
		}
	}

	function setCustomValidityForPhoneNumber(obj)
	{
		var error_0 = ( xe.current_lang === 'ko' ) ? '전화번호를 입력하세요.' : 'Enter the phone number.';
		var error_1 = ( !obj.hasClass('is_required') ) ? '' : error_0;
		var error_2 = ( xe.current_lang === 'ko' ) ? '전화번호 형식이어야 합니다.' : 'It must be in Korean phone number format.';
		var ref = [0, 0, 0];

		obj.children('input.tel').each(function() {
			this.setCustomValidity(error_1);
		});

		obj.children('input.tel').on('input', function() {
			$(this).val($(this).val().replace(/[^0-9]/g, ''));
			if ( $(this).val().length < 1 )
			{
				this.setCustomValidity(error_1);
				ref[$(this).index()] = 0;
			}
			else
			{
				setCustomValidityForPhoneNumberDivided($(this), error_1, error_2);
				ref[$(this).index()] = ( $(this).val() !== '' ) ? 1 : 0;
			}
		});

		if ( ( !obj.hasClass('is_required') ) )
		{
			obj.children('input.tel').on('change', function() {
				if ( $.inArray( 1, ref ) === -1 )
				{
					obj.children('input.tel').each(function() {
						this.setCustomValidity(error_1);
					});
				}
				else
				{
					$.each(ref, function(i, v) {
						if ( v === 0 )
						{
							obj.children('input.tel')[i].setCustomValidity(error_0);
						}
						else
						{
							setCustomValidityForPhoneNumberDivided($(obj.children('input.tel')[i]), error_1, error_2);
						}
					});
				}
			});
		}
	}

	function setCustomValidityForPhoneNumberDivided(obj, error_1, error_2)
	{
		if ( obj.index()%3 === 0 )
		{
			if ( obj.val().charAt(0) != '0' )
			{
				obj.val('');
				obj[0].setCustomValidity(error_1);
			}
			if ( obj.val().length === 1 )
			{
				obj[0].setCustomValidity(error_2);
			}
			else if ( obj.val().length > 1 )
			{
				if ( obj.val().charAt(1) == '2' )
				{
					obj.val(obj.val().substring(0, 2));
					obj[0].setCustomValidity('');
				}
				else
				{
					if ( obj.val().length < 3 )
					{
						obj[0].setCustomValidity(error_2);
					}
					else
					{
						obj.val(obj.val().substring(0, 3));
						obj[0].setCustomValidity('');
					}
				}
			}
		}
		else if ( obj.index()%3 === 1 )
		{
			if ( obj.val().length < 3 )
			{
				obj[0].setCustomValidity(error_2);
			}
			else
			{
				obj[0].setCustomValidity('');
				if ( obj.val().length > 4 )
				{
					obj.val(obj.val().substring(0, 4));
				}
			}
		}
		else if ( obj.index()%3 === 2 )
		{
			if ( obj.val().length < 4 )
			{
				obj[0].setCustomValidity(error_2);
			}
			else
			{
				obj[0].setCustomValidity('');
				if ( obj.val().length > 4 )
				{
					obj.val(obj.val().substring(0, 4));
				}
			}
		}
	}

	function setCustomValidityForDatePicker(obj)
	{
		var error_0 = ( xe.current_lang === 'ko' ) ? '날짜를 선택하세요.' : 'Select a date.';
		var error_1 = ( !obj.hasClass('is_required') ) ? '' : error_0;
		var error_2 = ( xe.current_lang === 'ko' ) ? 'yyyy-mm-dd 형식이어야 합니다.' : 'It must be in the format "yyyy-mm-dd".';
		var date_format = /[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/;
		var yyyy, mm, dd;

		//obj.children('input.date')[0].setCustomValidity(error_1);

var inputEl = obj.children('input.date')[0];

if (inputEl) {
    inputEl.setCustomValidity(error_1);
} else {
    console.warn('input.date 요소가 없습니다.');
}

		obj.children('input.date').on('input change', function() {
			getVaildationDateFormat(this);

			$(this).prev().val(this.value.replace(/[^0-9]/g, ''));

			if ( this.value === '' )
			{
				this.setCustomValidity(error_1);
			}
			else
			{
				if ( !date_format.test(this.value) )
				{
					this.setCustomValidity(error_2);
				}
				else
				{
					yyyy = Number(this.value.split('-')[0]);
					mm = Number(this.value.split('-')[1]);
					dd = Number(this.value.split('-')[2]);
					if ( mm === 02 )
					{
						if ( dd === 30 || dd === 31 )
						{
							this.setCustomValidity(error_2);
						}
						else if ( dd === 29 && yyyy%4 != 0 )
						{
							this.setCustomValidity(error_2);
						}
						else
						{
							this.setCustomValidity('');
						}
					}
					else if ( ( mm === 04 || mm === 06 || mm === 09 || mm === 11 ) && dd === 31 )
					{
						this.setCustomValidity(error_2);
					}
					else
					{
						this.setCustomValidity('');
					}
				}
			}
		});

		obj.children('input.btn').on('click', function() {
			obj.children('input.date')[0].setCustomValidity(error_1);
			if ( obj.children('label').hasClass('float') )
			{
				obj.children('label').removeClass('float');
			}
		});
	}

	function setCustomValidityForZipCode(input, callback)
	{
		var value = input.val();
		setInterval(function() {
			if ( input.val() !== value )
			{
				if ( input.val() === '' )
				{
					var error = ( xe.current_lang === 'ko' ) ? '우편번호를 검색하세요.' : 'Search for Korean zip-code.';
					input[0].setCustomValidity(error);
				}
				else
				{
					input[0].setCustomValidity('');
				}
				value = input.val();
				callback();
			}
		}, 100);
	}

	function getVaildationDateFormat(obj) {
		var date = obj.value;
		var DataFormat;
		var RegNum;
		date = date.replace(/[^0-9]/g, '');
		if ( date === '' || date === null || date.length < 5 )
		{
			obj.value = date;
			return;
		}
		if ( date.length <= 6 )
		{
			DataFormat = '$1-$2';
			RegNum = /([0-9]{4})([0-9]+)/;
		}
		else if ( date.length <= 8 )
		{
			DataFormat = '$1-$2-$3';
			RegNum = /([0-9]{4})([0-9]{2})([0-9]+)/;
		}
		date = date.replace(RegNum, DataFormat);
		obj.value = date;
	}
});