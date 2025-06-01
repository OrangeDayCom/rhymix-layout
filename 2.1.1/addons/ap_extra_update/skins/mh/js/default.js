jQuery(document).ready(function($) {
	var U = $('.ap_extra_update');

	U.on('submit', function(e) {
		e.preventDefault();
		var target = $(this).find('.ap_extra_update_element[data-document_srl]');
		updateExtraInfo(target.attr('data-document_srl'), target.attr('rel'), U.serialize());
	});

	$(document).on('click', '.ap_extra_update_command .btns', function() {
		if ( $(this).index() === 1 ) {
			closeUpdateExtraModal();
		}
	});

	$(document).on('click', '.ap_extra_update', function(e) {
		if ( e.target === e.currentTarget )
		{
			closeUpdateExtraModal();
		}
	});
});

function showUpdateExtraModal(obj, document_srl, no, type) {
	var U = $('.ap_extra_update');
	if ( !type ) {
		U.find('.ap_extra_update_element[rel="'+ no +'"]').css('display', 'flex').attr('data-document_srl', document_srl);
	} else if ( type === 'category' ) {
		U.find('.ap_extra_update_element[rel="category_update"]').css('display', 'flex').attr('data-document_srl', document_srl);
	}
	U.css('display', 'flex');
	
	$(obj).attr('data-target-document_srl', document_srl).attr('data-target-update_no', no);
}

function closeUpdateExtraModal() {
	$('[data-target-update_no]').removeAttr('data-target-document_srl').removeAttr('data-target-update_no');

	var U = $('.ap_extra_update');
	U.hide();
	U.find('.ap_extra_update_element').removeAttr('data-document_srl').hide();
	U.find('.ap_extra_update_element').find(':input').each(function() {
		switch (this.type) {
			case 'password':
			case 'select-multiple':
			case 'select-one':
			case 'text':
			case 'textarea':
				$(this).val('');
				break;
			case 'checkbox':
			case 'radio':
				this.checked = false;
		}
	});
}

function updateExtraInfo(document_srl, rel, values) {
	var url = request_uri + 'index.php?' + values;
	var queries = window.XE.URI(url.replace(/\&amp\;/g, '&')).search(true);
	var params = {
		module_srl: queries.module_srl * 1,
		document_srl: document_srl * 1
	};
	if ( rel === 'category_update' ) {
		params.old_category_srl = $('[data-target-update_no]').attr('data-target-update_no') * 1;
		params.category_srl = queries.category * 1;
	} else {
		if ( queries['extra_vars' + rel + '[]'] ) {
			if ( $.isArray(queries['extra_vars' + rel + '[]']) ) {
				queries['extra_vars' + rel] = queries['extra_vars' + rel + '[]'].join('|@|');
			} else {
				queries['extra_vars' + rel] = queries['extra_vars' + rel + '[]'];
			}
		}
		params.var_idx = rel * 1;
		params.value = queries['extra_vars' + rel];
	}

	$.ajax({
		url: '../addons/ap_extra_update/update_extra_vars.php',
		dataType: 'json',
		data: params,
		success: function(data) {
			if ( !data || !data.valueHTML ) {
				alert('데이터를 수정할 수 없습니다');
			} else {
				$('[data-target-document_srl="'+ data.document_srl +'"]').html(data.valueHTML);
			}
			closeUpdateExtraModal();
			if ( data.old_category_srl && data.category_srl && (data.old_category_srl !== data.category_srl) ) {
				location.reload();
				// 원래 카테고리의 새로운 document_count : data.category_list[data.old_category_srl].document_count
				// 수정 카테고리의 새로운 document_count : data.category_list[data.category_srl].document_count
			}
		},
		complete: function() {
			$('.ap_extra_update').hide();
		},
		error: function(msg) {
			alert('서버와의 통신이 원활하지 않습니다');
			$('.ap_extra_update').hide();
			console.log(msg);
		}
	});
}