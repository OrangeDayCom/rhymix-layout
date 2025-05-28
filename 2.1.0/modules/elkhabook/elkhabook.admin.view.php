<?php

/**
 * Elkha Book
 *
 * Copyright (c) 엘카
 *
 * Generated with https://www.poesis.org/tools/modulegen/
 */
class ElkhabookAdminView extends Elkhabook
{
	/**
	 * 초기화
	 */
	public function init() : \baseObject
	{
		// 관리자 화면 템플릿 경로 지정
		$this->setTemplatePath($this->module_path . 'tpl');
		return $this;
	}

	/**
	 * 관리자 설정 화면 예제
	 */
	public function dispElkhabookAdminConfig(): \baseObject
	{
		$oDB = \DB::getInstance();
		$index_info = $oDB->getIndexInfo('comments', 'idx_member_srl');
		\Context::set('comments_index_info', $index_info);

		// 현재 설정 상태 불러오기
		$config = $this->getConfig();

		// Context에 세팅
		Context::set('elkhabook_config', $config);

		// 스킨 파일 지정
		$this->setTemplateFile('config');
		return $this;
	}
}
