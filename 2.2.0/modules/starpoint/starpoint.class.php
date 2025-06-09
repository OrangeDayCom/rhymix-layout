<?php

/**
 * 게시글 별점리뷰
 *
 * Copyright (c) ICETEA
 *
 * Generated with https://www.poesis.dev/tools/modulegen
 */
class Starpoint extends ModuleObject
{
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
	public function createObject($status = 0, $message = 'success' /* $arg1, $arg2 ... */)
	{
		$args = func_get_args();
		if (count($args) > 2)
		{
			global $lang;
			$message = vsprintf($lang->$message, array_slice($args, 2));
		}
		return class_exists('BaseObject') ? new BaseObject($status, $message) : new Object($status, $message);
	}

	/**
	 * 모듈 설치 콜백 함수.
	 *
	 * 트리거 등록 외에 따로 할 일이 없다면 수정할 필요 없다.
	 *
	 * @return object
	 */
	public function moduleInstall()
	{
		// 트리거 등록
		$oModuleController = getController('module');
		
		// 문서 출력 시 별점 표시 트리거
		$oModuleController->insertTrigger('document.getDocumentEnd', 'starpoint', 'controller', 'triggerGetDocumentEnd', 'after');
		
		// 별점 모듈 템플릿에 추가할 트리거
		$oModuleController->insertTrigger('document.showDocument', 'starpoint', 'controller', 'triggerShowDocument', 'after');
		
		return new BaseObject();
	}

	/**
	 * 모듈 업데이트 확인 콜백 함수.
	 *
	 * 트리거 등록 외에 따로 할 일이 없다면 수정할 필요 없다.
	 *
	 * @return bool
	 */
	public function checkUpdate()
	{
		$oModuleModel = getModel('module');
		
		// 문서 출력 시 별점 표시 트리거 확인
		if(!$oModuleModel->getTrigger('document.getDocumentEnd', 'starpoint', 'controller', 'triggerGetDocumentEnd', 'after'))
			return true;
		
		// 별점 모듈 템플릿에 추가할 트리거 확인
		if(!$oModuleModel->getTrigger('document.showDocument', 'starpoint', 'controller', 'triggerShowDocument', 'after'))
			return true;

		$oDB = &DB::getInstance();
		if(!$oDB->isColumnExists('document_star', 'regdate')) return true;

		return false;
	}

	/**
	 * 모듈 업데이트 콜백 함수.
	 *
	 * 트리거 등록 외에 따로 할 일이 없다면 수정할 필요 없다.
	 *
	 * @return object
	 */
	public function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		
		// 문서 출력 시 별점 표시 트리거 등록
		if(!$oModuleModel->getTrigger('document.getDocumentEnd', 'starpoint', 'controller', 'triggerGetDocumentEnd', 'after'))
			$oModuleController->insertTrigger('document.getDocumentEnd', 'starpoint', 'controller', 'triggerGetDocumentEnd', 'after');
		
		// 별점 모듈 템플릿에 추가할 트리거 등록
		if(!$oModuleModel->getTrigger('document.showDocument', 'starpoint', 'controller', 'triggerShowDocument', 'after'))
			$oModuleController->insertTrigger('document.showDocument', 'starpoint', 'controller', 'triggerShowDocument', 'after');

		$oDB = &DB::getInstance();
		if(!$oDB->isColumnExists('document_star', 'regdate')) 
		{
			$oDB->addColumn('document_star', 'regdate', 'date', 'idx_regdate');
		}

		return new BaseObject();
	}

	/**
	 * 캐시파일 재생성 콜백 함수.
	 *
	 * @return void
	 */
	public function recompileCache()
	{
		// 별점 모듈 설정 캐시 삭제
		FileHandler::removeFilesInDir('./files/cache/starpoint');
	}
}