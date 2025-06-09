(function($){
	var acts = [
		'communication.procCommunicationAddFriend',
		'communication.procCommunicationDeleteFriend'
	];

	var _exec_json = window.jQuery.exec_json;
	window.jQuery.exec_json = function(action, params, callback_success, callback_error)
	{
		if($.inArray(action, acts) < 0)
		{
			_exec_json(action, params, callback_success, callback_error);
		}
		else
		{
			$.exec_json('elkhabook.getElkhabookConfig', {'target_srl': params.target_srl}, function(p){
				if(p.config.friend_srl)
				{
					if(!confirm(p.config.confirm_unfollow))
					{
						return;
					}
					params.friend_srl_list = p.config.friend_srl;
					action = 'communication.procCommunicationDeleteFriend';
				}
				else
				{
					if(!confirm(p.config.confirm_follow))
					{
						return;
					}
					action = 'communication.procCommunicationAddFriend';
				}
				_exec_json(action, params, function(p){
					callback_success(p);
					if(typeof(p.elkhabook_tpl_button) == 'string')
					{
						$('.elkhabook_tpl_button').html(p.elkhabook_tpl_button);
					}
				}, callback_error);
			});
		}
	};
	window.exec_json = window.jQuery.exec_json;

	var _exec_xml = window.exec_xml;
	window.exec_xml = function(module, act, params, callback_success, return_fields, callback_success_arg, fo_obj)
	{
		if($.inArray(module+'.'+act, acts) < 0)
		{
			return _exec_xml(module, act, params, callback_success, return_fields, callback_success_arg, fo_obj);
		}
		else
		{
			$.exec_json('elkhabook.getElkhabookConfig', {'target_srl': params.target_srl}, function(p){
				if(p.config.friend_srl)
				{
					if(!confirm(p.config.confirm_unfollow))
					{
						return;
					}
					params.friend_srl_list = p.config.friend_srl;
					action = 'communication.procCommunicationDeleteFriend';
				}
				else
				{
					if(!confirm(p.config.confirm_follow))
					{
						return;
					}
					action = 'communication.procCommunicationAddFriend';
				}
				_exec_xml(module, act, params, function(p){
					callback_success(p);
					if(typeof(p.elkhabook_tpl_button) == 'string')
					{
						$('.elkhabook_tpl_button').html(p.elkhabook_tpl_button);
					}
				}, return_fields, callback_success_arg, fo_obj);
			});
		}
	};
	window.$.exec_xml = window.exec_xml;

	// 친구 목록 더보기
	window.getElkhabookFriendList = function(a, member_srl, type, page)
	{
		$.exec_json('elkhabook.getElkhabookFriendList', {'target_member_srl':member_srl,'friend_type': type, 'friend_page': page}, function(p){
			if(p.error || typeof(p.getElkhabookFriendList) != 'string')
			{
				alert(p.message);
				return;
			}
			$(a).after(p.getElkhabookFriendList).remove();
		});
	};

	var go_to_page_regex;
	window.go_to_page = function(page_name, cur_page, lang)
	{
		var p = $.trim(prompt(lang, cur_page));
		if(p.match(/^[0-9]+$/))
		{
			go_to_page_regex = new RegExp('([\\&\\?]' + page_name + '=)[0-9]+(?:$|\\&)');
			if(location.href.match(go_to_page_regex))
			{
				location.href = location.href.replace(go_to_page_regex, '$1'+p);
			}
			else
			{
				location.href = current_url.setQuery(page_name, p);
			}
		}
		return false;
	};

	window.procElkhatalk3Delete = function(msg, member_srl, pk)
	{
		if(!confirm(msg))
		{
			return false;
		}
		$.exec_json('elkhatalk3.procElkhatalk3Delete2', {'no':pk,'_member_srl':member_srl}, function(p){
			if(!p.error)
			{
				setTimeout(function(){
					location.reload();
				}, 1);
			}
			else
			{
				alert(p.message);
			}
		});
		return false;
	};

	// 전체 공개, 비공개 등 개인 설정을 가능하게
	$(document).on('click', '.procElkhabookMyConfig', function(e){
		var params = {
			'name': $(this).data('name'),
			'contents': $(this).data('contents')
		};
		$.exec_json('elkhabook.procElkhabookMyConfig', params, function(p){
			if(typeof(p.procElkhabookMyConfig) == 'string')
			{
				$('.getElkhabookMyConfig[data-name]').each(function(){
					if($(this).data('name') === params.name)
					{
						this.outerHTML = p.procElkhabookMyConfig;
						return false;
					}
				});
			}
			setTimeout(function(){
				alert(p.message);
			}, 1);
		});
		return false;
	});

	// pjax 대응
	$(document).on('click', 'a.getElkhabookList[href]', function(e) {
		var params = {
			'board' : this.href.getQuery('board'),
			'doc_type' : this.href.getQuery('doc_type'),
			'member_srl' : window.elkhabook_member_srl
		};
		if(params.doc_type === '')
		{
			var matches = this.href.match(/\/([a-z]+)[^/]*$/); // 짧은 주소만 지원
			if(matches !== null)
			{
				params.doc_type = matches[1];
			}
		}
		var page = Number(this.href.getQuery('page'));
		var cpage = Number(this.href.getQuery('cpage'));
		var epage = Number(this.href.getQuery('epage'));
		if(page)
			params.page = page;
		else if(cpage)
			params.cpage = cpage;
		else if(epage)
			params.epage = epage;
		var url = this.href;

		var $getElkhabookList = $('.elkhabook > .getElkhabookList');
		params.show_count = $getElkhabookList.data('show_count');

		$.exec_json('elkhabook.getElkhabookList', params, function(p){
			if(typeof(p.getElkhabookList) == 'string')
			{
				history.pushState(p, '', url); // history.replaceState(null, url);
				$getElkhabookList.html(p.getElkhabookList);
			}
			else
			{
				alert(p.message);
			}
		});
		return false;
	});
	window.addEventListener('popstate', function(e) {
		var tpl = '';
		try {
			tpl = e.state.getElkhabookList;
		} catch (e) {
		}
		if(tpl.length)
		{
			$('.elkhabook > .sidebar_r.getElkhabookList').html(tpl);
		}
		else
		{
			location.reload();
		}
	});
})(jQuery);
