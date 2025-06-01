<?php

namespace elkhabook;
require_once(\RX_BASEDIR.'modules/comment/comment.item.php');
class elkhatalk extends \commentItem
{
	var bool $elkhatalk = TRUE;
	/*public function getPermanentUrl() : string
	{
	}*/
	public function isGranted() : bool
	{
		$logged_info = \Context::get('logged_info');
		if(!is_object($logged_info) || !($logged_info->member_srl ?? 0))
		{
			return FALSE;
		}
		return $this->get('member_srl')==$logged_info->member_srl || $logged_info->is_admin=='Y';
	}
	public function setAttribute($attribute)
	{
		$this->adds($attribute);
	}
	public function roomName() : string
	{
		$oModel = getModel('elkhabook');
		$config = $oModel->getConfig();

		if(isset($config->elkhatalk_rooms[ 'public' . $this->get('room') ]))
		{
			return $config->elkhatalk_rooms[ 'public' . $this->get('room') ];
		}
		return '?';
	}
	public function getContent2() : string
	{
		$logged_info = \Context::get('logged_info');
		if($this->get('option')!='D' || (is_object($logged_info) && $logged_info->is_admin=='Y'))
		{
			return htmlspecialchars($this->get('content'));
		}
		return \Context::getLang('elkhabook_delete_chat');
	}
	public function getProfileImage() : string
	{
		$member_srl = (INT)$this->get('member_srl');
		if($member_srl > 0)
		{
			$member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl);
			if(is_object($member_info) && ($member_info->member_srl ?? 0))
			{
				return $member_info->profile_image->src ?? '';
			}
		}
		return '';
	}
	public function getNickName() : string
	{
		$member_srl = (INT)$this->get('member_srl');
		if($member_srl > 0)
		{
			$member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl);
			if(is_object($member_info) && ($member_info->member_srl ?? 0))
			{
				return strip_tags($member_info->nick_name);
			}
		}
		return '[?]';
	}
	function getRegdate($format = 'Y.m.d H:i:s', $conversion = true) : string
	{
		return date($format, strtotime($this->get('regdate')));
	}
}
