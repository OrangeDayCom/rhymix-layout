<?php
require_once(__DIR__ . '/elkhabook.controller.php');
class ElkhabookAdminController extends ElkhabookController
{
	public function insertModule(\stdClass $vars) : \baseObject
	{
		\Context::loadLang('modules/member/lang');
		$oModuleController = getController('module');

		$args = new \stdClass();
		$args->mid = $vars->source_mid;
		$args->module = 'elkhabook';
		$args->skin = $vars->skin ?? 'default';
		$args->browser_title = $vars->browser_title ?: \Context::getLang('cmd_view_member_info');
		$args->layout_srl = $vars->layout_srl ?? -1;
		$args->mlayout_srl = $vars->mlayout_srl ?? -1;
		$args->use_mobile = 'Y';
		$args->mskin = '/USE_RESPONSIVE/';
		if(is_object($module_info = \ModuleModel::getModuleInfoByMid($vars->source_mid)) && strlen($module_info->module ?? ''))
		{
			if($module_info->module == 'elkhabook')
			{
				$args->module_srl = $module_info->module_srl;
				$output = $oModuleController->updateModule($args);
			}
			else
			{
				$output = $this->createObject(-1, 'invalid value: mid');
			}
		}
		else
		{
			$updated = false;
			$data = executeQueryArray('elkhabook.getModulesElkhabook')->data;
			foreach($data as $mi)
			{
				if($mi->mid == $vars->source_mid)
				{
					$args->module_srl = $mi->module_srl;
					$output = $oModuleController->updateModule($args);
					$updated = true;
				}
				else
				{
					$output = $oModuleController->deleteModule($mi->module_srl);
				}
			}
			if($updated === false)
			{
				$output = $oModuleController->insertModule($args);
			}
		}
		return method_exists($output, 'toBool') ? $output : $this->createObject();
	}
	public function procElkhabookAdminInsertConfig() : \baseObject
	{
		$vars = \Context::getRequestVars();
		$config = new \stdClass();
		$config->skin = $vars->skin;
		$config->doc_list = [];
		$config->elkhatalk_rooms = [];
		$config->user_id_open = $vars->user_id_open ?: 'N';
		$config->layout_srl = $vars->layout_srl ?? -1;
		$config->mlayout_srl = $vars->mlayout_srl ?? -1;
		$config->view = $vars->view ?? 'N';
		$config->list_count = $vars->list_count ?? 10;
		$config->friend_notify = $vars->friend_notify ?? 'disabled';
		$config->point_level_icon = $vars->point_level_icon ?? 'auto';
		if(isset($vars->source_mid) && strlen($vars->source_mid))
		{
			$output = $this->insertModule($vars);
			if(!$output->toBool())
			{
				return $output;
			}
			$config->source_mid = $vars->source_mid;
		}
		else
		{
			return $this->stop('invalid value: mid');
		}
		if(isset($vars->skin_colorset) && strlen($vars->skin_colorset))
		{
			$config->skin_colorset = $vars->skin_colorset;
		}
		if(isset($vars->follow_point) && strlen($vars->follow_point))
		{
			$config->follow_point = (INT)$vars->follow_point;
		}
		if(isset($vars->follow_add_limit) && strlen($vars->follow_add_limit))
		{
			$config->follow_add_limit = (INT)$vars->follow_add_limit;
		}
		if(isset($vars->follow_delete_limit) && strlen($vars->follow_delete_limit))
		{
			$config->follow_delete_limit = (INT)$vars->follow_delete_limit;
		}
		if(isset($vars->browser_title) && strlen($vars->browser_title))
		{
			$config->browser_title = $vars->browser_title;
		}
		if(strlen($vars->friend_follow_message ?? ''))
		{
			$config->friend_follow_message = $vars->friend_follow_message;
		}
		if(strlen($vars->friend_unfollow_message ?? ''))
		{
			$config->friend_unfollow_message = $vars->friend_unfollow_message;
		}
		if(strlen($vars->friend_unfollow_use ?? ''))
		{
			$config->friend_unfollow_use = $vars->friend_unfollow_use;
		}
		if(strlen($vars->notify_member_srl2 ?? ''))
		{
			if(preg_match('/^[0-9]+$/', $vars->notify_member_srl2))
			{
				$notify_info = \MemberModel::getMemberInfoByMemberSrl($vars->notify_member_srl2);
			}
			else
			{
				$notify_info = \MemberModel::getMemberInfoByUserID($vars->notify_member_srl2);
			}
			$config->notify_member_srl = $notify_info->member_srl ?? 0;
		}
		if(strlen($vars->exclude_list ?? ''))
		{
			$config->exclude_list = preg_split('/[\s\t\v\r\n,]+/', $vars->exclude_list);
		}
		$content_groups = (array)\Context::get('content_groups');
		$content_groups_order = (array)\Context::get('content_groups_order');
		foreach($content_groups as $key => $val)
		{
			if(!strlen($content_groups_order[$key] ?? '') || !strlen($val[0] ?? '') || !strlen($val[1] ?? '') || !strlen($val[2] ?? '') || !count($val[3] ?? []))
			{
				continue;
			}
			$config->content_groups = $config->content_groups ?? [];
			$order = (INT)$content_groups_order[$key];
			while(isset($config->content_groups[$order]))
			{
				$order++;
			}
			ksort($val);
			$config->content_groups[$order] = $val;
		}
		if(isset($config->content_groups))
		{
			ksort($config->content_groups);
			//$config->content_groups = array_values($config->content_groups); // remove keys
		}

		foreach($vars->doc_list_regex as $key => $regex)
		{
			if(!strlen(trim($regex)))
			{
				continue;
			}
			if(!strlen(trim($vars->doc_list_label[$key])))
			{
				continue;
			}
			$config->doc_list[$regex] = [
				'label' => $vars->doc_list_label[$key],
				'more' => (INT)$vars->doc_list_more[$key]
			];
		}

		foreach($vars->elkhatalk_rooms_name as $key => $room)
		{
			if(!strlen(trim($room)))
			{
				continue;
			}
			if(!strlen(trim($vars->elkhatalk_rooms_label[$key])))
			{
				continue;
			}
			$config->elkhatalk_rooms[$room] = $vars->elkhatalk_rooms_label[$key];
		}

		// 변경된 설정을 저장
		$output = $this->setConfig($config);
		if (!$output->toBool())
		{
			return $output;
		}

		// 설정 화면으로 리다이렉트
		$this->setMessage('success_registed');
		$this->setRedirectUrl(Context::get('success_return_url'));

		return $this;
	}
	public function procElkhabookAdminAlterIndex() : self
	{
		$oDB = \DB::getInstance();
		$oDB->dropIndex('comments', 'idx_member_srl');
		if((\Context::get('index') ?? '')=== 'Y')
		{
			$oDB->addIndex('comments', 'idx_member_srl', ['member_srl', 'module_srl']);
			//$oDB->query('ALTER TABLE `comments` DROP INDEX `idx_member_srl`, ADD INDEX `idx_member_srl` (`member_srl`, `module_srl`);');
		}
		else
		{
			$oDB->addIndex('comments', 'idx_member_srl', ['member_srl']);
			//$oDB->query('ALTER TABLE `comments` DROP INDEX `idx_member_srl`, ADD INDEX `idx_member_srl` (`member_srl`);');
		}
		return $this;
	}
}
