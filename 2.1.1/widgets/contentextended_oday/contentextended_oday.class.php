<?php
    /**
     * @class contentextended
     * @author EPMakes (support@epmakes.com)
     * @brief content를 출력하는 content 확장위젯
			  원작: Content 위젯(NHN, developers@xpressengine.com)
     * @version 2.43
     **/

    class contentextended_oday extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/

        function proc($args) {
            // 정렬 대상
            if(!in_array($args->order_target, array('list_order','update_order','regdate','rand()','voted_count','readed_count'))) $args->order_target = 'list_order';
			//if ($args->order_target == 'random') $args->order_target = 'rand()';

            // 정렬 순서
            if(!in_array($args->order_type, array('asc','desc'))) $args->order_type = 'asc';

            // 페이지 수
            $args->page_count = (int)$args->page_count;
            if(!$args->page_count) $args->page_count = 1;

            // 출력된 목록 수
            $args->list_count = (int)$args->list_count;
            if(!$args->list_count) $args->list_count = 5;

            // 썸네일 컬럼 수
            $args->cols_list_count = (int)$args->cols_list_count;
            if(!$args->cols_list_count) $args->cols_list_count = 5;

            // 제목 길이 자르기
            if(!$args->subject_cut_size) $args->subject_cut_size = 0;

            // 내용 길이 자르기
            if(!$args->content_cut_size) $args->content_cut_size = 100;

            // 최근 글 표시 시간
            if(!$args->duration_new) $args->duration_new = 12;

            // 썸네일 생성 방법
            if(!$args->thumbnail_type) $args->thumbnail_type = 'crop';

            // 썸네일 가로 크기
            if(!$args->thumbnail_width) $args->thumbnail_width = 100;

            // 썸네일 세로 크기
            if(!$args->thumbnail_height) $args->thumbnail_height = 75;

            // 보기 옵션
            $args->option_view_arr = explode(',',$args->option_view);

			// 새글 항상 출력
			if(!$args->show_always_new) $args->show_always_new = 'N';

            // markup 옵션
            if(!$args->markup_type) $args->markup_type = 'table';

			// 컨텐츠 조건 설정
			if(!$args->use_limit) $args->use_limit = 'N';

			// 컨텐츠 조건 범위
			if(!$args->limit_number) $args->limit_number = 20;

			// 포인트 레벨 표시
			if(!$args->show_point_level) $args->show_point_level = 'N';

			// 댓글의 비밀글 처리방식 설정
			if(!$args->comment_document_secret) $args->comment_document_secret = 'Y';

			// 댓글 없는 게시글만 보이기 기능
			if(!$args->show_nocomment_document) $args->show_nocomment_document = 'N';

			// 댓글의 게시글 이름 출력(v2.4)
			if(!$args->show_content_title) $args->show_content_title = 'N';

			// 컨텐츠 시간 범위
			if(!$args->duration_article) $args->duration_article = 0;
			
			// 탭 형태
			if(!$args->tab_type) $args->tab_type = 'none';
			if(!$args->tab_order) $args->tab_order = 'module_order';
			if(!$args->tab_showtype) $args->tab_showtype = 'module';
			if(!$args->tab_move_type) $args->tab_move_type = 'mouseover';

			// 링크 방식 관련(v2.4)
			if(!$args->hyperlink) $args->hyperlink = 'Y';
			if(!$args->hyperlink_src) $args->hyperlink_src = 'article';
			if(!$args->hyperlink_type) $args->hyperlink_type = 'currentwindow';

			// 특정 분류만 출력(v2.4)
			if($args->category_srl) {
				$args->specific_category = true;
				$args->specific_category_srl_list = explode(',', $args->category_srl);
			}
			else $args->specific_category = false;

			// 표시 권한
			$args->view_permission_arr = explode(',',$args->view_permission);
			$vp_view = false; $vp_list = false; $vp_write_document = false; $vp_write_comment = false;
			if (count($args->view_permission_arr)) {
				foreach($args->view_permission_arr as $kk => $val) {
					if ($val == 'list') $vp_list = true;
					else if ($val == 'view') $vp_view = true;
					else if ($val == 'write_document') $vp_write_document = true;
					else if ($val == 'write_comment') $vp_write_comment = true;
				}
			}
			$vp_exist = false;
			if ($vp_view || $vp_list || $vp_write_document || $vp_write_comment) $vp_exist = true;

			// 비밀글 표시 방법
			if (!$args->view_secret_document) $args->view_secret_document = 'all_user';

            // 내부적으로 쓰이는 변수 설정
            $oModuleModel = &getModel('module');
            $module_srls = $args->modules_info = $args->module_srls_info = $args->mid_lists = array();
            $site_module_info = Context::get('site_module_info');

            // rss 인 경우 URL정리
            if($args->content_type == 'rss'){
                $args->rss_urls = array();
                $rss_urls = array_unique(array($args->rss_url0,$args->rss_url1,$args->rss_url2,$args->rss_url3,$args->rss_url4));
                for($i=0,$c=count($rss_urls);$i<$c;$i++) {
                    if($rss_urls[$i]) $args->rss_urls[] = $rss_urls[$i];
                }

            // rss가 아닌 XE모듈일 경우 모듈 번호 정리 후 모듈 정보 구함
            } else {

                // 대상 모듈이 선택되어 있지 않으면 해당 사이트의 전체 모듈을 대상으로 함
                if(!$args->module_srls){
                    unset($obj);
					$obj = new stdClass();
                    $obj->site_srl = (int)$site_module_info->site_srl;
                    $output = executeQueryArray('widgets.contentextended_oday.getMids', $obj);
                    if($output->data) {
                        foreach($output->data as $key => $val) {
							// 권한 체크
							$gg = $oModuleModel->getGrant($oModuleModel->getModuleInfoByModuleSrl($val->module_srl), $_SESSION['logged_info']);
							if ($vp_exist && !(($vp_view && $gg->view) || ($vp_list && $gg->list) ||
								($vp_write_document && $gg->write_document) ||
								($vp_write_comment && $gg->write_comment))) continue;

                            $args->modules_info[$val->mid] = $val;
                            $args->module_srls_info[$val->module_srl] = $val;
                            $args->mid_lists[$val->module_srl] = $val->mid;
                            $module_srls[] = $val->module_srl;
                        }
                    }

                    $args->modules_info = $oModuleModel->getMidList($obj);
                // 대상 모듈이 선택되어 있으면 해당 모듈만 추출
                } else {
					$obj = new stdClass();
                    $obj->module_srls = $args->module_srls;
                    $output = executeQueryArray('widgets.contentextended_oday.getMids', $obj);
                    if($output->data) {
                        foreach($output->data as $key => $val) {
                            $args->modules_info[$val->mid] = $val;
                            $args->module_srls_info[$val->module_srl] = $val;
                            //$module_srls[] = $val->module_srl;
                        }
                        $idx_bef = explode(',',$args->module_srls);
						
						// 모듈 정보로부터 권한 체크
						$idx_aft = '';
						foreach($idx_bef as $kk => $msrl) {
							$gg = $oModuleModel->getGrant($oModuleModel->getModuleInfoByModuleSrl($msrl), $_SESSION['logged_info']);
							if ($vp_exist && !(($vp_view && $gg->view) || ($vp_list && $gg->list) ||
								($vp_write_document && $gg->write_document) ||
								($vp_write_comment && $gg->write_comment))) continue;

							if ($idx_aft != '') $idx_aft = $idx_aft . ',';
							$idx_aft = $idx_aft . $msrl;
						}
						$idx = explode(',',$idx_aft);

                        for($i=0,$c=count($idx);$i<$c;$i++) {
                            $srl = $idx[$i];
                            if(!$args->module_srls_info[$srl]) continue;
                            $args->mid_lists[$srl] = $args->module_srls_info[$srl]->mid;
							$module_srls[] = $srl;
                        }
                    }
                }

                // 아무런 모듈도 검색되지 않았다면 종료
                if(!count($args->modules_info) || !count($args->mid_lists)) return Context::get('msg_not_founded');
                $args->module_srl = implode(',',$module_srls);
            }

            /**
             * 컨텐츠 추출, 게시글/댓글/엮인글/RSS등 다양한 요소가 있어서 각 method를 따로 만듬
             **/
            // tab 형태가 아닐 경우
            if($args->tab_type == 'none' || $args->tab_type == '') {
                switch($args->content_type){
                    case 'comment':
                            $content_items = $this->_getCommentItems($args);
                        break;
                    case 'image':
                            $content_items = $this->_getImageItems($args);
                        break;
                    case 'rss':
                            $content_items = $this->getRssItems($args);
                        break;
                    case 'trackback':
                            $content_items = $this->_getTrackbackItems($args);
                        break;
                    default:
                            $content_items = $this->_getDocumentItems($args);
                        break;
                }
            // tab 형태일 경우
            } else {
                $content_items = array();

                switch($args->content_type){
                    case 'comment':
                            foreach($args->mid_lists as $module_srl => $mid){
                                $args->module_srl = $module_srl;
                                $content_items[$module_srl] = $this->_getCommentItems($args);
                            }
                        break;
                    case 'image':
                            foreach($args->mid_lists as $module_srl => $mid){
                                $args->module_srl = $module_srl;
                                $content_items[$module_srl] = $this->_getImageItems($args);
                            }
                        break;
                    case 'rss':
                            $content_items = $this->getRssItems($args);
                        break;
                    case 'trackback':
                            foreach($args->mid_lists as $module_srl => $mid){
                                $args->module_srl = $module_srl;
                                $content_items[$module_srl] = $this->_getTrackbackItems($args);
                            }
                        break;
                    default:
							// 카테고리로 정렬하는 경우
							if($args->tab_showtype == 'category') {
								foreach($args->mid_lists as $module_srl => $mid){
									$args->category_srl = null;
									$args->module_srl = $module_srl;
									$obj->module_srl = $args->module_srl;
									$output = executeQueryArray('widgets.contentextended_oday.getCategories',$obj);
									if($output->toBool() && $output->data) {
										foreach($output->data as $key => $val) {
											// 최상위 여부 파악(v2.4)
											if ($args->category_range == 'first' && $val->parent_srl) continue;
											// 특정 분류만 출력 여부 파악(v2.4)
											if ($args->specific_category && !in_array($val->category_srl, $args->specific_category_srl_list)) continue;

											$args->category_srl = $val->category_srl;
											$content_items[$args->category_srl] = $this->_getDocumentItems($args);
										}
									}
								}

							// 모듈로 정렬하는 경우
							} else {
								foreach($args->mid_lists as $module_srl => $mid){
									$args->module_srl = $module_srl;
									$content_items[$module_srl] = $this->_getDocumentItems($args);
								}
							}
                        break;
                }
            }

            $output = $this->_compile($args,$content_items);
            return $output;
        }

        /**
         * @brief 댓글 목록을 추출하여 contentextended_odayItem으로 return
         **/
        function _getCommentItems($args) {
            $oCommentModel = &getModel('comment');
			if ($args->comment_document_secret == 'Y') $oDocumentModel = &getModel('document');

            // 최신 시간 설정
            $time_check = date("YmdHis", time()-$args->duration_new * 60 * 60);
			$duration_article_check = date("YmdHis", time()-$args->duration_article * 60 * 60);

            // Comment를 가져오기 위한 변수 정리
			$obj = new stdClass();
            $obj->module_srl = $args->module_srl;
            $obj->sort_index = $args->order_target;
			// 댓글은 update_order가 지원되지 않음(v2.4)
			if ($obj->sort_index == "update_order") $obj->sort_index = "list_order";
            $obj->order_type = $args->order_type=="desc"?"asc":"desc";
			$obj->list_count = 300;
			$obj->duration_article = $args->duration_article?$duration_article_check:null;

			// 연속 가져오기 설정
			$pagecount = 0;
			$needcount = $args->list_count * $args->page_count; // 가져와야 하는 글 개수(목록 수 * 페이지 수)
			$getcount = 0; // 가져온 글 개수
            $content_items = array();
			$result = array();
            $first_thumbnail_idx = -1;
			while(true) {
				$pagecount = $pagecount + 1;
				$obj->page = $pagecount;
				$output = executeQueryArray('widgets.contentextended_oday.getNewestCommentList', $obj);
				// 더이상 결과가 없으면 break
				if(!$output->toBool() || !$output->data || !count($output->data)) break;

				$comment_list = $output->data;
				if($comment_list) {
					if(!is_array($comment_list)) $comment_list = array($comment_list);
					$comment_count = count($comment_list);
					foreach($comment_list as $key => $attribute) {
						if(!$attribute->comment_srl) continue;
						$oComment = null;
						$oComment = new commentItem();
						$oComment->setAttribute($attribute);

						// 해당 문서가 비밀글일 경우 권한이 있는지 확인, 없으면 continue
						if ($args->view_secret_document == 'use_permission' || $args->view_secret_document == 'not_show') {
							if ($args->view_secret_document == 'use_permission' && $oComment->isSecret() && !$oComment->isGranted()) continue;
							else if ($args->view_secret_document == 'not_show' && $oComment->isSecret()) continue;
							
							if ($args->comment_document_secret == 'Y') {
								$doobj = null;
								$doobj = new stdClass();
								$doobj->document_srl = $oComment->get('document_srl');
								$output = executeQuery('widgets.contentextended_oday.getDocument', $doobj);
								$oDocument = new documentItem();
								$oDocument->setAttribute($output->data, false);
								if ($oDocument->isSecret()) continue;
							}
						}

						// 컨텐츠 조건범위 확인
						if ($args->use_limit == 'voted_count_upper' && $oComment->get('voted_count') < $args->limit_number) continue;
						else if ($args->use_limit == 'voted_count_lower' && $oComment->get('voted_count') > $args->limit_number) continue;
						else if ($args->use_limit == 'readed_count_upper' && $oComment->get('readed_count') < $args->limit_number) continue;
						else if ($args->use_limit == 'readed_count_lower' && $oComment->get('readed_count') > $args->limit_number) continue;

						// 컨텐츠 시간범위 확인
						if ($args->duration_article != 0 && $oComment->get('regdate')<=$duration_article_check) continue;

						$result[$key] = $oComment;
						$getcount = $getcount + 1;
						if ($getcount >= $needcount) break;
					}
				}
				if ($getcount >= $needcount) break;
				if ($pagecount >= $output->total_page) break;
			}

			$use_point_level = 0;
			if ($args->show_point_level == "Y") {
				$oModuleModel = &getModel('module');
				$oPointModel = &getModel('point');
				$point_config = $oModuleModel->getModuleConfig('point');
				$use_point_level = 1;
			}

            foreach($result as $key => $oComment) {
                $attribute = $oComment->getObjectVars();
                $title = $oComment->getSummary($args->content_cut_size);
                $thumbnail = $oComment->getThumbnail($args->thumbnail_width,$args->thumbnail_height,$args->thumbnail_type);
                $url = sprintf("%s#comment_%s",getUrl('','document_srl',$oComment->get('document_srl')),$oComment->get('comment_srl'));

                $attribute->mid = $args->mid_lists[$attribute->module_srl];
                $browser_title = $args->module_srls_info[$attribute->module_srl]->browser_title;
                $domain = $args->module_srls_info[$attribute->module_srl]->domain;

                $content_item = new contentextended_odayItem($browser_title);
                $content_item->adds($attribute);

				if ($args->title_target == 'title') $content_item->setTitle($title);
				else if ($args->title_target == 'nickname') $content_item->setTitle($oComment->get('nick_name'));
				else if ($args->title_target == 'content') $content_item->setTitle($title);
				else $content_item->setTitle($title);

				// 글쓴이 대상 추가(v2.4)
				if ($args->nickname_target == 'nickname') $content_item->setNickName($oComment->get('nick_name'));
				else if ($args->nickname_target == 'userid') $content_item->setNickName($oComment->get('user_id'));
				else if ($args->nickname_target == 'username') $content_item->setNickName($oComment->get('user_name'));
				else $content_item->setNickName($oComment->get('nick_name'));

				// 링크 방식 변경(v2.4)
				if ($args->hyperlink == 'N') $content_item->setLink("#");
                else $content_item->setLink($url);

				// 댓글의 게시글 정보 가져오기(v2.4)
				if ($args->show_content_title == 'Y') {
					$docu_args->document_srl = $oComment->get('document_srl');
					$docu_output = executeQuery('widgets.contentextended_oday.getDocument', $docu_args);
					if ($docu_output->data) {
						$content_item->setDocumentTitle($docu_output->data->title);
						$content_item->setDocumentURL(getUrl('','document_srl',$oComment->get('document_srl')));
					}
				}

				if ($use_point_level) $content_item->setPointLevel($oPointModel->getLevel($oPointModel->getPoint($oComment->get('member_srl')), $point_config->level_step));

                $content_item->setThumbnail($thumbnail);
                $content_item->setDomain($domain);
                $content_item->setExtraImages($this->_printExtraImages($oComment->get('content'), $oComment->isSecret(), $oComment->get('regdate'), $oComment->get('last_update'), $oComment->hasUploadedFiles(), $args->duration_new * 60 * 60));
				$content_item->setVotedCount($oComment->get('voted_count'));
				$content_item->setReadedCount('0');
                $content_item->add('mid', $args->mid_lists[$attribute->module_srl]);
                $content_items[] = $content_item;
            }
            return $content_items;
        }

        function _getDocumentItems($args){
            // document 모듈의 model 객체를 받아서 결과를 객체화 시킴
            $oDocumentModel = &getModel('document');

            // 분류 구함
			$obj = new stdClass();
            $obj->module_srl = $args->module_srl;
            $output = executeQueryArray('widgets.contentextended_oday.getCategories',$obj);
            if($output->toBool() && $output->data) {
                foreach($output->data as $key => $val) {
					// 최상위 여부 파악(v2.4)
					if ($args->category_range == 'first' && $val->parent_srl) continue;
					// 특정 분류만 출력 여부 파악(v2.4)
					if ($args->specific_category && !in_array($val->category_srl, $args->specific_category_srl_list)) continue;

                    $category_lists[$val->module_srl][$val->category_srl] = $val;
                }
            }

            // 최신 시간 설정
            $time_check = date("YmdHis", time()-$args->duration_new * 60 * 60);
			$duration_article_check = date("YmdHis", time()-$args->duration_article * 60 * 60);

            // 글 목록을 구함 (+권한 확인)
            $obj->module_srl = $args->module_srl;
            $obj->sort_index = $args->order_target;
            $obj->order_type = $args->order_type=="desc"?"asc":"desc";
			$obj->list_count = 100; // 한번에 가져올 글 목록 개수
			$obj->duration_article = $args->duration_article?$duration_article_check:null;
			if ($args->category_srl) $obj->category_srl = $args->category_srl;
			$pagecount = 0;
			$needcount = $args->list_count * $args->page_count; // 가져와야 하는 글 개수(목록 수 * 페이지 수)
			$getcount = 0; // 가져온 글 개수
            $content_items = array();
            $first_thumbnail_idx = -1;
			while(true) {
				$pagecount = $pagecount + 1;
				$obj->page = $pagecount;
//2025-0403 랜덤 추가				
if($obj->sort_index=='rand()') {
    $output = executeQueryArray('widgets.contentextended_oday.getNewestDocumentsRandom', $obj);
}
else {
    $output = executeQueryArray('widgets.contentextended_oday.getNewestDocuments', $obj);
}
				// 더이상 결과가 없으면 break
				if(!$output->toBool() || !$output->data || !count($output->data)) break;
				// 권한 확인해서 문서 목록 생성하기
                foreach($output->data as $key => $attribute) {
                    $oDocument = new documentItem();
                    $oDocument->setAttribute($attribute, false);

					if ($getcount >= $needcount) {
						if ($args->show_always_new == 'N') break;
						if ($oDocument->get('regdate')<=$time_check) break;
					} 

					// 해당 문서가 비밀글일 경우 권한이 있는지 확인, 없으면 continue
					if ($args->view_secret_document == 'use_permission' && $oDocument->isSecret() && !$oDocument->isGranted()) continue;
					else if ($args->view_secret_document == 'not_show' && $oDocument->isSecret()) continue;
					
//2025-0405 임시글 감추기 추가	
if($oDocument->get('status')== 'TEMP') continue;

					// 컨텐츠 조건범위 확인
					if ($args->use_limit == 'voted_count_upper' && $oDocument->get('voted_count') < $args->limit_number) continue;
					else if ($args->use_limit == 'voted_count_lower' && $oDocument->get('voted_count') > $args->limit_number) continue;
					else if ($args->use_limit == 'readed_count_upper' && $oDocument->get('readed_count') < $args->limit_number) continue;
					else if ($args->use_limit == 'readed_count_lower' && $oDocument->get('readed_count') > $args->limit_number) continue;

					// 컨텐츠 시간범위 확인
					if ($args->duration_article != 0 && $oDocument->get('regdate')<=$duration_article_check) continue;

					// 댓글 없는 게시글만 가져오기일 경우 댓글이 있는지 확인
					if ($args->show_nocomment_document == 'Y') {
						if ($oDocument->get('comment_count') != 0) continue;
					}

					// 권한이 있다면 객체화 준비
                    $GLOBALS['XE_DOCUMENT_LIST'][$oDocument->document_srl] = $oDocument;
                    $document_srls[] = $oDocument->document_srl;
					$getcount = $getcount + 1;
					if ($getcount >= $needcount && $args->show_always_new == 'N') break;
                }
				if ($getcount >= $needcount && $args->show_always_new == 'N') break;
				if ($pagecount >= $output->total_page) break;
			}

            // 결과가 있으면 각 문서 객체화를 시킴
            if($getcount > 0) {
                $oDocumentModel->setToAllDocumentExtraVars();

				$use_point_level = 0;
				if ($args->show_point_level == "Y") {
					$oModuleModel = &getModel('module');
					$oPointModel = &getModel('point');
					$point_config = $oModuleModel->getModuleConfig('point');
					$use_point_level = 1;
				}

                for($i=0,$c=count($document_srls);$i<$c;$i++) {
                    $oDocument = $GLOBALS['XE_DOCUMENT_LIST'][$document_srls[$i]];
                    $document_srl = $oDocument->document_srl;
                    $module_srl = $oDocument->get('module_srl');
                    $category_srl = $oDocument->get('category_srl');
                    $thumbnail = $oDocument->getThumbnail($args->thumbnail_width,$args->thumbnail_height,$args->thumbnail_type);

                    $content_item = new contentextended_odayItem( $args->module_srls_info[$module_srl]->browser_title );
                    $content_item->adds($oDocument->getObjectVars());

					if ($args->title_target == 'title') $content_item->setTitle($oDocument->getTitle());
					else if ($args->title_target == 'nickname') $content_item->setTitle($oDocument->get('nick_name'));
					else if ($args->title_target == 'content') $content_item->setTitle($oDocument->getSummary($args->content_cut_size));
					else $content_item->setTitle($oDocument->getTitle());

					// 글쓴이 대상 추가(v2.4)
					if ($args->nickname_target == 'nickname') $content_item->setNickName($oDocument->get('nick_name'));
					else if ($args->nickname_target == 'userid') $content_item->setNickName($oDocument->get('user_id'));
					else if ($args->nickname_target == 'username') $content_item->setNickName($oDocument->get('user_name'));
					else $content_item->setNickName($oDocument->get('nick_name'));

					if ($use_point_level) $content_item->setPointLevel($oPointModel->getLevel($oPointModel->getPoint($oDocument->get('member_srl')), $point_config->level_step));

                    $content_item->setTitle($oDocument->getTitle());
                    $content_item->setCategory( $category_lists[$module_srl][$category_srl]->title );
                    $content_item->setDomain( $args->module_srls_info[$module_srl]->domain );
                    $content_item->setContent($oDocument->getSummary($args->content_cut_size));

					// 링크 방식 변경(v2.4)
					if ($args->hyperlink == 'N') $content_item->setLink("#");
					else if($args->hyperlink_src == 'article') $content_item->setLink( getSiteUrl($domain,'','document_srl',$document_srl) );
					else if($args->hyperlink_src == 'extravar') $content_item->setLink($oDocument->getExtraEidValue($args->hyperlink_extravar));
					else $content_item->setLink( getSiteUrl($domain,'','document_srl',$document_srl) );

                    $content_item->setThumbnail($thumbnail);
					$content_item->setVotedCount($oDocument->get('voted_count'));
					$content_item->setReadedCount($oDocument->get('readed_count'));
					//$content_item->setExtraImages($this->_printExtraImages($oDocument->get('content'), $oDocument->isSecret(), $oDocument->get('regdate'), $oDocument->get('last_update'), $oDocument->hasUploadedFiles(), $args->duration_new * 60 * 60));
                    $content_item->setExtraImages($oDocument->printExtraImages($args->duration_new * 60 * 60));
					if ($args->extravar_name) $content_item->setExtraVar($oDocument->getExtraEidValue($args->extravar_name));
					if ($args->extravar2_name) $content_item->setExtraVar2($oDocument->getExtraEidValue($args->extravar2_name));
					if ($args->extravar3_name) $content_item->setExtraVar3($oDocument->getExtraEidValue($args->extravar3_name));
					if ($args->extravar4_name) $content_item->setExtraVar4($oDocument->getExtraEidValue($args->extravar4_name));
					if ($args->extravar5_name) $content_item->setExtraVar5($oDocument->getExtraEidValue($args->extravar5_name));
                    $content_item->add('mid', $args->mid_lists[$module_srl]);
                    if($first_thumbnail_idx==-1 && $thumbnail) $first_thumbnail_idx = $i;
                    $content_items[] = $content_item;
                }

                $content_items[0]->setFirstThumbnailIdx($first_thumbnail_idx);
            }
            return $content_items;
        }

        function _getImageItems($args) {
            $oDocumentModel = &getModel('document');

            $obj->module_srls = $obj->module_srl = $args->module_srl;
            $obj->direct_download = 'Y';
            $obj->isvalid = 'Y';

            // 분류 구함
            $output = executeQueryArray('widgets.contentextended_oday.getCategories',$obj);
            if($output->toBool() && $output->data) {
                foreach($output->data as $key => $val) {
                    $category_lists[$val->module_srl][$val->category_srl] = $val;
                }
            }

            // 정해진 모듈에서 문서별 파일 목록을 구함
            $obj->list_count = $args->list_count * $args->page_count;
            $files_output = executeQueryArray("file.getOneFileInDocument", $obj);
            $files_count = count($files_output->data);
            if(!$files_count) return;

            $content_items = array();

            for($i=0;$i<$files_count;$i++) $document_srl_list[] = $files_output->data[$i]->document_srl;

            $tmp_document_list = $oDocumentModel->getDocuments($document_srl_list);

            if(!count($tmp_document_list)) return;

            foreach($tmp_document_list as $oDocument){
                $attribute = $oDocument->getObjectVars();
                $browser_title = $args->module_srls_info[$attribute->module_srl]->browser_title;
                $domain = $args->module_srls_info[$attribute->module_srl]->domain;
                $category = $category_lists[$attribute->module_srl]->text;
                $content = $oDocument->getSummary($args->content_cut_size);
                $url = sprintf("%s#%s",$oDocument->getPermanentUrl() ,$oDocument->getCommentCount());
                $thumbnail = $oDocument->getThumbnail($args->thumbnail_width,$args->thumbnail_height,$args->thumbnail_type);
                $extra_images = $oDocument->printExtraImages($args->duration_new);

                $content_item = new contentextended_odayItem($browser_title);
                $content_item->adds($attribute);

				// 글쓴이 대상 추가(v2.4)
				if ($args->nickname_target == 'nickname') $content_item->setNickName($oDocument->get('nick_name'));
				else if ($args->nickname_target == 'userid') $content_item->setNickName($oDocument->get('user_id'));
				else if ($args->nickname_target == 'username') $content_item->setNickName($oDocument->get('user_name'));
				else $content_item->setNickName($oDocument->get('nick_name'));

                $content_item->setCategory($category);
                $content_item->setContent($content);

				// 링크 방식 변경(v2.4)
				if ($args->hyperlink == 'N') $content_item->setLink("#");
                else $content_item->setLink($url);

                $content_item->setThumbnail($thumbnail);
                $content_item->setExtraImages($extra_images);
                $content_item->setDomain($domain);
                $content_item->add('mid', $args->mid_lists[$attribute->module_srl]);
                $content_items[] = $content_item;
            }

            return $content_items;
        }

        function getRssItems($args){

            $content_items = array();
            $args->mid_lists = array();

            foreach($args->rss_urls as $key => $rss){
                $args->rss_url = $rss;
                $content_item = $this->_getRssItems($args);
                if(count($content_item) > 0){
                    $browser_title = $content_item[0]->getBrowserTitle();
                    $args->mid_lists[] = $browser_title;
                    $content_items[] = $content_item;
                }
            }

            // 탭 형태가 아닐 경우
            if($args->tab_type == 'none' || $args->tab_type == ''){
                $items = array();
                foreach($content_items as $key => $val){
                    foreach($val as $k => $v){
                        $date = $v->get('regdate');
                        $i=0;
                        while(array_key_exists(sprintf('%s%02d',$date,$i), $items)) $i++;
                        $items[sprintf('%s%02d',$date,$i)] = $v;
                    }
                }
                if($args->order_type =='asc') ksort($items);
                else krsort($items);
                $content_items = array_slice(array_values($items),0,$args->list_count*$args->page_count);

            // 탭 형태
            } else {
                foreach($content_items as $key=> $content_item_list){
                    $items = array();
                    foreach($content_item_list as $k => $content_item){
                        $date = $content_item->get('regdate');
                        $i=0;
                        while(array_key_exists(sprintf('%s%02d',$date,$i), $items)) $i++;
                        $items[sprintf('%s%02d',$date,$i)] = $content_item;
                    }
                    if($args->order_type =='asc') ksort($items);
                    else krsort($items);

                    $content_items[$key] = array_values($items);
                }
            }
            return $content_items;
        }

        function _getRssBody($value) {
            if(!$value || is_string($value)) return $value;
            if(is_object($value)) $value = get_object_vars($value);
            $body = null;
            if(!count($value)) return;
            foreach($value as $key => $val) {
                if($key == 'body') {
                    $body = $val;
                    continue;
                }
                if(is_object($val)||is_array($val)) $body = $this->_getRssBody($val);
                if($body !== null) return $body;
            }
            return $body;
        }

        function _getSummary($content, $str_size = 50)
        {
            $content = preg_replace('!(<br[\s]*/{0,1}>[\s]*)+!is', ' ', $content);

            // </p>, </div>, </li> 등의 태그를 공백 문자로 치환
            $content = str_replace(array('</p>', '</div>', '</li>'), ' ', $content);

            // 태그 제거
            $content = preg_replace('!<([^>]*?)>!is','', $content);

            // < , > , " 를 치환
            $content = str_replace(array('&lt;','&gt;','&quot;','&nbsp;'), array('<','>','"',' '), $content);

            // 연속된 공백문자 삭제
            $content = preg_replace('/ ( +)/is', ' ', $content);

            // 문자열을 자름
            $content = trim(cut_str($content, $str_size, $tail));

            // >, <, "를 다시 복구
            $content = str_replace(array('<','>','"'),array('&lt;','&gt;','&quot;'), $content);

            // 영문이 연결될 경우 개행이 안 되는 문제를 해결
            $content = preg_replace('/([a-z0-9\+:\/\.\~,\|\!\@\#\$\%\^\&\*\(\)\_]){20}/is',"$0-",$content);
            return $content; 
        }


       /**
         * @brief rss 주소로 부터 내용을 받아오는 함수
         * tistory 의 경우 원본 주소가 location 헤더를 뿜는다. (내용은 없음)이를 해결하기 위한 수정 - rss_reader 위젯과 방식 동일
         **/
        function requestFeedContents($rss_url) {
            $rss_url = str_replace('&amp;','&',Context::convertEncodingStr($rss_url));
            return FileHandler::getRemoteResource($rss_url, null, 3, 'GET', 'application/xml');
        }

        function _getRssItems($args){
            // 날짜 형태
            $DATE_FORMAT = $args->date_format ? $args->date_format : "Y-m-d H:i:s";

            $buff = $this->requestFeedContents($args->rss_url);

            $encoding = preg_match("/<\?xml.*encoding=\"(.+)\".*\?>/i", $buff, $matches);
            if($encoding && !preg_match("/UTF-8/i", $matches[1])) $buff = Context::convertEncodingStr($buff);

            $buff = preg_replace("/<\?xml.*\?>/i", "", $buff);

            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse($buff);
            if($xml_doc->rss) {
                $rss->title = $xml_doc->rss->channel->title->body;
                $rss->link = $xml_doc->rss->channel->link->body;

                $items = $xml_doc->rss->channel->item;

                if(!$items) return;
                if($items && !is_array($items)) $items = array($items);

                $content_items = array();

                foreach ($items as $key => $value) {
                    if($key >= $args->list_count * $args->page_count) break;
                    unset($item);

                    foreach($value as $key2 => $value2) {
                        if(is_array($value2)) $value2 = array_shift($value2);
                        $item->{$key2} = $this->_getRssBody($value2);
                    }

                    $content_item = new contentextended_odayItem($rss->title);
                    $content_item->setContentsLink($rss->link);
                    $content_item->setTitle($item->title);
                    $content_item->setNickName(max($item->author,$item->{'dc:creator'}));
                    //$content_item->setCategory($item->category);
                    $item->description = preg_replace('!<a href=!is','<a onclick="window.open(this.href);return false" href=', $item->description);
                    $content_item->setContent($this->_getSummary($item->description, $args->content_cut_size));

					// 링크 방식 변경(v2.4)
					if ($args->hyperlink == 'N') $content_item->setLink("#");
					else $content_item->setLink($item->link);

                    $date = date('YmdHis', strtotime(max($item->pubdate,$item->pubDate,$item->{'dc:date'})));
                    $content_item->setRegdate($date);

                    $content_items[] = $content_item;
                }
            } elseif($xml_doc->{'rdf:rdf'}) {
                // rss1.0 지원 (Xml이 대소문자를 구분해야 하는데 XE의 XML파서가 전부 소문자로 바꾸는 바람에 생긴 case) by misol
                $rss->title = $xml_doc->{'rdf:rdf'}->channel->title->body;
                $rss->link = $xml_doc->{'rdf:rdf'}->channel->link->body;

                $items = $xml_doc->{'rdf:rdf'}->item;

                if(!$items) return;
                if($items && !is_array($items)) $items = array($items);

                $content_items = array();

                foreach ($items as $key => $value) {
                    if($key >= $args->list_count * $args->page_count) break;
                    unset($item);

                    foreach($value as $key2 => $value2) {
                        if(is_array($value2)) $value2 = array_shift($value2);
                        $item->{$key2} = $this->_getRssBody($value2);
                    }

                    $content_item = new contentextended_odayItem($rss->title);
                    $content_item->setContentsLink($rss->link);
                    $content_item->setTitle($item->title);
                    $content_item->setNickName(max($item->author,$item->{'dc:creator'}));
                    //$content_item->setCategory($item->category);
                    $item->description = preg_replace('!<a href=!is','<a onclick="window.open(this.href);return false" href=', $item->description);
                    $content_item->setContent($this->_getSummary($item->description, $args->content_cut_size));
                    $content_item->setLink($item->link);
                    $date = date('YmdHis', strtotime(max($item->pubdate,$item->pubDate,$item->{'dc:date'})));
                    $content_item->setRegdate($date);

                    $content_items[] = $content_item;
                }
            } elseif($xml_doc->feed && $xml_doc->feed->attrs->xmlns == 'http://www.w3.org/2005/Atom') {
                // Atom 1.0 spec 지원 by misol
                $rss->title = $xml_doc->feed->title->body;
                $links = $xml_doc->feed->link;
                if(is_array($links)) {
                    foreach ($links as $value) {
                        if($value->attrs->rel == 'alternate') {
                            $rss->link = $value->attrs->href;
                            break;
                        }
                    }
                }
                elseif($links->attrs->rel == 'alternate') $rss->link = $links->attrs->href;

                $items = $xml_doc->feed->entry;

                if(!$items) return;
                if($items && !is_array($items)) $items = array($items);

                $content_items = array();

                foreach ($items as $key => $value) {
                    if($key >= $args->list_count * $args->page_count) break;
                    unset($item);

                    foreach($value as $key2 => $value2) {
                        if(is_array($value2)) $value2 = array_shift($value2);
                        $item->{$key2} = $this->_getRssBody($value2);
                    }

                    $content_item = new contentextended_odayItem($rss->title);
                    $links = $value->link;
                    if(is_array($links)) {
                        foreach ($links as $val) {
                            if($val->attrs->rel == 'alternate') {
                                $item->link = $val->attrs->href;
                                break;
                            }
                        }
                    }
                    elseif($links->attrs->rel == 'alternate') $item->link = $links->attrs->href;

                    $content_item->setContentsLink($rss->link);
                    if($item->title) {
                        if(!preg_match("/html/i", $value->title->attrs->type)) $item->title = $value->title->body;
                    }
                    $content_item->setTitle($item->title);
                    $content_item->setNickName(max($item->author,$item->{'dc:creator'}));
                    $content_item->setAuthorSite($value->author->uri->body);
                    //$content_item->setCategory($item->category);
                    $item->description = preg_replace('!<a href=!is','<a onclick="window.open(this.href);return false" href=', $item->content);
                    if($item->description) {
                        if(!preg_match("/html/i", $value->content->attrs->type)) $item->description = htmlspecialchars($item->description);
                    }
                    if(!$item->description) {
                        $item->description = $item->summary;
                        if($item->description) {
                            if(!preg_match("/html/i", $value->summary->attrs->type)) $item->description = htmlspecialchars($item->description);
                        }
                    }
                    $content_item->setContent($this->_getSummary($item->description, $args->content_cut_size));
                    $content_item->setLink($item->link);
                    $date = date('YmdHis', strtotime(max($item->published,$item->updated,$item->{'dc:date'})));
                    $content_item->setRegdate($date);

                    $content_items[] = $content_item;
                }
            }
            return $content_items;
        }

        function _getTrackbackItems($args){
            // 분류 구함
            $output = executeQueryArray('widgets.contentextended_oday.getCategories',$obj);
            if($output->toBool() && $output->data) {
                foreach($output->data as $key => $val) {
                    $category_lists[$val->module_srl][$val->category_srl] = $val;
                }
            }

            $obj->module_srl = $args->module_srl;
            $obj->sort_index = $args->order_target;
            $obj->list_count = $args->list_count * $args->page_count;

            // trackback 모듈의 model 객체를 받아서 getTrackbackList() method를 실행
            $oTrackbackModel = &getModel('trackback');
            $output = $oTrackbackModel->getNewestTrackbackList($obj);

            // 오류가 생기면 그냥 무시
            if(!$output->toBool() || !$output->data) return;

            // 결과가 있으면 각 문서 객체화를 시킴
            $content_items = array();
            foreach($output->data as $key => $item) {
                $domain = $args->module_srls_info[$item->module_srl]->domain;
                $category = $category_lists[$item->module_srl]->text;
                $url = getSiteUrl($domain,'','document_srl',$item->document_srl);
                $browser_title = $args->module_srls_info[$item->module_srl]->browser_title;

                $content_item = new contentextended_odayItem($browser_title);
                $content_item->adds($item);
                $content_item->setTitle($item->title);
                $content_item->setCategory($category);
                $content_item->setNickName($item->blog_name);
                $content_item->setContent($item->excerpt);  ///<<
                $content_item->setDomain($domain);  ///<<
				
				// 링크 방식 변경(v2.4)
				if ($args->hyperlink == 'N') $content_item->setLink("#");
				else $content_item->setLink($url);

                $content_item->add('mid', $args->mid_lists[$item->module_srl]);
                $content_item->setRegdate($item->regdate);
                $content_items[] = $content_item;
            }
            return $content_items;
        }

        // 범용 아이콘 출력 함수
        function _getExtraImages($content, $is_secret, $regdate, $last_update, $upload_file, $time_interval=43200) {
            // 아이콘 목록을 담을 변수 미리 설정
            $buffs = array();

            $check_files = false;

            //$content = $citem->get('content');

            // 비밀글 체크
            if($is_secret) $buffs[] = "secret";

            // 최신 시간 설정
            $time_check = date("YmdHis", time()-$time_interval);

            // 새글 체크
            if($regdate>$time_check) $buffs[] = "new";
            else if($last_update>$time_check) $buffs[] = "update";

            // 사진 이미지 체크
            preg_match_all('!<img([^>]*?)>!is', $content, $matches);
            $cnt = count($matches[0]);
            for($i=0;$i<$cnt;$i++) {
                if(preg_match('/editor_component=/',$matches[0][$i])&&!preg_match('/image_(gallery|link)/i',$matches[0][$i])) continue;
                $buffs[] = "image";
                $check_files = true;
                break;
            }

            // 동영상 체크
            if(preg_match('!<embed([^>]*?)>!is', $content) || preg_match('/editor_component=("|\')*multimedia_link/i', $content) ) {
                $buffs[] = "movie";
                $check_files = true;
            }

            // 첨부파일 체크
            if($upload_file) $buffs[] = "file";

            return $buffs;
        }

        // getExtraImages로 구한 값을 이미지 태그를 씌워서 리턴
        function _printExtraImages($content, $is_secret, $regdate, $last_update, $upload_file, $time_check = 43200) {
            // 아이콘 디렉토리 구함
            $path = sprintf('%s%s',getUrl(), 'modules/document/tpl/icons/');

            $buffs = $this->_getExtraImages($content, $is_secret, $regdate, $last_update, $upload_file, $time_check);
            if(!count($buffs)) return;

            $buff = null;
            foreach($buffs as $key => $val) {
                $buff .= sprintf('<img src="%s%s.gif" alt="%s" title="%s" style="margin-right:2px;" />', $path, $val, $val, $val);
            }
            return $buff;
        }


        function _compile($args,$content_items){
            $oTemplate = &TemplateHandler::getInstance();

            // 위젯에 넘기기 위한 변수 설정
			$widget_info = new stdClass();
            $widget_info->modules_info = $args->modules_info;
            $widget_info->option_view_arr = $args->option_view_arr;
            $widget_info->list_count = $args->list_count;
            $widget_info->page_count = $args->page_count;
            $widget_info->subject_cut_size = $args->subject_cut_size;
            $widget_info->content_cut_size = $args->content_cut_size;

            $widget_info->duration_new = $args->duration_new * 60*60;
            $widget_info->thumbnail_type = $args->thumbnail_type;
            $widget_info->thumbnail_width = $args->thumbnail_width;
            $widget_info->thumbnail_height = $args->thumbnail_height;
            $widget_info->cols_list_count = $args->cols_list_count;
            $widget_info->mid_lists = $args->mid_lists;

            $widget_info->tab_move_type = $args->tab_move_type;

            $widget_info->show_browser_title = $args->show_browser_title;
			$widget_info->show_content_title = $args->show_content_title;
            $widget_info->show_category = $args->show_category;
            $widget_info->show_comment_count = $args->show_comment_count;
            $widget_info->show_trackback_count = $args->show_trackback_count;
            $widget_info->show_icon = $args->show_icon;
			$widget_info->show_always_new = $args->show_always_new;
			$widget_info->show_point_level = $args->show_point_level;
			if ($args->show_point_level == "Y") {
				$oModuleModel = &getModel('module');
				$point_config = $oModuleModel->getModuleConfig('point');
				$widget_info->point_level_icon = $point_config->level_icon;
			}

            $widget_info->list_type = $args->list_type;
            $widget_info->tab_type = $args->tab_type;

            $widget_info->markup_type = $args->markup_type;

			//추가 변수
			$widget_info->show_main_title = $args->show_main_title;
			$widget_info->show_main_title_icon = $args->show_main_title_icon;
			$widget_info->go_url = $args->go_url;
			$widget_info->view_mode = $args->view_mode;
			$widget_info->view_mode_ex = $args->view_mode_ex;
			$widget_info->man_width = $args->man_width;
			$widget_info->man_gap = $args->man_gap;
			$widget_info->content_type = $args->content_type;
			$widget_info->show_thumbnail = $args->show_thumbnail;
			$widget_info->show_title = $args->show_title;
			$widget_info->show_contents = $args->show_contents;
			$widget_info->show_all_meta = $args->show_all_meta;
			$widget_info->show_nickname = $args->show_nickname;
			$widget_info->show_regdate = $args->show_regdate;
			$widget_info->show_read_count = $args->show_read_count;
			$widget_info->show_vote_count = $args->show_vote_count;
			$widget_info->duration_article = $args->duration_article;
			
			
			
            // 탭형태일경우 탭에 대한 정보를 정리하고 module_srl로 되어 있는 key값을 index로 변경
            if($args->tab_type != 'none' && $args->tab_type) {
                $tab = array();
				$datego = array();

				// 최근 글순 탭정렬일 경우
				if($args->tab_order == 'recent_order') {
					foreach($content_items as $module_srl => $val){
						if(!is_array($content_items[$module_srl]) || !count($content_items[$module_srl])) continue;
						$datego[$module_srl] = $content_items[$module_srl][0]->getRegdate();
					}
					arsort($datego);
					
					foreach($datego as $module_srl => $regdate) {
						if(!is_array($content_items[$module_srl]) || !count($content_items[$module_srl])) continue;

						unset($tab_item);
						if ($args->tab_showtype == 'category')
							$tab_item->title = $content_items[$module_srl][0]->getCategory();
						else 
							$tab_item->title = $content_items[$module_srl][0]->getBrowserTitle();
						$tab_item->content_items = $content_items[$module_srl];
						$tab_item->domain = $content_items[$module_srl][0]->getDomain();
						if ($args->tab_showtype == 'category') 
							$tab_item->url = getSiteUrl($tab_item->domain, '','mid',$content_items[$module_srl][0]->getMid(),'category',$module_srl);
						else {
							$tab_item->url = $content_items[$module_srl][0]->getContentsLink();
							if(!$tab_item->url) $tab_item->url = getSiteUrl($tab_item->domain, '','mid',$args->mid_lists[$module_srl]);
						}
						$tab[] = $tab_item;
					}
				} else {
					foreach($content_items as $module_srl => $val) {
						if(!is_array($content_items[$module_srl]) || !count($content_items[$module_srl])) continue;

						unset($tab_item);
						if ($args->tab_showtype == 'category')
							$tab_item->title = $content_items[$module_srl][0]->getCategory();
						else 
							$tab_item->title = $content_items[$module_srl][0]->getBrowserTitle();
						$tab_item->content_items = $content_items[$module_srl];
						$tab_item->domain = $content_items[$module_srl][0]->getDomain();
						if ($args->tab_showtype == 'category') 
							$tab_item->url = getSiteUrl($tab_item->domain, '','mid',$content_items[$module_srl][0]->getMid(),'category',$module_srl);
						else {
							$tab_item->url = $content_items[$module_srl][0]->getContentsLink();
							if(!$tab_item->url) $tab_item->url = getSiteUrl($tab_item->domain, '','mid',$args->mid_lists[$module_srl]);
						}
						$tab[] = $tab_item;
					}
				}
                $widget_info->tab = $tab;
            } else {
                $widget_info->content_items = $content_items;
            }
            unset($args->option_view_arr);
            unset($args->modules_info);

            Context::set('colorset', $args->colorset);
            Context::set('widget_info', $widget_info);

            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            return $oTemplate->compile($tpl_path, "content");
        }
    }

    class contentextended_odayItem extends BaseObject {

        var $browser_title = null;
        var $document_title = null;
        var $document_url = null;
        var $has_first_thumbnail_idx = false;
        var $first_thumbnail_idx = null;
        var $contents_link = null;
        var $domain = null;
		var $extra_var = null;
		var $extra_var2 = null;
		var $extra_var3 = null;
		var $extra_var4 = null;
		var $extra_var5 = null;
		var $point_level = null;

	function __construct($browser_title='')
	{
		$this->browser_title = $browser_title;
	}
        function setContentsLink($link){
            $this->contents_link = $link;
        }
        function setFirstThumbnailIdx($first_thumbnail_idx){
            if(is_null($this->first_thumbnail) && $first_thumbnail_idx>-1) {
                $this->has_first_thumbnail_idx = true;
                $this->first_thumbnail_idx= $first_thumbnail_idx;
            }
        }
        function setExtraImages($extra_images){
            $this->add('extra_images',$extra_images);
        }
        function setDomain($domain) {
            static $default_domain = null;
            if(!$domain) {
                if(is_null($default_domain)) $default_domain = Context::getDefaultUrl();
                $domain = $default_domain;
            }
            $this->domain = $domain;
        }
        function setLink($url){
            $this->add('url',$url);
        }
        function setTitle($title){
            $this->add('title',$title);
        }

        function setThumbnail($thumbnail){
            $this->add('thumbnail',$thumbnail);
        }
        function setContent($content){
            $this->add('content',$content);
        }
        function setRegdate($regdate){
            $this->add('regdate',$regdate);
        }
        function setNickName($nick_name){
            $this->add('nick_name',$nick_name);
        }
		function setVotedCount($voted_count){
			$this->add('voted_count',$voted_count);
		}
		function setReadedCount($readed_count){
			$this->add('readed_count',$readed_count);
		}
		function setExtraVar($extravar){
			$this->extra_var = $extravar;
		}
		function setExtraVar2($extravar){
			$this->extra_var2 = $extravar;
		}
		function setExtraVar3($extravar){
			$this->extra_var3 = $extravar;
		}
		function setExtraVar4($extravar){
			$this->extra_var4 = $extravar;
		}
		function setExtraVar5($extravar){
			$this->extra_var5 = $extravar;
		}
		function setPointLevel($pointlevel){
			$this->point_level = $pointlevel;
		}

        // 글 작성자의 홈페이지 주소를 저장 by misol
        function setAuthorSite($site_url){
            $this->add('author_site',$site_url);
        }
        function setCategory($category){
            $this->add('category',$category);
        }
		function setCategorySrl($category_srl){
			$this->add('category_srl',$category_srl);
		}

		// 댓글의 게시물 관련 정보(v2.4)
		function setDocumentTitle($document_title){
			$this->document_title = $document_title;
		}
		function setDocumentURL($document_url){
			$this->document_url = $document_url;
		}
		function getDocumentTitle(){
			return $this->document_title;
		}
		function getDocumentURL(){
			return $this->document_url;
		}

        function getBrowserTitle(){
            return $this->browser_title;
        }
        function getDomain() {
            return $this->domain;
        }
        function getContentsLink(){
            return $this->contents_link;
        }

        function getFirstThumbnailIdx(){
            return $this->first_thumbnail_idx;
        }

        function getLink(){
            return $this->get('url');
        }
        function getModuleSrl(){
            return $this->get('module_srl');
        }
		function getMid(){
            return $this->get('mid');
		}
        function getTitle($cut_size = 0, $tail='...'){
            $title = strip_tags($this->get('title'));

            if($cut_size) $title = cut_str($title, $cut_size, $tail);

            $attrs = array();
            if($this->get('title_bold') == 'Y') $attrs[] = 'font-weight:bold';
            if($this->get('title_color') && $this->get('title_color') != 'N') $attrs[] = 'color:#'.$this->get('title_color');

            if(count($attrs)) $title = sprintf("<span style=\"%s\">%s</span>", implode(';', $attrs), htmlspecialchars($title));

            return $title;
        }
        function getContent(){
            return $this->get('content');
        }
        function getCategory(){
            return $this->get('category');
        }
		function getCategorySrl(){
			return $this->get('category_srl');
		}
        function getNickName(){
            return $this->get('nick_name');
        }
        function getAuthorSite(){
            return $this->get('author_site');
        }
        function getCommentCount(){
            $comment_count = $this->get('comment_count');
            return $comment_count>0 ? $comment_count : '';
        }
        function getTrackbackCount(){
            $trackback_count = $this->get('trackback_count');
            return $trackback_count>0 ? $trackback_count : '';
        }
        function getRegdate($format = 'Y.m.d H:i:s') {
            return zdate($this->get('regdate'), $format);
        }
        function printExtraImages() {
            return $this->get('extra_images');
        }
        function haveFirstThumbnail() {
            return $this->has_first_thumbnail_idx;
        }
        function getThumbnail(){
            return $this->get('thumbnail');
        }
        function getMemberSrl() {
            return $this->get('member_srl');
        }
		function getVotedCount() {
			return $this->get('voted_count');
		}
		function getReadedCount() {
			return $this->get('readed_count');
		}
		function getExtraVar() {
			return $this->extra_var;
		}
		function getExtraVar2() {
			return $this->extra_var2;
		}
		function getExtraVar3() {
			return $this->extra_var3;
		}
		function getExtraVar4() {
			return $this->extra_var4;
		}
		function getExtraVar5() {
			return $this->extra_var5;
		}
		function getPointLevel() {
			return $this->point_level;
		}
    }
?>
