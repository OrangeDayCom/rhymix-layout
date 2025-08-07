<?php
/**
 * @class  starpointView
 * @author 80san
 * @brief 별점(평점) 모듈의 View 클래스
 */
class starpointView extends starpoint
{
	/**
	 * @brief 초기화
	 */
	public function init()
	{
		// Set template path
		$this->setTemplatePath($this->module_path.'tpl');
	}

	/**
	 * @brief 평점 위젯 표시
	 */
	function dispStarpointRating()
	{
		// Get document_srl from request
		$document_srl = Context::get('document_srl');
		if(!$document_srl) return new BaseObject(-1, 'msg_invalid_request');

		// Get document info
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		if(!$oDocument->isExists()) return new BaseObject(-1, 'msg_invalid_document');

		// Get current logged in user info
		$logged_info = Context::get('logged_info');
		$is_logged = Context::get('is_logged');

		// Get rating information
		$output = $this->getStarpointInfo($document_srl);
		
		// Set variables for template
		Context::set('document_srl', $document_srl);
		Context::set('rating_avg', $output->get('rating_avg') ? $output->get('rating_avg') : 0);
		Context::set('rating_count', $output->get('rating_count') ? $output->get('rating_count') : 0);
		Context::set('is_logged', $is_logged);
		
		// Check if user already rated
		$already_rated = false;
		if($is_logged) {
			$args = new stdClass();
			$args->document_srl = $document_srl;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('starpoint.getRatingByMember', $args);
			$already_rated = ($output->data) ? true : false;
		}
		Context::set('already_rated', $already_rated);

		// Set template file
		$this->setTemplateFile('rating');
		
		// Return success
		return new BaseObject();
	}
	
	/**
	 * @brief 문서의 평점 정보 가져오기
	 * @param int $document_srl 문서 일련번호
	 * @return BaseObject 평점 정보를 포함하는 객체
	 */
	function getStarpointInfo($document_srl)
	{
		// Initialize
		$args = new stdClass();
		$args->document_srl = $document_srl;
		
		// Get rating information from database
		$output = executeQuery('starpoint.getRatingInfo', $args);
		if(!$output->toBool()) return $output;
		
		// Return the rating information
		return $output;
	}
}