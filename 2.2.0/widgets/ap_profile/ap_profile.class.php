<?php
/**
 * @class ap_profile
 * @author cydemo (cydemo@gmail.com)
 * @brief 문서의 글쓴이 프로필을 출력하는 위젯
 * @version 0.2
 **/

class ap_profile extends WidgetHandler
{
	function proc($args)
	{
		// 기본 유형은 '내 프로필'
		if ( !$args->type ) $args->type = 'mine';
		$oMemberModel = getModel('member');

		// 글쓴이 프로필
		if ( $args->type === 'writer' )
		{
			// 문서 보기가 아닌 경우 위젯 출력 중지
			if ( !Context::get('document_srl') ) return;
			// 회원정보 추출
			$member_info = $oMemberModel->getMemberInfoByMemberSrl(Context::get('oDocument')->get('member_srl'));
		}
		// 내 프로필
		else if ( $args->type === 'mine' )
		{
			// 비로그인시 위젯 출력 중지
			if ( !Context::get('is_logged') ) return;
			// 회원정보 추출
			$member_info = Context::get('logged_info');
		}
		$member_srl = $member_info->member_srl;

		// 해당 회원이 타겟 그룹에 속하는지 확인. 무소속이면 서명만 출력
		$as_whole = false;
		if ( !$args->target_group )
		{
			$as_whole = true;
		}
		else
		{
			$defined_groups = array_map('trim', explode(',', $args->target_group));
			foreach ( array_keys($member_info->group_list) as $group_srl )
			{
				if ( in_array($group_srl, $defined_groups) )
				{
					$as_whole = true;
					break;
				}
			}
		}

		// 위젯 변수 정리
		$profile_info = new stdClass;
		$profile_info->widget_cache = $widget_cache = $args->widget_cache = 0;
		$profile_info->type = $args->type;
		$profile_info->tab = $args->tab ? $args->tab : 'Y';
		$profile_info->tab_order = $args->tab_order ? array_map('trim', explode(',', $args->tab_order)) : array('profile', 'contents', 'network', 'intro', 'history');
		$profile_info->as_whole = $as_whole;
			$header = $args->header;
		$profile_info->header = $header ? array_map('trim', explode(',', $header)) : array();
			$config = getModel('module')->getModuleConfig('member');
		$profile_info->profile_image_max_width = $args->image_width ? (int)$args->image_width : $config->profile_image_max_width;
		$profile_info->profile_image_max_height = $args->image_height ? (int)$args->image_height : $config->profile_image_max_height;

		// 출력옵션과 프로필 아이템에 캐시 가져오기
		$opt_key = 'ap_profile_opt:' . $member_srl;
		$item_key = 'ap_profile_item:' . $member_srl;
		$options = Rhymix\Framework\Cache::get($opt_key);
		$profile_item = Rhymix\Framework\Cache::get($item_key);

		// 캐시 없으면 데이터 새로 취득
		if ( !$options || !$profile_item )
		{
			// 위젯 아이템 정리 : 필수 항목(프로필 이미지, 닉네임, 회원번호, 서명란)
			$profile_item = new stdClass;
			$profile_item->member_srl = $member_srl;
			if ( strpos($header, 'profile_image') !== false ) $profile_item->profile_image = $member_info->profile_image;
			if ( strpos($header, 'nick_name') !== false ) $profile_item->nick_name = $member_info->nick_name;

			/**********
			 * 서명/소개 *
			 **********/
			$intro = $args->intro;
			$intro_title = $args->intro_title ? $args->intro_title : 'INTRO';
			if ( strpos($intro, 'signature') !== false ) $profile_item->signature = $oMemberModel->getSignature($member_srl);

			// 선택 그룹에 소속된 회원이면 위젯 설정에 따라 출력 정보 생성
			if ( $profile_info->as_whole )
			{
				// DB쿼리 관련 인수 정리
				$obj = new stdClass;
				$obj->member_srl = $member_srl;

				/***********
				 * 기본 프로필 *
				 ***********/
				$profile = $args->profile;
				$profile_title = $args->profile_title ? $args->profile_title : 'PROFILE';

				// 기본 옵션에 따라 변수 추가 배분
				if ( strpos($profile, 'user_id') !== false ) $profile_item->user_id = $member_info->user_id;
				if ( strpos($profile, 'user_name') !== false ) $profile_item->user_name = $member_info->user_name;

				if ( strpos($profile, 'email_address') !== false ) $profile_item->email_address = $member_info->email_address;
				if ( strpos($profile, 'homepage') !== false ) $profile_item->homepage = $member_info->homepage;
				if ( strpos($profile, 'blog') !== false ) $profile_item->blog = $member_info->blog;
				if ( strpos($profile, 'birthday') !== false ) $profile_item->birthday = $member_info->birthday;

if (strpos($profile, 'groups') !== false) {
    // $member_info->group_list가 배열인지 확인 후 처리
    if (isset($member_info->group_list) && is_array($member_info->group_list)) {
        $profile_item->groups = implode(', ', $member_info->group_list);
    } else {
        // group_list가 유효하지 않을 때의 처리
        $profile_item->groups = '';
    }
}

				if ( strpos($profile, 'regdate') !== false ) $profile_item->regdate = $member_info->regdate;
				if ( strpos($profile, 'last_login') !== false ) $profile_item->last_login = $member_info->last_login;

if (strpos($profile, 'extra_keys') !== false) {
    $_extra_keys = $oMemberModel->getJoinFormList();
    if (is_array($_extra_keys) && count($_extra_keys)) {
        $extra_keys = array();

        // $args->extra_keys를 배열로 변환
        $extra_keys_array = is_string($args->extra_keys) ? array_map('trim', explode(',', $args->extra_keys)) : (is_array($args->extra_keys) ? $args->extra_keys : []);

        foreach ($_extra_keys as $key => $val) {
            if (!in_array($val->column_name, $extra_keys_array)) {
                continue;
            }
            $extra_keys[$key] = $val;
        }
    }

    if (isset($extra_keys) && count($extra_keys)) {
        $profile_item->extra_vars = new stdClass;
        foreach ($extra_keys as $key => $val) {
            $profile_item->extra_vars->{$val->column_name} = array(
                'title' => $val->column_title,
                'value' => isset($member_info->{$val->column_name}) ? $member_info->{$val->column_name} : null,
            );
        }
    }
}


				/************
				 * 최근 글/댓글 *
				 ************/
				$contents = $args->contents;
				$contents_title = $args->contents_title ? $args->contents_title : 'CONTENTS';

				if ( strpos($contents, 'document') !== false || strpos($contents, 'comment') !== false )
				{
					if ( $args->module_srls )
					{
						if ( $args->module_srls_act !== 'E' ) $obj->module_srl = $args->module_srls;
						else $obj->exclude_module_srl = $args->module_srls;
					}
					// 최근 글 가져오기
					if ( strpos($contents, 'document') !== false )
					{
						// 위젯 변수 정리 : 썸네일 여부
						$profile_info->thumb = $args->thumb ? $args->thumb : 'Y';

						$obj->list_count = 5;
						$obj->page_count = 1;
						$profile_item->document = executeQueryArray('widgets.ap_profile.getDocumentListByMemberSrl', $obj)->data;
						if ( $profile_info->thumb === 'Y' && $profile_item->document )
						{
							// 위젯 변수 정리 : 썸네일 사이즈
							$profile_info->thumb_width = $args->thumb_width ? (int)$args->thumb_width : 80;
							$profile_info->thumb_height = $args->thumb_height ? (int)$args->thumb_height : 80;

							foreach ( $profile_item->document as $key => $val )
							{
								$profile_item->document[$key]->src = getModel('document')->getDocument($val->document_srl)->getThumbnail($profile_info->thumb_width, $profile_info->thumb_height, 'crop');
							}
						}
					}

					// 최근 댓글 가져오기
					if ( strpos($contents, 'comment') !== false )
					{
						$obj->list_count = 5;
						$obj->page_count = 1;
						$profile_item->comment = executeQueryArray('widgets.ap_profile.getCommentListByMemberSrl', $obj)->data;
					}
				}

				/************
				 * 네트워킹 현황 *
				 ************/
				$network = $args->network;
				$network_title = $args->network_title ? $args->network_title : 'NETWORK';

				if ( strpos($network, 'follows') !== false || strpos($network, 'follow_sum') !== false || strpos($network, 'followers') !== false || strpos($network, 'follower_sum') !== false )
				{
					// 위젯 변수 정리 : 명단 수, 구독 모듈 사용 여부, 구독자 감춤 여부, 구독자 명칭
					$profile_info->follow_limit = $args->follow_limit ? (int)$args->follow_limit : 1;
					$profile_info->mi_memberfollow_use = false;
					$profile_info->mi_memberfollower_hidden = false;
					$profile_info->follow_naming = $_naming = $args->follow_naming ? $args->follow_naming : 'nick_name';

					// 구독 모듈 사용 여부 체크 후 변수값 변경
					if ( is_object(getModule('memberfollow')) )
					{
						$mf_config = getModel('memberfollow')->getConfig();
						if ( $mf_config->use === 'Y' ) $profile_info->mi_memberfollow_use = true;
						if ( $mf_config->set_follower_u_hidden === "Y") $profile_info->mi_memberfollower_hidden = true;
					}

					// 팔로우 및 팔로윙 수 가져오기
					if ( strpos($network, 'follows') !== false || strpos($network, 'follow_sum') !== false )
					{
						// 구독 모듈 사용 여부에 따라 쿼리 실행
						if( !$profile_info->mi_memberfollow_use ) $follows_arr = executeQueryArray('widgets.ap_profile.getFriendsOfMember', $obj, array())->data;
						else $follows_arr = executeQueryArray('widgets.ap_profile.getFollow', $obj, array())->data;

						if( strpos($network, 'follows') !== false && !$profile_info->mi_memberfollower_hidden )
						{
							$_follows_arr = array();
							foreach ( $follows_arr as $i => $f )
							{
								if ( $i === $profile_info->follow_limit ) break;
								$_follows_arr[] = '<a href="#" onclick="return false;" class="nName member_' . $f->member_srl . '">' . $f->$_naming . '</a>';
							}
							$profile_item->follows = implode(', ', $_follows_arr);
						}

						if ( strpos($network, 'follow_sum') !== false ) $profile_item->follow_sum = count($follows_arr);
					}

					// 팔로우 및 팔로우 수 가져오기
					if ( strpos($network, 'followers') !== false || strpos($network, 'follower_sum') !== false )
					{
						// 구독 모듈 사용 여부에 따라 쿼리 실행
						if( !$profile_info->mi_memberfollow_use ) $followers_arr = executeQueryArray('widgets.ap_profile.getFriendsForMember', $obj, array())->data;
						else $followers_arr = executeQueryArray('widgets.ap_profile.getFollower', $obj, array())->data;

						if( strpos($network, 'followers') !== false && !$profile_info->mi_memberfollower_hidden )
						{
							$_followers_arr = array();
							foreach ( $followers_arr as $i => $f )
							{
								if ( $i === $profile_info->follow_limit ) break;
								$_followers_arr[] = '<a href="#" onclick="return false;" class="nName member_' . $f->member_srl . '">' . $f->$_naming . '</a>';
							}
							$profile_item->followers = implode(', ', $_followers_arr);
						}

						if ( strpos($network, 'follower_sum') !== false ) $profile_item->follower_sum = count($followers_arr);
					}
				}

				/**********
				 * 활동 내역 *
				 **********/
				$history = $args->history;
				$history_title = $args->history_title ? $args->history_title : 'HISTORY';

				// 게시글수, 댓글수
				if ( strpos($history, 'total_document_count') !== false ) $profile_item->total_document_count = getModel('document')->getDocumentCountByMemberSrl($member_srl);
				if ( strpos($history, 'total_comment_count') !== false ) $profile_item->total_comment_count = getModel('comment')->getCommentCountByMemberSrl($member_srl);

				// 추천 관련 총계
				if ( strpos($history, 'voting_document_count') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getVotingCountByMemberSrlInDocument', $obj);
					$profile_item->voting_document_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
				}
				if ( strpos($history, 'voting_comment_count') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getVotingCountByMemberSrlInComment', $obj);
					$profile_item->voting_comment_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
				}
				if ( strpos($history, 'voted_document_count') !== false || strpos($history, 'voted_document_sum') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getVotedCountForMemberSrlInDocument', $obj);
					if ( strpos($history, 'voted_document_count') !== false ) $profile_item->voted_document_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
					if ( strpos($history, 'voted_document_sum') !== false ) $profile_item->voted_document_sum = ( !$output->toBool() || !$output->data->count ) ? 0 : intval($output->data->sum);
				}
				if ( strpos($history, 'voted_comment_count') !== false || strpos($history, 'voted_comment_sum') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getVotedCountForMemberSrlInComment', $obj);
					if ( strpos($history, 'voted_comment_count') !== false ) $profile_item->voted_comment_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
					if ( strpos($history, 'voted_comment_sum') !== false ) $profile_item->voted_comment_sum = ( !$output->toBool() || !$output->data->count ) ? 0 : intval($output->data->sum);
				}

				// 비추천 관련 총계
				if ( strpos($history, 'blaming_document_count') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getBlamingCountByMemberSrlInDocument', $obj);
					$profile_item->blaming_document_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
				}
				if ( strpos($history, 'blaming_comment_count') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getBlamingCountByMemberSrlInComment', $obj);
					$profile_item->blaming_comment_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
				}
				if ( strpos($history, 'blamed_document_count') !== false || strpos($history, 'blamed_document_sum') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getBlamedCountForMemberSrlInDocument', $obj);
					if ( strpos($history, 'blamed_document_count') !== false ) $profile_item->blamed_document_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
					if ( strpos($history, 'blamed_document_sum') !== false ) $profile_item->blamed_document_sum = ( !$output->toBool() || !$output->data->count ) ? 0 : intval($output->data->sum);
				}
				if ( strpos($history, 'blamed_comment_count') !== false || strpos($history, 'blamed_comment_sum') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getBlamedCountForMemberSrlInComment', $obj);
					if ( strpos($history, 'blamed_comment_count') !== false ) $profile_item->blamed_comment_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
					if ( strpos($history, 'blamed_comment_sum') !== false ) $profile_item->blamed_comment_sum = ( !$output->toBool() || !$output->data->count ) ? 0 : intval($output->data->sum);
				}

				// 신고 관련 총계
				if ( strpos($history, 'accusing_document_count') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getAccusingCountByMemberSrlInDocument', $obj);
					$profile_item->accusing_document_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
				}
				if ( strpos($history, 'accusing_comment_count') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getAccusingCountByMemberSrlInComment', $obj);
					$profile_item->accusing_comment_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
				}
				if ( strpos($history, 'accused_document_count') !== false || strpos($history, 'accused_document_sum') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getAccusedCountForMemberSrlInDocument', $obj);
					if ( strpos($history, 'accused_document_count') !== false ) $profile_item->accused_document_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
					if ( strpos($history, 'accused_document_sum') !== false ) $profile_item->accused_document_sum = ( !$output->toBool() || !$output->data->count ) ? 0 : intval($output->data->sum);
				}
				if ( strpos($history, 'accused_comment_count') !== false || strpos($history, 'accused_comment_sum') !== false )
				{
					$output = executeQuery('widgets.ap_profile.getAccusedCountForMemberSrlInComment', $obj);
					if ( strpos($history, 'accused_comment_count') !== false ) $profile_item->accused_comment_count = ( !$output->toBool() || !$output->data->count ) ? 0 : $output->data->count;
					if ( strpos($history, 'accused_comment_sum') !== false ) $profile_item->accused_comment_sum = ( !$output->toBool() || !$output->data->count ) ? 0 : intval($output->data->sum);
				}
			}

			// 범주별 출력 옵션 정리
			$options = array();
			foreach ( $profile_info->tab_order as $val )
			{
				if ( !${$val} ) continue;
				$options[$val] = new stdClass;
				$options[$val]->title = ${$val . '_title'};
				$options[$val]->list = array_map('trim', explode(',', ${$val}));
			}

			// 캐시 적용
			$ttl = !$args->ttl ? '10s' : $args->ttl;
			if ( preg_match('/^([0-9\.]+)([smhd])$/i', $ttl, $matches) )
			{
				$multipliers = array('s' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400);
				$ttl = intval(floatval($matches[1]) * $multipliers[strtolower($matches[2])]);
			}
			Rhymix\Framework\Cache::set($opt_key, $options, $ttl);
			Rhymix\Framework\Cache::set($item_key, $profile_item, $ttl);
		}

		// 위젯 스킨으로 위젯 변수 전달
		Context::set('options', $options);
		Context::set('profile_info', $profile_info);
		Context::set('profile_item', $profile_item);

		// 언어 파일 로드
		Context::loadLang($this->widget_path . 'lang');

		// 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);

		// 템플릿 파일을 지정
		$tpl_file = 'profile';

		// 템플릿 컴파일
		$oTemplate = TemplateHandler::getInstance();
		$output = $oTemplate->compile($tpl_path, $tpl_file);
		return $output;
	}
}
?>