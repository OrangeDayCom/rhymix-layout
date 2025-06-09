<?php
class ElkhabookModel extends Elkhabook
{
	var array $count_info = []; // 팔로워, 팔로잉, 친구의 카운트 php 캐시
	var array $member_srl_groups = [[]/* $sequence => [1,2,3,4,5] */];
	var array $doc_types = [
		'following' => '팔로잉',
		'followers' => '팔로워',
		'friends' => '친구',
		'votes' => '추천',
		'scraps' => '스크랩'
	];
	var array $voted_counts = [];
	public function getElkhabookFriendButton(int $member_srl = 0) : \baseObject
	{
		$member_srl = $member_srl ?: (INT)Context::get('target_srl');
		$me_member_srl = is_object($logged_info = \Context::get('logged_info')) && $logged_info->member_srl ? $logged_info->member_srl : 0;
		$count_info = $this->getElkhabookCountInfo($member_srl);

		if($count_info['내팔로잉'] && $count_info['내팔로워'])
			$status = \Context::getLang('elkhabook_is_freind');
		else if($count_info['내팔로잉'])
			$status = \Context::getLang('elkhabook_followed'); // 내가 상대를 친추했음: 팔로우하고 있습니다.
		else if($count_info['내팔로워'])
			$status = \Context::getLang('elkhabook_freind'); // 상대가 나를 친추했음: 친구가 됩니다.
		else
			$status = \Context::getLang('elkhabook_follow'); // 팔로우 합니다.

		$tpl = sprintf(' <a href="javascript:;" onclick="jQuery.exec_json(%s,%s, function(p){});" class="tooltip tooltip-follow%s"> <span class="label">%s</span> <span class="num follow_count">%d</span> </a> ',
			"'communication.procCommunicationAddFriend'",
			"{target_srl:$member_srl}",
			$me_member_srl == $member_srl || $count_info['내팔로잉'] ? ' on' : '',
			$status,
			$count_info['팔로워'] + $count_info['친구']
		);
		$this->add('tpl_button', $tpl);
		return $this;
	}
	public function getElkhabookConfig() : \baseObject
	{
		$config = $this->getConfig();
		$target_srl = (INT)\Context::get('target_srl');
		$logged_info = \Context::get('logged_info');

		$args = new \stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->target_srl = $target_srl;
		$args->list_count = 1;
		$data = executeQuery('elkhabook.getFriends', $args)->data;

		$elkhabook_config = new \stdClass();
		$elkhabook_config->friend_srl = is_object($data) && isset($data->friend_srl) && $data->friend_srl ? $data->friend_srl : 0;
		$elkhabook_config->confirm_follow = sprintf(\Context::getLang('elkhabook_confirm_follow'), $config->follow_point);
		$elkhabook_config->confirm_unfollow = sprintf(\Context::getLang('elkhabook_confirm_unfollow'), $config->follow_point);
		$this->add('config', $elkhabook_config);
		return $this;
	}
	public function getElkhabookCountInfo(INT $member_srl) : array
	{
		if($member_srl <= 0)
		{
			return ['팔로워' => 0, '팔로잉'=> 0, '친구' => 0, '내팔로워' => 0, '내팔로잉' => 0];
		}
		if(!isset($this->count_info[$member_srl]))
		{
			$member_srl2 = is_object($logged_info = \Context::get('logged_info')) && $logged_info->member_srl ? $logged_info->member_srl : 0;
			$oDB = DB::getInstance();
			$result = $oDB->query("
			SELECT
				COUNT(CASE WHEN f.target_srl = m.member_srl THEN 1 END) - FLOOR(COUNT(CASE WHEN f2.member_srl IS NOT NULL THEN 1 END) / 2) AS '팔로워',
				COUNT(CASE WHEN f.member_srl = m.member_srl THEN 1 END) - FLOOR(COUNT(CASE WHEN f2.member_srl IS NOT NULL THEN 1 END) / 2) AS '팔로잉',
				FLOOR(COUNT(CASE WHEN f2.member_srl IS NOT NULL THEN 1 END) / 2) AS '친구',
				IF(? > 0, COUNT(CASE WHEN (f.member_srl = m.member_srl AND f.target_srl = m.member_srl2) THEN 1 END), 0) AS '내팔로워',
				IF(? > 0, COUNT(CASE WHEN (f.target_srl = m.member_srl AND f.member_srl = m.member_srl2) THEN 1 END), 0) AS '내팔로잉'
			FROM (SELECT ? `member_srl`, ? `member_srl2`) AS `m`
			LEFT JOIN `member_friend` AS `f`
				ON f.member_srl = m.member_srl
				OR f.target_srl = m.member_srl
			LEFT JOIN `member_friend` AS `f2`
				ON f2.member_srl = f.target_srl
				AND f2.target_srl = f.member_srl;", $member_srl2, $member_srl2, $member_srl, $member_srl2);
			$this->count_info[$member_srl] = (array)$oDB->fetch($result);
		}
		return $this->count_info[$member_srl];
	}
	public function getElkhabookFriendList(INT $member_srl = 0) : \baseObject
	{
		$friend_list = ['팔로워' => [], '팔로잉'=> [], '친구' => []];
		$type = isset($friend_list[\Context::get('friend_type')]) ? (STRING)\Context::get('friend_type') : '';
		$member_srl = $member_srl ?: (INT)Context::get('target_member_srl') ?: 0;
		$page = abs((INT)Context::get('friend_page')) ?: 1;
		$friend_more = ['팔로워' => FALSE, '팔로잉'=> FALSE, '친구' => FALSE];
		$list_count = 20;
		$queries = [];
		$params = [];
		$member_not_exists = [];
		if($member_srl && is_object($member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl)) && $member_info->member_srl == $member_srl)
		{
			if($type === '')
			{
				$count_info = $this->getElkhabookCountInfo($member_srl);

				if($count_info['팔로잉'])
				{
					$queries[] = "(SELECT f.target_srl AS `member_srl`, m.nick_name AS `nick_name`, '팔로잉' AS `type`
					FROM `member_friend` AS `f`
					LEFT JOIN `member_friend` AS `f2`
						ON f2.target_srl = f.member_srl
						AND f2.member_srl = f.target_srl
					LEFT JOIN `member` AS `m`
						ON m.member_srl = f.target_srl
					WHERE f.member_srl = ?
						AND f2.target_srl IS NULL
					ORDER BY f.friend_srl DESC
					LIMIT ?, ?)";
					$params = array_merge($params, [$member_srl, ($page - 1) * $list_count, $list_count]);
					$friend_more['팔로잉'] = $count_info['팔로잉'] > $page * $list_count;
				}
				if($count_info['팔로워'])
				{
					$queries[] = "(SELECT f.member_srl AS `member_srl`, m.nick_name AS `nick_name`, '팔로워' AS `type`
					FROM `member_friend` AS `f`
					LEFT JOIN `member_friend` AS `f2`
						ON f2.target_srl = f.member_srl
						AND f2.member_srl = f.target_srl
					LEFT JOIN `member` AS `m`
						ON m.member_srl = f.member_srl
					WHERE f.target_srl = ?
						AND f2.target_srl IS NULL
					ORDER BY f.friend_srl DESC
					LIMIT ?, ?)";
					$params = array_merge($params, [$member_srl, ($page - 1) * $list_count, $list_count]);
					$friend_more['팔로워'] = $count_info['팔로워'] > $page * $list_count;
				}
				if($count_info['친구'])
				{
					$queries[] = "(SELECT f.member_srl AS `member_srl`, m.nick_name AS `nick_name`, '친구' AS `type`
					FROM `member_friend` AS `f`
					INNER JOIN `member_friend` AS `f2`
						ON f2.target_srl = f.member_srl
						AND f2.member_srl = f.target_srl
					LEFT JOIN `member` AS `m`
						ON m.member_srl = f.member_srl
					WHERE f.target_srl = ?
					ORDER BY f.friend_srl DESC
					LIMIT ?, ?)";
					$params = array_merge($params, [$member_srl, ($page - 1) * $list_count, $list_count]);
					$friend_more['친구'] = $count_info['친구'] > $page * $list_count;
				}

				if(count($queries))
				{
					$oDB = DB::getInstance();
					$result = $oDB->query(implode(' UNION ALL ', $queries), $params);
					$data = $oDB->fetch($result);
					if(is_object($data) && isset($data->member_srl))
					{
						$data = [$data];
					}

					foreach($data as $val)
					{
						if(!is_string($val->nick_name) || $val->nick_name == '')
						{
							$member_not_exists[] = $val->member_srl;
							$val->nick_name = '?';
						}
						$friend_list[$val->type][] = $val;
					}
				}
			}
			else
			{
				$args = new \stdClass();
				$args->list_count = $list_count;
				$args->page = $page;
				if($type == '팔로잉')
				{
					$args->member_srl = $member_srl;
					$output = executeQueryArray('elkhabook.getFriendListFollowing', $args, ['f.target_srl member_srl', 'm.nick_name']);
				}
				else if($type == '팔로워')
				{
					$args->target_srl = $member_srl;
					$output = executeQueryArray('elkhabook.getFriendListFollower', $args, ['f.member_srl', 'm.nick_name']);
				}
				else if($type == '친구')
				{
					$args->member_srl = $member_srl;
					$output = executeQueryArray('elkhabook.getFriendListFriend', $args, ['f.member_srl', 'm.nick_name']);
				}
				foreach($output->data as $val)
				{
					$val->type = $type;
					if(!is_string($val->nick_name) || $val->nick_name == '')
					{
						$member_not_exists[] = $val->member_srl;
						$val->nick_name = '?';
					}
				}
				$friend_list = [$type => $output->data];
				$friend_more[$type] = $output->page_navigation->cur_page < $output->page_navigation->total_page;
			}
		}

		$this->add('friend_more', $friend_more);
		$this->add('friend_list', $friend_list);
		$this->add('friend_page', $page);
		$config = $this->getConfig();
		$oTemplateHandler = \TemplateHandler::getInstance();
		$tpl = $oTemplateHandler->compile($this->module_path . "skins/$config->skin", __FUNCTION__);
		$this->add(__FUNCTION__, $tpl);
		return $this;
	}

	public function voted_count($member_srl = 0) : int
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			if(is_object($logged_info) && $logged_info->member_srl)
			{
				$member_srl = $logged_info->member_srl;
			}
		}
		if(!$member_srl)
		{
			return 0;
		}
		// voted_count 추가
		if(!isset($this->voted_counts[$member_srl]))
		{
			$args = new \stdClass();
			$args->member_srl = $member_srl;
			$voted_count = executeQuery('elkhabook.getVotedCount', $args)->data->voted_count;

			$this->voted_counts[$member_srl] = $voted_count > 0 ? $voted_count : 0;
		}
		return $this->voted_counts[$member_srl];
	}

	public function level($member_srl = 0) : array
	{
		if(!$member_srl)
		{
			return [0,0];
		}
		$point = \PointModel::getPoint($member_srl);
		$point_config = \ModuleModel::getModuleConfig('point');
		$level = \PointModel::getLevel($point, $point_config->level_step);
		return [(INT)$point, $level, $point_config->point_name];
	}

	public function getCategoryCmt(INT $member_srl) : array
	{
		static $doc_list = [];
		if(!isset($doc_list[$member_srl]))
		{
			$doc_list[$member_srl] = [
				'__default' => [
					'docs' => []
				],
				'__module_srls' => []
			];

			$config = $this->getConfig();
			if(!count($config->doc_list))
			{
				return $doc_list[$member_srl];
			}
			$args = new \stdClass();
			$args->member_srl = $member_srl < 0 && isset($this->member_srl_groups[abs($member_srl)]) ? $this->member_srl_groups[abs($member_srl)] : $member_srl;
			$data = executeQueryArray('elkhabook.getCategoryCmt', $args)->data;
			foreach($data as $val)
			{
				$continue = false;
				if(is_string($val->mid) && strlen($val->mid) && !in_array($val->module_srl ?? 0, $this->_excludeModuleSrls() ?? []))
				{
					foreach($config->doc_list as $regex => $v)
					{
						if(strlen($regex))
						{
							if(!isset($doc_list[$member_srl][$regex]))
							{
								$doc_list[$member_srl][$regex] = $v;
								$doc_list[$member_srl][$regex]['docs'] = [];
							}
							if(preg_match($regex, $val->mid))
							{
								$doc_list[$member_srl]['__module_srls'][ $val->module_srl ] = $val->count;
								$count = $val->count * 1000;
								while( isset($doc_list[$member_srl][$regex]['docs'][$count]) )
								{
									$count++;
								}
								$doc_list[$member_srl][$regex]['docs'][$count] = $val;

								$continue = true;
								break;
							}
						}
					}
				}
				if($continue)
				{
					continue;
				}

				$count = $val->count * 1000;
				while( isset($doc_list[$member_srl]['__default']['docs'][$count]) )
				{
					$count++;
				}
				$doc_list[$member_srl]['__default']['docs'][$count] = $val;
			}
		}
		return $doc_list[$member_srl];
	}
	public function getCategory(INT $member_srl) : array
	{
		static $doc_list = [];
		if(!isset($doc_list[$member_srl]))
		{
			$doc_list[$member_srl] = [
				'__default' => [
					'docs' => []
				],
				'__module_srls' => []
			];

			$config = $this->getConfig();
			if(!count($config->doc_list))
			{
				return $doc_list[$member_srl];
			}
			$args = new \stdClass();
			$args->member_srl = $member_srl < 0 && isset($this->member_srl_groups[abs($member_srl)]) ? $this->member_srl_groups[abs($member_srl)] : $member_srl;
			$data = executeQueryArray('elkhabook.getCategory', $args)->data;
			foreach($data as $val)
			{
				$continue = false;
				if(is_string($val->mid) && strlen($val->mid) && !in_array($val->module_srl ?? 0, $this->_excludeModuleSrls() ?? []))
				{
					foreach($config->doc_list as $regex => $v)
					{
						if(strlen($regex))
						{
							if(!isset($doc_list[$member_srl][$regex]))
							{
								$doc_list[$member_srl][$regex] = $v;
								$doc_list[$member_srl][$regex]['docs'] = [];
							}
							if(preg_match($regex, $val->mid))
							{
								$doc_list[$member_srl]['__module_srls'][ $val->module_srl ] = $val->count;
								$count = $val->count * 1000;
								while( isset($doc_list[$member_srl][$regex]['docs'][$count]) )
								{
									$count++;
								}
								$doc_list[$member_srl][$regex]['docs'][$count] = $val;


								$continue = true;
								break;
							}
						}
					}
				}
				if($continue)
				{
					continue;
				}

				$count = $val->count * 1000;
				while( isset($doc_list[$member_srl]['__default']['docs'][$count]) )
				{
					$count++;
				}
				$doc_list[$member_srl]['__default']['docs'][$count] = $val;
			}
		}
		return $doc_list[$member_srl];
	}
	public function content_grant(int $member_srl, string $contents) : bool
	{
		if($contents == 'open')
		{
			return true;
		}
		$logged_info = \Context::get('logged_info');
		$member_srl_me = $logged_info->member_srl ?? 0;
		if($member_srl == $member_srl_me)
		{
			return true;
		}
		if($contents == 'logged')
		{
			return $member_srl_me > 0;
		}
		else if($contents != 'hide' && $member_srl_me > 0)
		{
			static $contents_list = [];
			if(!isset($contents_list[$member_srl]))
			{
				$args = [
					'member_srl' => [$member_srl, $member_srl_me],
					'target_srl' => [$member_srl, $member_srl_me]
					//'list_count' => ?
				];
				$data = executeQueryArray('elkhabook.getFriends', $args, ['member_srl', 'target_srl'])->data;
				$contents_list[$member_srl] = [];
				foreach($data as $val)
				{
					// 자기자신을 추가한 경우가 있나?
					if($val->member_srl == $val->target_srl)
					{
						continue;
					}
					if($val->member_srl == $member_srl_me)
					{
						$contents_list[$member_srl]['follower'] = true;
					}
					if($val->target_srl == $member_srl_me)
					{
						$contents_list[$member_srl]['following'] = true;
					}
				}
				if(isset($contents_list[$member_srl]['following']) && isset($contents_list[$member_srl]['follower']))
				{
					$contents_list[$member_srl]['friends'] = true;
				}
			}
			if(isset($contents_list[$member_srl][$contents]))
			{
				return true;
			}
		}
		return false;
	}
	public function content_groups(int $member_srl, \stdClass $module_info, array $extra_info) : array
	{
		$config = $this->getConfig();
		if(in_array($module_info->module_srl ?? 0, $this->_excludeModuleSrls() ?? []))
		{
			return [];
		}
		list($doc_type, $show_count, $page, $cpage, $epage) = $extra_info;
		$output = [];

		foreach($config->content_groups as $arr)
		{
			$idx = '';
			if(strlen($type = $this->doc_types[$doc_type] ?? '')) // | 친구 | 팔로잉 | 팔로워
			{
				if(strpos($arr[0], $type) === false)
				{
					continue;
				}
				$idx = $doc_type;
			}
			else if(strlen($doc_type))
			{
				continue;
			}
			else
			{
				foreach($this->doc_types as $key => $val)
				{
					if(strpos($arr[0], $val) !== false)
					{
						$type = $val;
						$idx = $key;
						break;
					}
				}
			}

			$my_config = $this->my_config($member_srl, $arr);
			if(strpos($arr[0], '문서') !== false)
			{
				if($epage || $cpage)
				{
					continue;
				}

				if($page && !strlen($doc_type) && strlen($idx))
				{
					continue;
				}

				if($module_info->module_srl ?? 0)
				{
					$label = '<a href="'.getUrl('','mid',$module_info->mid).'">'.$this->getBrowserTitle($module_info->module_srl).'</a> ';
				}
				else
				{
					$label = \Context::replaceUserLang($arr[1]);
				}

				$class = strlen($idx) ? "document {$idx}" : 'document';

				if($show_count-- <= 0)
				{
					$doc_list = $this->createObject();
					$doc_list->data = [];
					$doc_list->page_navigation = new \PageHandler(0, 0, 0);
				}
				else if(strlen($my_config[0] ?? '') && $this->content_grant($member_srl, $my_config[0]))
				{
					$doc_list = $this->getDocList($member_srl, $page, $module_info->module_srl ?? 0, $type);
				}
				else
				{
					$msg = \Context::getLang('msg_not_permitted');// . ' [' . \Context::getLang("elkhabook_contents_{$my_config[0]}") . ']';
					$doc_list = $this->createObject(-1, $msg);
					$doc_list->data = [];
					$doc_list->page_navigation = new \PageHandler(0, 0, 0);
				}

				$output[$class] = [
					$label,
					$doc_list,
					'page',
					[$idx, $arr[0]],
					$my_config //$arr[3]
				];
			}
			else if(strpos( $arr[0], '댓글') !== false)
			{
				if($epage || $page)
				{
					continue;
				}

				if($cpage && !strlen($doc_type) && strlen($idx))
				{
					continue;
				}

				if($module_info->module_srl ?? 0)
				{
					$label = '<a href="'.getUrl('','mid',$module_info->mid).'">'.$this->getBrowserTitle($module_info->module_srl).'</a> ' . \Context::getLang('comment');
				}
				else
				{
					$label = \Context::replaceUserLang($arr[1]);
				}

				$class = strlen($idx) ? "comment {$idx}" : 'comment';

				if($show_count-- <= 0)
				{
					$doc_list = $this->createObject();
					$doc_list->data = [];
					$doc_list->page_navigation = new \PageHandler(0, 0, 0);
				}
				else if(strlen($my_config[0] ?? '') && $this->content_grant($member_srl, $my_config[0]))
				{
					$doc_list = $this->getCmtList($member_srl, $cpage, $module_info->module_srl ?? 0, $type);
				}
				else
				{
					$msg = \Context::getLang('msg_not_permitted');// . ' [' . \Context::getLang("elkhabook_contents_{$my_config[0]}") . ']';
					$doc_list = $this->createObject(-1, $msg);
					$doc_list->data = [];
					$doc_list->page_navigation = new \PageHandler(0, 0, 0);
				}

				$output[$class] = [
					$label,
					$doc_list,
					'cpage',
					[$idx, $arr[0]],
					$my_config //$arr[3]
				];
			}
			else if($arr[0] == '채팅' && !$cpage && !$page && count($config->elkhatalk_rooms))
			{
				if($show_count-- <= 0)
				{
					$doc_list = $this->createObject();
					$doc_list->data = [];
					$doc_list->page_navigation = new \PageHandler(0, 0, 0);
				}
				else if(strlen($my_config[0] ?? '') && $this->content_grant($member_srl, $my_config[0]))
				{
					$doc_list = $this->getChatList($member_srl, $epage);
				}
				else
				{
					$msg = \Context::getLang('msg_not_permitted');// . ' [' . \Context::getLang("elkhabook_contents_{$my_config[0]}") . ']';
					$doc_list = $this->createObject(-1, $msg);
					$doc_list->data = [];
					$doc_list->page_navigation = new \PageHandler(0, 0, 0);
				}
				$output['chat'] = [
					\Context::replaceUserLang($arr[1]),
					$doc_list,
					'epage',
					['', $arr[0]],
					$my_config //$arr[3]
				];
			}
		}

		return $output;
	}
	public function getElkhabookList() : \baseObject
	{
		$_member_info = $this->_member_info();
		\Context::set('_member_info', $_member_info);
		if(!$_member_info->member_srl)
		{
			return $this->stop('msg_not_exists_member');
		}

		// html 처리했던 코드 이식하기.
		$params = [];
		$params['elkhabook_config'] = $config = $this->getConfig();
		$params['module_srl'] = 0;
		$params['page'] = $page = (INT)\Context::get('page');
		$params['cpage'] = $cpage = (INT)\Context::get('cpage');
		$params['epage'] = $epage = (INT)\Context::get('epage');
		$params['_module_info'] = null;
		$params['oModel'] = $this;
		$params['doc_type'] = $doc_type = (STRING)\Context::get('doc_type');

		if(strlen($board = (STRING)\Context::get('board')) && !in_array($board, $config->exclude_list ?? []))
		{
			$module_info = \ModuleModel::getModuleInfoByMid($board);
			$match = false;
			foreach($config->doc_list as $regex => $val)
			{
				if(preg_match($regex, $board))
				{
					$match = TRUE;
					break;
				}
			}
			if($match && is_object($module_info) && ($module_info->module_srl ?? 0) && $module_info->mid == $board)
			{
				$params['_module_info'] = $module_info;
				$params['module_srl'] = $module_info->module_srl;
			}
			else
			{
				$board = '';
			}
		}
		$params['board'] = $board;
		$params['output'] = $this->content_groups($_member_info->member_srl, $params['_module_info'] ?: new \stdClass, [$doc_type, (INT)\Context::get('show_count') ?: 99, $page, $cpage, $epage]);

		foreach($params as $key => $val)
		{
			\Context::set($key, $val);
		}

		$oTemplateHandler = \TemplateHandler::getInstance();
		$tpl = $oTemplateHandler->compile($this->module_path . "skins/{$config->skin}", __FUNCTION__);
		$this->add(__FUNCTION__, $tpl);

		// 굳이 트리거로 분리한 이유: 다른 모듈에서 어떤 식으론가 레벨아이콘 같은 대응을 원할 수 있으므로, 이 코드를 참고해서 트리거를 만들 수 있다.
		// 트리거 선언을 유의할 것: 이 예제는 html view(=dispElkhabookIndex) 에 대응하지 않으므로 ajax 일 때만.
		$act = __FUNCTION__;
		getController('module')->addTriggerFunction("act:elkhabook.{$act}", 'after', function($oModule) use($config, $act) {
			if($config->point_level_icon == 'off')
			{
				return;
			}
			$addon_config = \AddonModel::getAddonConfig('point_level_icon') ?: new \stdClass;
			if($config->point_level_icon == 'auto')
			{
				$type = Rhymix\Framework\UA::isMobile() ? 'mobile' : 'pc';
				$site_srl = \Context::get('site_module_info')->site_srl ?? 0;
				$cache_key = "object:{$act}_{$type}_{$site_srl}";
				$cache_val = Rhymix\Framework\Cache::get($cache_key);
				if(!is_object($cache_val) || !isset($cache_val->activated))
				{
					$oAddonAdminModel = \AddonAdminModel::getInstance();
					$cache_val = new \stdClass();
					$cache_val->activated = $oAddonAdminModel->isActivatedAddon('point_level_icon', $site_srl, $type);
					Rhymix\Framework\Cache::set($cache_key, $cache_val, 600);
				}
				if(!$cache_val->activated)
				{
					return;
				}
			}

			require_once \RX_BASEDIR . 'addons/point_level_icon/point_level_icon.lib.php';
			$temp_output = preg_replace_callback('!<(div|span|a)([^\>]*)member_([0-9\-]+)([^\>]*)>(.*?)\<\/(div|span|a)\>!is', function($matches) use($addon_config) {
				return pointLevelIconTrans($matches, $addon_config);
			}, $oModule->get($act));
			if(strlen($temp_output))
			{
				$oModule->add($act, $temp_output);
			}
		});
		return $this;
	}
	public function sequence(array $member_srls) : int
	{
		$sequence = count($this->member_srl_groups);
		while(isset($this->member_srl_groups[$sequence]))
		{
			$sequence++;
		}
		$this->member_srl_groups[$sequence] = $member_srls;
		return $sequence * -1;
	}
	public function getFriendListByType(int $member_srl, string $type) : array
	{
		static $data = [];

		if(!isset($data[$member_srl]))
		{
			$data[$member_srl] = [$type => []];
		}
		else if(!isset($data[$member_srl][$type]))
		{
			$data[$member_srl][$type] = [];
		}
		else
		{
			return $data[$member_srl][$type];
		}
		// 혹시 카운트 변수가 이미 0 으로 캐싱되었다면.
		if(isset($this->count_info[$member_srl][$type]) && !(INT)$this->count_info[$member_srl][$type])
		{
			// 팔로워, 팔로잉은 친구 수도 검증.
			if($type == '친구' || (isset($this->count_info[$member_srl]['친구']) && !(INT)$this->count_info[$member_srl]['친구']))
			{
				return [];
			}
		}
		if($type == '친구')
		{
			$_data = executeQueryArray('elkhabook.getFriendListFriend', ['member_srl' => $member_srl], ['f.member_srl'])->data;
		}
		else if($type == '팔로잉')
		{
			$_data = executeQueryArray('elkhabook.getFollowingList', ['member_srl' => $member_srl], ['f.target_srl member_srl'])->data;
		}
		else if($type == '팔로워')
		{
			$_data = executeQueryArray('elkhabook.getFollowerList', ['target_srl' => $member_srl], ['f.member_srl'])->data;
		}
		else
		{
			return [];
		}
		foreach($_data as $val)
		{
			$data[$member_srl][$type][] = (INT)$val->member_srl;
		}

		return $data[$member_srl][$type];
		/*if(count($types) == 1)
		{
			return $data[$member_srl][ array_first($types) ];
		}
		return array_unique(array_merge(
			in_array('팔로잉', $types) ? $data[$member_srl]['팔로잉'] : [],
			in_array('팔로워', $types) ? $data[$member_srl]['팔로워'] : [],
			in_array('친구', $types) ? $data[$member_srl]['친구'] : []
		));*/
	}
	public function getVoteList(/*array|*/object $args) : \baseObject
	{
		/*$args = [
			'member_srl' => $obj[0],
			'page' => $obj[1]
		];
		if($obj[2] ?? 0)
		{
			$args['module_srl'] = $obj[2];
		}*/
		$output = executeQueryArray('elkhabook.getVoteList', $args);
		$data = [];
		foreach($output->data as $key => $attribute)
		{
			$data[$key] = new \DocumentItem();
			$data[$key]->setAttribute($attribute, false);
		}
		$output->data = $data;
		return $output;
	}
	public function getScrapList(object $args) : \baseObject
	{
		$output = executeQueryArray('elkhabook.getScrapList', $args);
		$data = [];
		foreach($output->data as $key => $attribute)
		{
			$data[$key] = new \DocumentItem();
			$data[$key]->setAttribute($attribute, false);
		}
		$output->data = $data;
		return $output;
	}
	public function getDocList(INT $member_srl, INT $page, INT $module_srl = 0, string $type = '') : \baseObject
	{
		if($type == '추천' || $type == '스크랩')
		{
			$obj = new \stdClass();
			$obj->member_srl = $member_srl;
			$sequence = $member_srl;
		}
		else
		{
			if(strlen($type))
			{
				$member_srls = $this->getFriendListByType($member_srl, $type);

				if(!count($member_srls))
				{
					$output = $this->createObject();
					$output->data = [];
					$output->page_navigation = new \PageHandler(0, 0, 0);
					return $output;
				}
				$sequence = $this->sequence($member_srls);
			}
			else
			{
				$member_srls = [abs($member_srl)];
				$sequence = $member_srl;
			}
			$obj = new \stdClass();
			$obj->member_srl = $member_srls;
			$obj->sort_index = 'list_order';
			$obj->order_type = 'ASC';
		}

		$config = $this->getConfig();
		$obj->list_count = $config->list_count;
		$obj->page = $page ?: 1;

		$module_srls = array_keys($this->getCategory($sequence)['__module_srls']);
		if($module_srl)
		{
			if(in_array($module_srl, $module_srls))
			{
				$obj->module_srl = $module_srl;
			}
			else
			{
				$output = $this->createObject();
				$output->data = [];
				$output->page_navigation = new \PageHandler(0, 0, 0);
				return $output;
			}
		}
		else if(count($module_srls))
		{
			$obj->module_srl = implode(',', $module_srls);
		}

		if($type == '추천')
		{
			return $this->getVoteList($obj);
		}
		else if($type == '스크랩')
		{
			return $this->getScrapList($obj);
		}
		else
		{
			//$columns = array('document_srl','title','content','regdate','module_srl');
			return \DocumentModel::getDocumentList($obj, FALSE, FALSE/*, $columns*/);
		}
	}

	public function getCmtList(INT $member_srl, INT $page = 1, INT $module_srl = 0, string $type = '') : \baseObject
	{
		if(strlen($type))
		{
			$member_srls = $this->getFriendListByType($member_srl, $type);

			if(!count($member_srls))
			{
				$output = $this->createObject();
				$output->data = [];
				$output->page_navigation = new \PageHandler(0, 0, 0);
				return $output;
			}
			$sequence = $this->sequence($member_srls);
		}
		else
		{
			$member_srls = [abs($member_srl)];
			$sequence = $member_srl;
		}

		//$oModel = getModel('comment');
		//$comments = $oModel->getCommentListByMemberSrl($member_srl, ['comment_srl'], 1, FALSE, $list_count);
		$config = $this->getconfig();
		$args = new stdClass();
		$args->member_srl = $member_srls;//abs($member_srl);
		$args->list_count = $config->list_count;
		$args->page = $page ?: 1;

		$module_srls = array_keys($this->getCategoryCmt($sequence)['__module_srls']);
		if($module_srl)
		{
			if(in_array($module_srl, $module_srls))
			{
				$args->s_module_srl = [$module_srl];
			}
			else
			{
				$output = $this->createObject();
				$output->data = [];
				$output->page_navigation = new \PageHandler(0, 0, 0);
				return $output;
			}
		}
		else if(count($module_srls))
		{
			$args->s_module_srl = $module_srls;
		}

		$args->s_member_srl = $args->member_srl;
		unset($args->member_srl);
		$output = executeQueryArray('elkhabook.getCommentList', $args);

		require_once(_XE_PATH_.'modules/comment/comment.item.php');
		foreach($output->data as $key => $comment)
		{
			$oComment = new commentItem(0);
			$oComment->setAttribute($comment);
			$oComment->oDocument = \DocumentModel::getDocument($oComment->get('document_srl'), FALSE, FALSE);
			$output->data[$key] = $oComment;
		}
		return $output;
	}
	public function getBrowserTitle(int $module_srl)
	{
		static $title_list = [];
		if(!isset($title_list[$module_srl]))
		{
			$module_info = \ModuleModel::getModuleInfoByModuleSrl($module_srl);
			if(is_object($module_info) && $module_info->module_srl == $module_srl)
			{
				$title_list[$module_srl] = \Context::replaceUserLang($module_info->browser_title);
			}
			else
			{
				$title_list[$module_srl] = '?';
			}
		}
		return $title_list[$module_srl];
	}
	public function getMid(int $module_srl)
	{
		static $mid_list = [];
		if(!isset($mid_list[$module_srl]))
		{
			$module_info = \ModuleModel::getModuleInfoByModuleSrl($module_srl);
			if(is_object($module_info) && $module_info->module_srl == $module_srl)
			{
				$mid_list[$module_srl] = $module_info->mid;
			}
			else
			{
				$mid_list[$module_srl] = '';
			}
		}
		return $mid_list[$module_srl];
	}
	// 닉변 기록
	public function getNickList(int $member_srl) : array
	{
		if(!$member_srl)
		{
			return [];
		}
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		return executeQueryArray('member.getMemberModifyNickName', $args)->data;
	}
	public function getChatList(INT $member_srl, INT $page, int $room = 0) : \baseObject
	{
		$config = $this->getconfig();

		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$args->list_count = $config->list_count;
		$args->page = $page ?: 1;
		if($room > 0)
		{
			$args->room = $room;
		}
		else
		{
			$args->room = [];
			$rooms = array_keys($config->elkhatalk_rooms);
			foreach($rooms as $room)
			{
				$args->room[] = (INT)preg_replace('/[^0-9]+/u', '', $room);
			}
		}

		$output = executeQueryArray('elkhabook.getElkhatalk', $args);

		require_once(__DIR__.'/elkhatalk.item.php');
		foreach($output->data as $key => $comment)
		{
			$comment->member_srl = $member_srl;
			if(isset($comment->msg))
			{
				$comment->content = &$comment->msg;
				$comment->valid = $comment->option=='D'? 'N' : 'Y';
			}
			$oComment = new \elkhabook\elkhatalk();
			$oComment->setAttribute($comment);
			$output->data[$key] = $oComment;
		}
		return $output;
	}
	public function getChatListByPk(INT $pk = 0, INT $page_count = 7) : \baseObject
	{
		$config = $this->getConfig();
		$pk_less_prev = 0; // 이전 페이지 존재하는지 체크하기.
		Context::set('prev_exists', FALSE);

		$columns = array();
		$args = new stdClass();
		if($pk <= 0)
		{
			$epage = (INT)Context::get('epage');
			// 0 페이지는 초기 화면.
			if($epage <= 0)
			{
				$args->list_count = $config->list_count; // 15
			}
			else
			{
				// list_count, sort 대신에 pk 지정함.
				$args->pk_more = ($epage -1) * $config->list_count + 1;
				$args->pk_less = $pk_less_prev = $epage * $config->list_count + 1; // + 1이전 페이지 존재하는지 체크하기
			}
		}
		else
		{
			$args->pk = $pk;
			$args->pk_more = $pk - $page_count;
			$args->pk_less = $pk_less_prev = $pk + $page_count + 1; // + 1이전 페이지 존재하는지 체크하기
		}
		$output = executeQueryArray('elkhabook.getElkhatalkByPk', $args);

		if(count($output->data))
		{
			$reset = reset($output->data);
			if($pk_less_prev && $reset->pk==$pk_less_prev)
			{
				$first = array_shift($output->data);
				Context::set('prev_exists', $pk_less_prev);
			}
		}

		// 현재 페이지 구하기
		if($pk > 0)
		{
			Context::set('epage', ceil($pk / $config->list_count) );
		}
		else if(count($output->data))
		{
			$reset = reset($output->data);
			Context::set('epage', ceil($reset->pk / $config->list_count) );
		}


		require_once(__DIR__.'/elkhatalk.item.php');
		foreach($output->data as $key => $comment)
		{
			if(isset($comment->msg))
			{
				$comment->content = &$comment->msg;
				$comment->valid = $comment->option=='D'? 'N' : 'Y';
			}
			$oComment = new \elkhabook\elkhatalk();
			$oComment->setAttribute($comment);
			$output->data[$key] = $oComment;
			if($pk && $pk==$oComment->get('pk'))
			{
				$output->oComment = $oComment;
			}
		}
		return $output;
	}
	public function isAccessible(object $obj) : bool
	{
		if(isset($obj->elkhatalk))
		{
			return TRUE;
		}
		if(!($module_srl = (int)$obj->get('module_srl')) || $module_srl == abs($obj->get('member_srl')) || !is_object($module_info = \ModuleModel::getModuleInfoByModuleSrl($module_srl)) || !($module_info->module_srl ?? 0))
		{
			return false;
		}
		$logged_info = \Context::get('logged_info');
		if(!is_object($logged_info) || !($logged_info->member_srl ?? 0))
		{
			if((int)$logged_info->member_srl != (int)$obj->get('member_srl') && ($logged_info->is_admin ?? 'N') != 'Y' && in_array($module_srl, $this->_excludeModuleSrls()))
			{
				return false;
			}
		}


		if( $obj->get('comment_srl') || $obj->get('document_srl') )
		{
			if($obj->get('comment_srl'))
			{
				$oDocument = \DocumentModel::getDocument($obj->get('document_srl'), false, false);
				if(!$oDocument->isAccessible())
				{
					return false;
				}
			}
			if(($module_info->consultation ?? 'N') == 'N')
			{
				return $obj->isAccessible(TRUE);
			}
		}

		return FALSE;
	}
	protected function _excludeModuleSrls() : array
	{
		static $module_srls;
		if(!count($module_srls ?? []))
		{
			$module_srls = [];
			$config = $this->getConfig();
			foreach($config->exclude_list ?? [] as $mid)
			{
				$module_info = \ModuleModel::getModuleInfoByMid($mid);
				if(is_object($module_info) && ($module_info->module_srl ?? 0))
				{
					$module_srls[] = (int)$module_info->module_srl;
				}
			}
		}
		return $module_srls;
	}
}
