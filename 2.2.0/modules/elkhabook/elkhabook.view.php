<?php

class ElkhabookView extends Elkhabook
{
	public function init() : \baseObject
	{
		foreach($this->_acts as $act => $val)
		{
			if($this->act == $val['act'])
			{
				$config = $this->getConfig();
				if(($config->view ?? '') == 'Y')
				{
					if(!is_object($logged_info = Context::get('logged_info')) || !$logged_info->member_srl)
					{
						return $this->stop('msg_not_logged');
					}
				}
				$this->setTemplatePath($this->module_path . 'skins/' . $config->skin);
				$this->setTemplateFile($act);
				$this->module_info->layout_srl = $config->layout_srl;
				$this->module_info->mlayout_srl = $config->mlayout_srl;
				return $this;
			}
		}
		return $this->stop('msg_invalid_request');
	}

	public function triggerBeforeDispMemberModule($oModule) : object
	{
		if(\Context::getRequestMethod() != 'GET')
		{
			return $oModule;
		}
		$url = $this->getUrl((STRING)\Context::get('act'), (INT)\Context::get('member_srl'));
		if($url != '')
		{
			header("Location: $url");
			exit;
		}
		return $oModule;
	}
	public function dispElkhabookIndex() : \baseObject
	{
		$config = $this->getConfig();
		$target_srl = (INT)\Context::get('member_srl') ?: (INT)\Context::get('document_srl') ?: (INT)\Context::get('target_srl') ?: 0;
		if(!$target_srl && strlen($user_id = \Context::get('target_id') ?: ''))
		{
			if($config->user_id_open == 'Y')
			{
				if(is_object($member_info = \MemberModel::getMemberInfoByUserID($user_id)) && $member_info->user_id == $user_id)
				{
					$target_srl = $member_info->member_srl;
				}
			}
			else if($config->user_id_open == 'nick_name')
			{
				$target_srl = (INT)\MemberModel::getMemberSrlByNickName($user_id);
			}
		}
		if(!$target_srl)
		{
			if(is_object($logged_info = \Context::get('logged_info')) && ($logged_info->member_srl ?? 0))
			{
				header('Location: ' . static::getUrl('dispMemberInfo', $logged_info->member_srl), true, 302);
				exit;
			}
			return $this->stop('msg_not_logged');
		}

		foreach(\Context::getInstance()->opengraph_metadata as $key => $val)
		{
			if($val[0] == 'og:url') unset(\Context::getInstance()->opengraph_metadata[$key]);
		}

		// $oModel->setMemberInfo 에서 옮겨옴.
		$member_info = $this->_member_info($target_srl);
		\Context::loadLang('modules/member/lang');
		\Context::set('_member_info', $member_info);
		if($member_info->member_srl)
		{
			\Context::setBrowserTitle("{$config->browser_title} - {$member_info->nick_name}");
		}
		else
		{
			\Context::setBrowserTitle( \Context::getLang('msg_not_exists_member') );
		}
		return $this;
	}
}
