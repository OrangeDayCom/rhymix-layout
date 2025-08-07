<?php
declare(strict_types=1);
namespace Rhymix\widgets;

// 8.x
/*readonly class Config
{
	public function __construct(
		public string $rank_type,
		public int    $list_count,
		public array  $except_members,
		public int    $cache_expires,
		public string $left_members,
	) {}
}
*/
// 7.4 호환.
class Config
{
	public string $rank_type;
	public int    $list_count;
	public array  $except_members;
	public int    $cache_expires;
	public string $left_members;
	public function __construct(array $arr)
	{
		list($this->rank_type, $this->list_count, $this->except_members, $this->cache_expires, $this->left_members) = $arr;
	}
}

class ElkhaRank extends \WidgetHandler
{
	public const DATA_DIR = 'files/elkha_rank/';
	public function proc(object $args) : string
	{
		$skin = strlen($args->skin ?? '') ? $args->skin : 'default';
		$list_count = (int)($args->list_count ?? 0) ?: 10;
		$rank_type = strlen($args->rank_type ?? '') ? $args->rank_type : 'point';
		$cache_expires = strlen($args->cache_expires ?? '') ? (int)$args->cache_expires : 3600;
		$left_members = in_array($args->left_members ?? '', ['S', 'I', 'E']) ? $args->left_members : 'S';

		$except_members = [];
		$_except_members = strlen($args->except_members ?? '') ? preg_split('/[^a-z0-9\-_]+/ui', $args->except_members) : [];
		foreach($_except_members as $val)
		{
			if(preg_match('/^[0-9]+$/', $val))
			{
				$member_info = \MemberModel::getMemberInfoByMemberSrl((int)$val);
			}
			else if(strlen($val))
			{
				$member_info = \MemberModel::getMemberInfoByUserID($val);
			}
			else
			{
				continue;
			}
			if(is_object($member_info) && ($member_info->member_srl ?? 0))
			{
				$except_members[] = $member_info->member_srl;
			}
		}

		$widget_info = new \stdClass();
		// 8.x
		/*$widget_info->list = $this->list(new Config(
			rank_type: $rank_type,
			list_count: $list_count,
			except_members:  $except_members,
			cache_expires:  $cache_expires,
			left_members: $left_members
		));*/
		$widget_info->list = $this->list(new Config([$rank_type, $list_count, $except_members, $cache_expires, $left_members]));
		$widget_info->lang_msg_not_founded = strlen($args->lang_msg_not_founded ?? '') ? \Context::getLang($args->lang_msg_not_founded) : \Context::getLang('msg_not_founded');
		$widget_info->lang_rank = strlen($args->lang_rank ?? '') ? \Context::getLang($args->lang_rank) : '순위';
		$widget_info->max_count = count($widget_info->list) ? array_first($widget_info->list)[1] : 0;
		$widget_info->lang_left_member = strlen($args->lang_left_member ?? '') ? $args->lang_left_member : '#회원정보없음';

		if(strlen($args->lang_rank_name ?? ''))
		{
			$widget_info->lang_rank_name = \Context::getLang($args->lang_rank_name);
		}
		else
		{
			switch($rank_type)
			{
				case 'point':
					$point_config = \ModuleModel::getModuleConfig('point');
					$widget_info->lang_rank_name = '포인트';
					break;
				case 'friends':
					$widget_info->lang_rank_name = '친구';
					break;
				case 'documents':
					$widget_info->lang_rank_name = '게시글';
					break;
				case 'comments':
					$widget_info->lang_rank_name = '댓글';
					break;
				case 'votes':
					$widget_info->lang_rank_name = '추천';
					break;
			}
		}

		\Context::set('widget_info', $widget_info);

		$oTemplate = \TemplateHandler::getInstance();
		try {
			return $oTemplate->compile("widgets/elkha_rank/skins/{$skin}", 'skin');
		} catch (\Rhymix\Framework\Exception $e) {
			return $e->getMessage();
		}
	}
	public function point(Config $config) : array
	{
		// int $list_count, array $except_members, int $cache_expires
		$oCacheHandler = \CacheHandler::getInstance();
		if($oCacheHandler->isSupport())
		{
			$cache_key = 'object:widget:elkha_rank:' . md5($config->list_count . implode(':', $config->except_members) . $config->left_members);
			$data = $oCacheHandler->get($cache_key);
		}
		else
		{
			$data = null;
		}
		if(!is_array($data))
		{
			// member_srl not in 인덱스 안타니까 그냥 list_count 값을 더해서 가져온 후 제외하자.
			$args = new \stdClass();
			$args->list_count = $config->list_count + count($config->except_members);
			$query_id = 'widgets.elkha_rank.getRankPoint' . ($config->left_members == 'E' ? '' : 'Include');
			$data = executeQueryArray($query_id, $args)->data;
			if($oCacheHandler->isSupport() && $config->cache_expires)
			{
				$oCacheHandler->put($cache_key, $data, $config->cache_expires);
			}
		}
		$list = [];
		foreach($data as $val)
		{
			if(!in_array($val->member_srl, $config->except_members))
			{
				$list[] = [\MemberModel::getMemberInfoByMemberSrl($val->member_srl), $val->count];
			}
			if(count($list) >= $config->list_count)
			{
				break;
			}
		}

		return $list;
	}
	public function list(Config $config) : array
	{
		if(!in_array($config->rank_type, ['friends', 'votes', 'votes2', 'documents', 'comments']))
		{
			return $this->point($config);
		}

		$key = count($config->except_members) + array_sum($config->except_members) + $config->list_count;
		$key .= $config->left_members;
		$filename = self::DATA_DIR . "{$config->rank_type}.{$key}.json";
		$buff = \FileHandler::readFile($filename);
		if(is_string($buff) && strlen($buff))
		{
			$list = json_decode($buff);
			$ignore_cache = !is_array($list);
		}
		else
		{
			$ignore_cache = true;
			$list = [];
		}
		$logged_info = \Context::get('logged_info');
		if($config->rank_type == 'votes2' && is_object($logged_info) && ($logged_info->member_srl ?? 0) && !in_array($logged_info->member_srl, $config->except_members))
		{
			$member_srls = [$logged_info->member_srl, -$logged_info->member_srl];
			$session_val = $_SESSION['widget:elkha_rank'] ?? [];
			$time = $session_val[$config->rank_type][0] ?? time();
			if(time() - $time > $config->cache_expires || !isset($_SESSION['widget:elkha_rank'][$config->rank_type]))
			{
				// 세션에 기존 최고 값을 넣기.
				$session_val[$config->rank_type] = [time()];
				$ignore_cache = true;
				$_SESSION['widget:elkha_rank'] = $session_val;
			}
		}
		else
		{
			$member_srls = [];
		}
		if($ignore_cache === false && (!file_exists($filename) || time() - filemtime($filename) > $config->cache_expires))
		{
			$ignore_cache = true;
		}

		if($ignore_cache)
		{
			if($config->rank_type == 'votes2')
			{
				foreach($list as $val)
				{
					$member_srls[] = $val[0];
					$member_srls[] = -$val[0];
				}
			}
			if(count($member_srls) || $config->rank_type != 'votes2')
			{
				$_rank_type = $config->rank_type == 'votes2' ? 'votes' : $config->rank_type;
				$query_id = 'widgets.elkha_rank.get' . ucfirst($_rank_type) . ($config->left_members == 'E' ? '' : 'Include');
				$args = new \stdClass();
				$args->list_count = $config->list_count;
				if(count($member_srls))
				{
					$args->member_srls = $member_srls;
				}
				else
				{
					$args->not_member_srls = [0];
					foreach($config->except_members as $member_srl)
					{
						$args->not_member_srls[] = $member_srl;
						//$args->not_member_srls[] = -$member_srl;
					}
				}
				$data = executeQueryArray($query_id, $args)->data;
				$list = [];
				foreach($data as $val)
				{
					$count = $val->count;
					while(isset($list[$count]))
					{
						$count++;
					}
					$list[$count] = [$val->member_srl, $val->count];
				}
				krsort($list);
				array_splice($list, $config->list_count);
				\FileHandler::writeFile($filename, json_encode($list));
			}
			else
			{
				$list = [];
			}
		}
		$_list = [];
		foreach($list as $val)
		{
			$member_info = \MemberModel::getMemberInfoByMemberSrl($val[0]);
			if(is_object($member_info) && ($member_info->member_srl ?? 0))
			{
				$_list[] = [$member_info, $val[1]];
			}
			else if($config->left_members != 'S')
			{
				$_list[] = [new \stdClass, $val[1]];
			}
		}
		return $_list;
	}
}
class_alias(__NAMESPACE__ . '\\ElkhaRank', 'elkha_rank', false);
