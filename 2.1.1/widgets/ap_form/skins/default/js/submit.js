var values = {};

function sendMessage(f) {
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
	if ( email_address.val() !== '' ) {
		content += '<p><b>' + f.find('label[for="email_address"]').text() + '</b> : ' + email_address.val() + '</p>';
	} else {
		var guest_email = 'guest@' + location.hostname.replace('www', '');
		email_address.val(guest_email);
		content += '<p><b>' + f.find('label[for="email_address"]').text() + '</b> : ' + email_address.val() + '</p>';
	}
	if ( phone_number.length > 0 && phone_number.val() !== '' ) {
		var phone_international = '';
		if ( phone_country.length > 0 && phone_country.val() !== '' ) {
			phone_international = phone_country.children('option[value='+ phone_country.val() +']').text().replace(/[^\(\+\-0-9\)]/g, '') + ' ';
			if ( phone_country.attr('disabled') ) {
				phone_country.attr('disabled', false);
			}
		}
		content += '<p><b>' + f.find('label[for="phone_number"]').text() + '</b> : ' + phone_international + phone_number.val() + '</p>';
		phone_number.val(phone_international + phone_number.val());
		if ( phone_number.attr('disabled') ) {
			phone_number.attr('disabled', false);
		}
	}
	if ( message.length > 0 && message.val() !== '' ) {
		content += '<p>&nbsp;</p>' +
			'<p><b>' + f.find('label[for="message"]').text() + '</b> : </p>' +
			'<p>&nbsp;</p>' +
			'<p style="margin-left:32px;">' + message.val().replace(/\n/g, "<br>") + '</p>';
	}

	// 제목, 카테고리, 내용 취합
	if ( title.val() === '' ) {
		( xe.current_lang === 'ko' ) ? title.val(nick_name.val() + '님으로부터의 메시지') : title.val('Message From ' + nick_name.val());
	}
	if ( category.length > 0 ) {
		f.children('input[name="category_srl"]').val(category.val());
		f.children('input[name="category_title"]').val(category.children('option:selected').text());
	}
	f.children('input[name="content"]').val(content);

	// 주요 입력값(확장변수 제외)을 전역 변수 values로 전달하고, 문서 삽입 시도
	for ( let v of f.serializeArray() ) {
		var name = v.name,
			value = v.value;

		if ( $.inArray( name, ['title', 'category_title', 'password', 'nick_name', 'email_address', 'phone_number', 'message'] ) === -1 ) {
			continue;
		}

		values[name] = value;
	};
	return procFilter(f[0], insert);
}

function completeDocumentInserted(insert_result) {
	alert(insert_result.message);
	if ( insert_result.error ) {
		return false;
	}

	var f = $('.ap_form');
	var redirect = f.find('input[name="redirect"]').val();

	if ( redirect === 'M' ) {
		location.href = default_url.setQuery('mid', insert_result.mid);
	} else if ( redirect === 'D' ) {
		location.href = default_url.setQuery('mid', insert_result.mid).setQuery('document_srl', insert_result.document_srl);
	} else if ( redirect === 'U' ) {
		var redirect_url = f.find('input[name="redirect_url"]').val();
		location.href = redirect_url;
	} else {
		var result = $('.ap_result');

		$.exec_json('board.dispBoardContentView', {mid : 'board', document_srl : insert_result.document_srl}, function(document_result) {
			(async function() {
				if ( document_result.error ) {
					alert(document_result.message);
					return false;
				}

				// 기본 정보
				$.each(values, function(key, val) {
					var target = result.find('[rel="'+ key +'"]');
					if ( !val || val === null || val === undefined || val === 'undefined' || val === '' ) {
						return true;
					}

					if ( $.inArray( key, ['title', 'nick_name', 'category_title', 'password'] ) !== -1 ) {
						target.text(val);
					} else if ( $.inArray( key, ['email_address', 'phone_number', 'message'] ) !== -1 ) {
						if ( key === 'email_address' ) {
							val = '<a href="mailto:'+ val +'">'+ val +'</a>';
						} else if ( key === 'phone_number' ) {
							var _value = val;
							if ( val.indexOf('+') !== -1 ) {
								var full_num = val.split(' '),
									country = full_num[0],
									domestic = full_num[1];
								country = country.replace(/\(|\)/g, '');
								if ( $.inArray(country.replace(/[^0-9]/g, ''), ['39', '378', '379']) !== -1 ) {
									domestic = domestic.replace(/^0/g, '');
								}
								val = '<a href="tel:'+ country + '-' + domestic +'">'+ val +'</a>';
							} else {
								val = '<a href="tel:'+ val +'">'+ _value +'</a>';
							}
						} else if ( key === 'message' ) {
							val = val.replace(/\n/g, '<br />');
						}
						target.html(val);
					}
				});

				// 확장변수
				var oDocument = document_result.oDocument;
				for ( let key = 0; key < oDocument.extra_vars.length; key++ ) {
					var idx = key + 1;
					var val = oDocument.extra_vars[key];
					var type = val.type;
					var value = val.value;
					if ( !value || value === null || value === undefined || value === 'undefined' || value === '' ) {
						continue;
					}

					var target = result.find('[rel="extra_vars'+ idx +'"]');

					if ( $.inArray( type, ['date', 'country', 'language', 'timezone', 'checkbox', 'kr_zip'] ) !== -1 ) {
						if ( $.inArray( type, ['country', 'language', 'timezone'] ) !== -1 ) {
							value = f.find('select[name="extra_vars'+ idx +'"] option[value="'+ value +'"]').text();
						} else if ( type === 'date' ) {
							value = value.replace(/(\d{4})(\d{2})(\d{2})/g, '$1-$2-$3');
						} else if ( type === 'checkbox' ) {
							value = value.replace('|@|', ', ');
						} else if ( type === 'kr_zip' ) {
							value = value.replace('|@|', ' ');
						}

						target.text(value);
					} else if ( $.inArray( type, ['homepage', 'url', 'email_address', 'textarea'] ) !== -1 ) {
						if ( type === 'homepage' || type === 'url' ) {
							value = '<a href="'+ value +'" target="_blank">'+ value +'</a>';
						} else if ( type === 'email_address' ) {
							value = '<a href="mailto:'+ value +'">'+ value +'</a>';
						} else if ( type === 'textarea' ) {
							value = value.replace(/\n/g, '<br />');
						}

						target.html(value);
					} else if ( $.inArray( type, ['tel', 'tel_v2', 'tel_intl', 'tel_intl_v2'] ) !== -1 ) {
						var value_html = value_text = '';
						if ( type === 'tel' ) {
							value_html = value_text = value.replace(/\|\@\|/g, '-');
						} else if ( type === 'tel_v2' ) {
							value_html = value_text = value;
						} else if ( type === 'tel_intl' || type === 'tel_intl_v2' ) {
							var val_arr = value.split('|@|');

							value_html += '+' + val_arr[0] + '-' + val_arr[1];
							value_text += '(+' + val_arr[0] + ') ' + val_arr[1];

							if ( type === 'tel_intl' ) {
								for ( let i = 2; i < val_arr.length - 1; i++ ) {
									value_html += '-' + val_arr[i];
									value_text += '-' + val_arr[i];
								};
							}
						}

						target.html('<a href="tel:'+ value_html +'">'+ value_text +'</a>');
					} else if ( type === 'file' ) {
						var file_result = await getFileInfo(value);
						target.html(file_result);
					} else {
						target.text(value);
					}
				};

				f.closest('.xe-widget-wrapper').hide();

				// 출력 요소 정리 : 빈 값이 존재하면 상위 요소 삭제
				result.find('[rel]').each(function() {
					var el = $(this);
					if ( el.html() === '' ) {
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
				$('html, body').animate({
					scrollTop: result.offset().top
				}, 400);
				setTimeout(function() {
					result.children('header').removeClass('is_blink');
				}, 3000);
			})();
		});
	}
}

async function getFileInfo(file_srl) {
	return new Promise((resolve, reject) => {
		$.exec_json('file.procFileGetList', {file_srls: file_srl}, function(response) {
			try {
				if ( !response.error ) {
					var file_info = response.file_list[0];
					resolve('<span><a href="/'+ file_info.download_url +'">'+ file_info.source_filename +'</a> ('+ file_info.human_file_size +')</span>');
				} else {
					resolve('파일 정보를 가져올 수 없습니다.');
				}
			} catch (error) {
				reject(error);
			}
		});
	});
}