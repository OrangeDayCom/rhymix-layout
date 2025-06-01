<?php

/**
 * Elkha Book
 *
 * Copyright (c) 엘카
 *
 * Generated with https://www.poesis.org/tools/modulegen/
 */
class ElkhabookController extends Elkhabook
{
	public static function notifyInfo() : object
	{
		$config = static::getConfig();
		if($config->notify_member_srl ?? 0)
		{
			return \MemberModel::getMemberInfoByMemberSrl($config->notify_member_srl);
		}
		return new \stdClass();
	}
	public static function followMessage(string $message, bool $use_html = false, int $member_srl = 0) : string
	{
		return preg_replace_callback('/\[[A-Z\_]+\]/', function($matches) use($use_html, $member_srl) {
			if($matches[0] == '[NICK_NAME]')
			{
				$member_info = $member_srl ? \MemberModel::getMemberInfoByMemberSrl($member_srl) : \Context::get('logged_info');
				return (is_object($member_info) && $member_info->nick_name) ? $member_info->nick_name : '?';
			}
			else if($matches[0] == '[POINT]')
			{
				$config = static::getConfig();
				return number_format($config->follow_point);
			}
			else if($matches[0] == '[POINT_NAME]')
			{
				$point_config = \ModuleModel::getModuleConfig('point');
				return $point_config->point_name;
			}
			else if($matches[0] == '[URL]')
			{
				if($use_html)
				{
					return sprintf('<a href="%s">%s</a>', static::getUrl('dispMemberInfo', $member_srl, 'getUrl'), htmlspecialchars(urldecode(static::getUrl('dispMemberInfo', $member_srl, 'getNotEncodedFullUrl'))) );
				}
				else
				{
					return static::getUrl('dispMemberInfo', $member_srl, 'getFullUrl');
				}
			}
			return $matches[0];
		}, \Context::replaceUserLang($message));
	}

	public function beforeAddFriend($obj) : self
	{
		$config = $this->getConfig();
		if(($config->follow_add_limit ?: 0) && \Context::get('logged_info')->is_admin != 'Y')
		{
			$args = new \stdClass();
			$args->member_srl = $obj->member_srl;
			$args->list_count = 1;
			$data = executeQuery('elkhabook.getFriends', $args)->data;

			if(is_object($data) && isset($data->regdate) && ( $limit = time() - (INT)strtotime($data->regdate) - floor($config->follow_add_limit * 3600) ) < 0)
			{
				\Context::loadLang('modules/elkhabook/lang');
				if(abs($limit) < 3600)
				{
					$message = sprintf(\Context::getLang('elkhabook_can_not_add_minutes'), floor(abs($limit) / 60));
				}
				else
				{
					$message = sprintf(\Context::getLang('elkhabook_can_not_add_hours'), floor(abs($limit) / 3600));
				}
				throw new Rhymix\Framework\Exception($message);
			}
		}
		return $this;
	}
	public function afterAddFriend($args) : self
	{
		$config = $this->getConfig();
		if($point = $config->follow_point ?: 0)
		{
			$oPointController = getController('point');
			$output = $oPointController->setPoint($args->target_srl, $point, 'add');
		}
		$oModel = getModel('elkhabook');
		$oModel->getElkhabookFriendButton($args->target_srl);
		$oCommunicationController = getController('communication');
		$oCommunicationController->add('elkhabook_tpl_button', $oModel->get('tpl_button'));

		if($config->friend_notify == 'elkhatalk3')
		{
			$logged_info = \Context::get('logged_info');
			$notify_info = static::notifyInfo();
			$sender_srl = $notify_info->member_srl ?? $logged_info->member_srl;
			$message = static::followMessage($config->friend_follow_message);
			$oElkhatalk3Controller = getController('elkhatalk3');
			$oElkhatalk3Controller->procElkhatalk3Say(FALSE, $args->target_srl, $message, $sender_srl);
		}
		else if($config->friend_notify == 'communication')
		{
			$communication_config = \CommunicationModel::getConfig();

			$logged_info = \Context::get('logged_info');
			$notify_info = static::notifyInfo();
			$sender_srl = $notify_info->member_srl ?? $logged_info->member_srl;
			$content = static::followMessage($config->friend_follow_message, $communication_config->editor_skin != 'textarea');
			$title = explode("\n", $content)[0];
			if($communication_config->editor_skin != 'textarea')
			{
				$content = nl2br($content);
			}
			$oCommunicationController->sendMessage($sender_srl, $args->target_srl, $title, $content);
		}

		return $this;
	}
	public function beforeDeleteFriend($args) : self
	{
		$config = $this->getConfig();
		$data = executeQueryArray('elkhabook.getFriends', $args)->data;
		$friend_srl_list = [];
		$target_srls = [];
		foreach($data as $val)
		{
			if(\Context::get('logged_info')->is_admin == 'Y')
			{
				$friend_srl_list[] = $val->friend_srl;
				$target_srls[] = $val->target_srl;
			}
			else if(( $limit = time() - (INT)strtotime($val->regdate) - floor($config->follow_delete_limit * 3600) ) < 0)
			{
				\Context::loadLang('modules/elkhabook/lang');
				if(abs($limit) < 3600)
				{
					$message = sprintf(\Context::getLang('elkhabook_can_not_delete_minutes'), floor(abs($limit) / 60));
				}
				else
				{
					$message = sprintf(\Context::getLang('elkhabook_can_not_delete_hours'), floor(abs($limit) / 3600));
				}
				throw new Rhymix\Framework\Exception($message);
			}
			else if(!is_object($member_info = \MemberModel::getMemberInfoByMemberSrl($val->target_srl)) || !$member_info->member_srl)
			{
				$friend_srl_list[] = $val->friend_srl;
			}
			else
			{
				$friend_srl_list[] = $val->friend_srl;
				$target_srls[] = $val->target_srl;
			}
		}
		if(!count($friend_srl_list))
		{
			\Context::loadLang('modules/elkhabook/lang');
			throw new Rhymix\Framework\Exception('elkhabook_can_not_delete');
		}
		$args->friend_srl_list = $friend_srl_list;
		$args->target_srls = $target_srls;
		return $this;
	}
	public function afterDeleteFriend($args) : self
	{
		$config = $this->getConfig();
		if(isset($args->target_srls) && is_array($args->target_srls) && ($point = $config->follow_point ?: 0))
		{
			$oPointController = getController('point');
			foreach($args->target_srls as $target_srl)
			{
				$output = $oPointController->setPoint($target_srl, $point, 'minus');
			}
		}
		$oCommunicationController = getController('communication');
		if(is_array($args->target_srls) && count($args->target_srls) == 1)
		{
			$target_srl = reset($args->target_srls);
			$oModel = getModel('elkhabook');
			$oModel->getElkhabookFriendButton($target_srl);
			$oCommunicationController->add('elkhabook_tpl_button', $oModel->get('tpl_button'));
		}

		if(($config->friend_unfollow_use ?? 'N') === 'Y')
		{
			if($config->friend_notify == 'elkhatalk3')
			{
				$logged_info = \Context::get('logged_info');
				$notify_info = static::notifyInfo();
				$sender_srl = $notify_info->member_srl ?? $logged_info->member_srl;
				$message = static::followMessage($config->friend_unfollow_message);
				$oElkhatalk3Controller = getController('elkhatalk3');
				foreach($args->target_srls as $target_srl)
				{
					$oElkhatalk3Controller->procElkhatalk3Say(FALSE, $target_srl, $message, $sender_srl);
				}
			}
			else if($config->friend_notify == 'communication')
			{
				$communication_config = \CommunicationModel::getConfig();

				$logged_info = \Context::get('logged_info');
				$notify_info = static::notifyInfo();
				$sender_srl = $notify_info->member_srl ?? $logged_info->member_srl;
				$content = static::followMessage($config->friend_unfollow_message, $communication_config->editor_skin != 'textarea');
				$title = explode("\n", $content)[0];
				if($communication_config->editor_skin != 'textarea')
				{
					$content = nl2br($content);
				}
				foreach($args->target_srls as $target_srl)
				{
					$oCommunicationController->sendMessage($sender_srl, $target_srl, $title, $content);
				}
			}
		}

		return $this;
	}
	public function procElkhabookMyConfig() : \baseObject
	{
		$name = (STRING)\Context::get('name');
		$contents = (STRING)\Context::get('contents');
		$logged_info = \Context::get('logged_info');

		$config = $this->getConfig();
		foreach ($config->content_groups as $arr)
		{
			if($arr[0] == $name && in_array($contents, $arr[3]))
			{
				$output = executeQuery('elkhabook.insertElkhabookConfig', [
					'name' => $name,
					'contents' => $contents,
					'member_srl' => $logged_info->member_srl,
				]);
				if($output->toBool())
				{
					$my_config = $this->my_config($logged_info->member_srl, $arr, false);
					\Context::set('my_config', $my_config);
					$this->setMessage(\Context::getLang('success_updated') . "\n[".\Context::getLang("elkhabook_contents_{$my_config[0]}")."]");

					$oTemplateHandler = \TemplateHandler::getInstance();
					$tpl = $oTemplateHandler->compile($this->module_path . "skins/{$config->skin}", __FUNCTION__);
					$this->add(__FUNCTION__, $tpl);

					return $this;
				}
				return $output;
			}
		}
		return $this->stop('msg_invalid_request');
	}
}
