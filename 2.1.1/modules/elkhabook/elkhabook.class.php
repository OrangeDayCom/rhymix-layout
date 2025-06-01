<?php

/**
 * Elkha Book
 *
 * Copyright (c) 엘카
 *
 * Generated with https://www.poesis.org/tools/modulegen/
 */
class Elkhabook extends ModuleObject
{
	protected static int $_member_srl;
	public array $_acts = [
		'dispMemberInfo' => ['act' => 'dispElkhabookIndex', 'mid' => 'source_mid']
	];

	public function getAct(string $act) : string
	{
		return isset($this->_acts[$act]) ? $this->_acts[$act]['act'] : $act;
	}
	public static function getUrl(string $act, int $member_srl, $func = 'getNotEncodedUrl', string $doc_type = '') : string
	{
		if(!$member_srl && is_object($logged_info = Context::get('logged_info')) && $logged_info->member_srl)
		{
			$member_srl = $logged_info->member_srl;
		}
		static $urls = [];
		if(!isset($urls[$act]))
		{
			$urls[$act] = [];
		}
		if(!isset($urls[$act][$member_srl]))
		{
			if($member_srl && is_object($member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl)) && ($member_info->member_srl ?? 0) == $member_srl)
			{
				$oElkhabook = self::getInstance();
				$config = $oElkhabook->getConfig();
				if(isset($oElkhabook->_acts[$act]) && file_exists(__DIR__ . "/skins/$config->skin/$act.html"))
				{
					if(preg_match('/^(true|y|yes)$/i', \ModuleModel::getModuleActionXml('elkhabook')->action->dispElkhabookIndex->global_route))
					{
						if($config->user_id_open == 'Y')
						{
							$urls[$act][$member_srl] = ['', 'act','dispElkhabookIndex', 'target_id', $member_info->user_id]; //\RX_BASEURL . '@' . urlencode($member_info->user_id);
						}
						else if($config->user_id_open == 'nick_name')
						{
							$urls[$act][$member_srl] = ['', 'act','dispElkhabookIndex', 'target_id', $member_info->nick_name]; //\RX_BASEURL . '@' . urlencode($member_info->nick_name);
						}
						else
						{
							$urls[$act][$member_srl] = ['', 'mid', $config->{$oElkhabook->_acts[$act]['mid']}, 'member_srl', $member_srl];
						}
					}
					else
					{
						if($config->user_id_open == 'Y')
						{
							$urls[$act][$member_srl] = ['', 'target_id', $member_info->user_id, 'mid', $config->{$oElkhabook->_acts[$act]['mid']}];
						}
						else if($config->user_id_open == 'nick_name')
						{
							$urls[$act][$member_srl] = ['', 'target_id', $member_info->nick_name, 'mid', $config->{$oElkhabook->_acts[$act]['mid']}];
						}
						else
						{
							$urls[$act][$member_srl] = ['', 'mid', $config->{$oElkhabook->_acts[$act]['mid']}, 'member_srl', $member_srl];
						}
					}
				}
			}
			else
			{
				$urls[$act][$member_srl] = [];
			}
		}
		// 구버전 코드 호환.
		if(is_bool($func))
		{
			$func = $func ? 'getUrl' : 'getNotEncodedUrl';
		}
		return call_user_func_array($func, array_merge($urls[$act][$member_srl], ['doc_type', $doc_type]));
	}

	/**
	 * 모듈 설정 캐시를 위한 변수.
	 */
	protected static object $_config_cache;

	/**
	 * 캐시 핸들러 캐시를 위한 변수.
	 */
	protected static $_cache_handler_cache = null;

	public static function getConfig() : object
	{
		if (!isset(static::$_config_cache))
		{
			$oModuleModel = getModel('module');
			static::$_config_cache = $oModuleModel->getModuleConfig('elkhabook') ?: new \stdClass;
		}
		static::$_config_cache->source_mid = static::$_config_cache->source_mid ?? 'mylog';
		static::$_config_cache->skin = static::$_config_cache->skin ?? 'default';
		static::$_config_cache->list_count = static::$_config_cache->list_count ?? 10;
		static::$_config_cache->elkhatalk_list_count = static::$_config_cache->elkhatalk_list_count ?? 15;
		static::$_config_cache->skin_colorset = static::$_config_cache->skin_colorset ?? '#2c3e50';
		static::$_config_cache->doc_list = static::$_config_cache->doc_list ?? [];
		static::$_config_cache->follow_point = static::$_config_cache->follow_point ?? 15;
		static::$_config_cache->elkhatalk_rooms = static::$_config_cache->elkhatalk_rooms ?? [];
		static::$_config_cache->follow_add_limit = static::$_config_cache->follow_add_limit ?? 1;
		static::$_config_cache->follow_delete_limit = static::$_config_cache->follow_delete_limit ?? 24;
		static::$_config_cache->user_id_open = static::$_config_cache->user_id_open ?? 'N';
		static::$_config_cache->browser_title = static::$_config_cache->browser_title ?? \Context::getLang('cmd_view_member_info');
		static::$_config_cache->layout_srl = static::$_config_cache->layout_srl ?? -1;
		static::$_config_cache->mlayout_srl = static::$_config_cache->mlayout_srl ?? -1;
		static::$_config_cache->view = static::$_config_cache->view ?? 'N';
		static::$_config_cache->friend_follow_message = static::$_config_cache->friend_follow_message ?? "[[NICK_NAME]] 님이 당신을 팔로우 했습니다.\n[POINT] [POINT_NAME]를 받습니다.\n[URL]";
		static::$_config_cache->friend_unfollow_message = static::$_config_cache->friend_unfollow_message ?? "[[NICK_NAME]] 님이 당신을 언팔로우 했습니다.\n[POINT] [POINT_NAME]를 잃습니다.\n[URL]";
		static::$_config_cache->point_level_icon = static::$_config_cache->point_level_icon ?? 'auto';
		static::$_config_cache->exclude_list = static::$_config_cache->exclude_list ?? [];
		if(!strlen(static::$_config_cache->friend_notify ?? ''))
		{
			if(class_exists('elkhatalk3'))
			{
				static::$_config_cache->friend_notify = 'elkhatalk3';
			}
			else
			{
				$communication_config = \CommunicationModel::getConfig();
				if(($communication_config->enable_message ?? 'Y') == 'Y')
				{
					static::$_config_cache->friend_notify = 'communication';
				}
			}
		}
		static::$_config_cache->content_groups = static::$_config_cache->content_groups ?? [
			//$order => ['내부 키 값', '커스텀 레이블', '권한 기본 값', ['권한1', '권한2']]
			1 => ['문서', '문서', 'open', ['open']],
			2 => ['댓글', '댓글', 'open', ['open']]
		];

		return static::$_config_cache;
	}
	protected function _member_info(int $member_srl = 0) : \stdClass
	{
		if($member_srl)
		{
			static::$_member_srl = $member_srl;
		}
		else if(static::$_member_srl ?? 0)
		{
			$member_srl = static::$_member_srl;
		}
		else
		{
			$member_srl = (INT)\Context::get('member_srl');
		}
		if($member_srl)
		{
			$member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl);
			if(is_object($member_info) && ($member_info->member_srl ?? 0) == $member_srl)
			{
				return $member_info;
			}
		}
		$member_info = new \stdClass();
		$member_info->member_srl = $member_info->member_srl ?? 0;
		$member_info->nick_name = $member_info->nick_name ?? '?';
		return $member_info;
	}
	public function my_config(int $member_srl, array $content_group, bool $use_cache = true) : array
	{
		static $configs = [];
		if(!isset($configs[$member_srl]) || !$use_cache)
		{
			if($use_cache)
			{
				$configs[$member_srl] = $this->getCache($member_srl);
			}
			if(!$use_cache || !is_array($configs[$member_srl]))
			{
				$configs[$member_srl] = [];
				$data = executeQueryArray('elkhabook.getElkhabookMyConfig', ['member_srl' => $member_srl], ['name','contents'])->data ?? [];
				foreach($data as $val)
				{
					$configs[$member_srl][$val->name/*문서_친구*/] = $val->contents/*open*/;
				}
				$this->setCache($member_srl, $configs[$member_srl], 3600);
			}
		}
		if(isset($configs[$member_srl][$content_group[0]]))
		{
			$contents = $configs[$member_srl][$content_group[0]];
		}
		else
		{
			$contents = $content_group[2]; // open (기본 값)
		}
		return array_values(array_unique(array_merge([$contents], $content_group[3]))); // array_unshift
	}
	/**
	 * 모듈 설정을 저장하는 함수.
	 *
	 * 설정을 변경할 필요가 있을 때 ModuleController를 직접 호출하지 말고 이 함수를 사용한다.
	 * getConfig()으로 가져온 설정을 적절히 변경하여 setConfig()으로 다시 저장하는 것이 정석.
	 *
	 * @param object $config
	 * @return object
	 */
	public function setConfig(object $config) : \baseObject
	{
		$oModuleController = getController('module');
		$result = $oModuleController->insertModuleConfig($this->module, $config);
		if ($result->toBool())
		{
			static::$_config_cache = $config;
		}
		return $result;
	}

	/**
	 * 오브젝트 캐시에서 값을 가져오는 함수.
	 *
	 * 그룹 키를 지정하지 않으면 자동으로 현재 모듈 이름이 그룹 키로 사용되므로
	 * 필요시 그룹 키를 비움으로써 신속하게 캐시를 갱신할 수 있다.
	 *
	 * @param string $key
	 * @param int $ttl
	 * @param string $group_key (optional)
	 * @return mixed
	 */
	public function getCache(string $key, int $ttl = 86400, string $group_key = 'elkhabook')
	{
		if (self::$_cache_handler_cache === null)
		{
			self::$_cache_handler_cache = \CacheHandler::getInstance('object');
		}

		if (self::$_cache_handler_cache->isSupport())
		{
			return self::$_cache_handler_cache->get(self::$_cache_handler_cache->getGroupKey($group_key, $key), $ttl);
		}
		else
		{
			return false;
		}
	}

	/**
	 * 오브젝트 캐시에 값을 저장하는 함수.
	 *
	 * 그룹 키를 지정하지 않으면 자동으로 현재 모듈 이름이 그룹 키로 사용되므로
	 * 필요시 그룹 키를 비움으로써 신속하게 캐시를 갱신할 수 있다.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @param string $group_key (optional)
	 * @return bool
	 */
	public function setCache(string $key, $value, int $ttl = 86400, string $group_key = 'elkhabook') : bool
	{
		if (self::$_cache_handler_cache === null)
		{
			self::$_cache_handler_cache = \CacheHandler::getInstance('object');
		}

		if (self::$_cache_handler_cache->isSupport())
		{
			$group_key = $group_key ?: $this->module;
			return !!self::$_cache_handler_cache->put(self::$_cache_handler_cache->getGroupKey($group_key, $key), $value, $ttl);
		}
		else
		{
			return false;
		}
	}

	/**
	 * 오브젝트 캐시에서 개별 키를 삭제하는 함수.
	 *
	 * @param string $key
	 * @param string $group_key (optional)
	 * @return bool
	 */
	public function deleteCache(string $key, string $group_key = 'elkhabook') : bool
	{
		if (self::$_cache_handler_cache === null)
		{
			self::$_cache_handler_cache = CacheHandler::getInstance('object');
		}

		if (self::$_cache_handler_cache->isSupport())
		{
			return !!self::$_cache_handler_cache->delete(self::$_cache_handler_cache->getGroupKey($group_key, $key));
		}
		else
		{
			return false;
		}
	}

	/**
	 * 오브젝트 캐시를 비우는 함수.
	 *
	 * 지정된 그룹 키에 소속된 데이터만 삭제한다.
	 * 현재 모듈에서 저장한 데이터만 삭제하는 것이 기본값이다.
	 *
	 * @param string $group_key (optional)
	 * @return bool
	 */
	public function clearCache(string $group_key = 'elkhabook') : bool
	{
		if (self::$_cache_handler_cache === null)
		{
			self::$_cache_handler_cache = \CacheHandler::getInstance('object');
		}

		if (self::$_cache_handler_cache->isSupport())
		{
			$group_key = $group_key ?: $this->module;
			return !!self::$_cache_handler_cache->invalidateGroupKey($group_key);
		}
		else
		{
			return false;
		}
	}

	/**
	 * XE Object를 생성하여 반환한다.
	 *
	 * XE 1.8 이하, XE 1.9 이상, PHP 7.1 이하, PHP 7.2 이상 모두 호환된다.
	 * 기본적인 사용법은 return new Object(-1, 'error'); 라고 쓸 자리에
	 * return $this->createObject(-1, 'error'); 라고 쓰면 된다.
	 *
	 * 반환할 언어 내용 중 %s, %d 등 변수를 치환하는 부분이 있다면
	 * 치환할 내용을 추가 파라미터로 넘겨주면 sprintf()의 역할까지 해준다.
	 *
	 * @param string $message
	 * @param $arg1, $arg2 ...
	 * @return object
	 */
	public function createObject(int $status = 0, string $message = 'success' /* $arg1, $arg2 ... */) : \baseObject
	{
		$args = func_get_args();
		if (count($args) > 2)
		{
			global $lang;
			$message = vsprintf($lang->$message, array_slice($args, 2));
		}
		return new \baseObject($status, $message);
	}

	/**
	 * 모듈 설치 콜백 함수.
	 *
	 * 트리거 등록 외에 따로 할 일이 없다면 수정할 필요 없다.
	 *
	 * @return object
	 */
	public function moduleInstall() : \baseObject
	{
		// 최초 1회만.
		$config = $this->getConfig();
		$oAdminController = getAdminController('elkhabook');
		$output = $oAdminController->insertModule($config);

		if($this->checkUpdate())
		{
			$output = $this->moduleUpdate();
		}

		return $this->createObject(0, 'success_updated');
	}

	/**
	 * 모듈 업데이트 확인 콜백 함수.
	 *
	 * 트리거 등록 외에 따로 할 일이 없다면 수정할 필요 없다.
	 *
	 * @return bool
	 */
	public function checkUpdate() : bool
	{
		$oDB = \DB::getInstance();
		if(!$oDB->isTableExists('elkhabook_config'))
		{
			return true;
		}
		return false;
	}

	/**
	 * 모듈 업데이트 콜백 함수.
	 *
	 * 트리거 등록 외에 따로 할 일이 없다면 수정할 필요 없다.
	 *
	 * @return object
	 */
	public function moduleUpdate() : \baseObject
	{
		$db_config = Rhymix\Framework\Config::get('db.master');
		$oDB = \DB::getInstance();
		$query = "CREATE TABLE `{$db_config['prefix']}elkhabook_config` (
  `pk` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_srl` BIGINT UNSIGNED NOT NULL,
  `name` ENUM('문서','댓글','문서_친구','문서_팔로잉','문서_팔로워','문서_추천','댓글_친구','댓글_팔로잉','댓글_팔로워') NOT NULL,
  `contents` ENUM('open','logged','friends','follower','following','hide') NOT NULL,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `member_srl` (`member_srl`,`name`)
) ENGINE={$db_config['engine']}
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;";
		$result = $oDB->query($query);
		return $this;
	}

	/**
	 * 캐시파일 재생성 콜백 함수.
	 *
	 * @return void
	 */
	public function recompileCache() : void
	{
		$this->clearCache();
	}
}
