<?php

class ap_form extends WidgetHandler
{
	function proc($args) {
		Context::loadLang($this->widget_path."lang");

		// 선택된 모듈 번호로 mid값 가져오기
		$oModuleModel = getModel('module');
		$target_module_info = $oModuleModel->getModuleInfoByModuleSrl($args->target_module_srl);

		if ( $target_module_info->module ==='board' )
		{
			$args->target_mid = $target_module_info->mid;

			// 분류 사용 방식에 따라 '문의 유형' 관련 변수 정리
			$use = isset($args->use_category) ? $args->use_category : null;
			$print = false;
			$srls = isset($args->target_category_srls) ? $args->target_category_srls : 0;
			$list = array();

			if ( $use !== 'N' )
			{
				$oDocumentModel = getModel('document');

				// 게시판의 분류를 모두 활용
				if ( $use === 'A' )
				{
					$category_list = $oDocumentModel->getCategoryList($args->target_module_srl);
					if ( count($category_list) )
					{
						foreach ( $category_list as $val )
						{
							$list[$val->category_srl] = $val->title;
						}
						$srls = implode(', ', array_keys($list));
					}
				}
				// 게시판의 분류를 일부만 활용
				else
				{
					// 입력된 값에서 숫자만 추출하여 배열 변수로 할당
					preg_match_all('/[0-9]+/', $srls, $matches);

					if ( count($matches[0]) )
					{
						if ( count($matches[0]) === 1 )
						{
							$category_info = $oDocumentModel->getCategory($matches[0][0]);
							if ( $category_info )
							{
								$srls = $matches[0][0];
								$list[$matches[0][0]] = $category_info->title;
							}
						}
						else
						{
							$category_list = $oDocumentModel->getCategoryList($args->target_module_srl);
							if ( count($category_list) )
							{
								foreach ( $category_list as $val )
								{
									if ( in_array($val->category_srl, $matches[0]) )
									{
										$list[$val->category_srl] = $val->title;
									}
								}
							}
							$srls = implode(', ', array_keys($list));
						}
					}
				}

				// 활용가능한 분류가 2개 이상으로 확인
				if ( count($list) > 1 )
				{
					$print = true;
				}
			}
			
			$args->use_category = $use;
			$args->has_category = $print;
			$args->target_category_srls = $srls;
			$args->target_category_list = $list;

			if ( !isset($args->use_phone) )
			{
				$args->use_phone = 'R';
			}

			if ( !isset($args->use_international) )
			{
				$args->use_international = 'N';
			}

			// 회원정보->전화번호 변수 활용
			$logged_info = Context::get('logged_info');
			$num = $logged_info->phone_number ?? '';
			$member_config = MemberModel::getMemberConfig();
			$args->countries = '';

			if ( $args->use_international === 'Y' && $member_config->phone_number_hide_country !== 'Y' )
			{
				$state = $logged_info->phone_country ?? '';
				$default_country = $state;
				if ( !$default_country && $member_config->phone_number_default_country )
				{
					$default_country = $member_config->phone_number_default_country;
				}
				if ( $default_country && !preg_match('/^[A-Z]{3}$/', $default_country) )
				{
					$default_country = Rhymix\Framework\i18n::getCountryCodeByCallingCode($default_country);
				}
				if ( !$default_country && Context::get('lang_type') === 'ko' )
				{
					$default_country = 'KOR';
				}

				$country_list = Rhymix\Framework\i18n::listCountries(Context::get('lang_type') === 'ko' ? Rhymix\Framework\i18n::SORT_NAME_KOREAN : Rhymix\Framework\i18n::SORT_NAME_ENGLISH);
				$inputTag = '<select name="phone_country" id="phone_country" class="phone_country"' . ($state ? ' disabled' : '') . '>';
				if ( $state )
				{
					$inputTag .= '<option value="' . $state . '">';
					$inputTag .= escape(Context::get('lang_type') === 'ko' ? $country_list[$state]->name_korean : $country_list[$state]->name_english) . ' (+' . $country_list[$state]->calling_code . ')</option>';
				}
				else
				{
					$inputTag .= '<option value="">' . Context::getLang("international_phone_number") . '</option>';
					foreach ( $country_list as $country )
					{
						if ( $country->calling_code )
						{
							$inputTag .= '<option value="' . $country->iso_3166_1_alpha3 . '"' . ($country->iso_3166_1_alpha3 === $default_country ? ' selected="selected"' : '') . '>';
							$inputTag .= escape(Context::get('lang_type') === 'ko' ? $country->name_korean : $country->name_english) . ' (+' . $country->calling_code . ')</option>';
						}
					}
				}
				$inputTag .= '</select>' . "\n";
				$args->countries = $inputTag;
				
				if ( $state === 'KOR' )
				{
					$phone_number = \Rhymix\Framework\Korea::formatPhoneNumber($num);
				}
				elseif ( $state === 'USA' )
				{
					$digits = preg_replace('/[^0-9]/', '', $num);
					$phone_number = substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
				}
				else
				{
					$phone_number = $num;
				}

				$args->international = true;
				$args->default_country = $default_country;
				$args->phone_number = $phone_number;
				$args->calling_number = \Rhymix\Framework\i18n::formatPhoneNumber($num, $state, false);
			}
			else
			{
				$args->international = false;
				$args->phone_number = $args->calling_number = $num;
			}


			// 사용자 정의 확장변수 관련 변수 정리
			$use = isset($args->use_extra_keys) ? $args->use_extra_keys : 'N';
			$print = false;
			$keys = isset($args->target_extra_keys) ? $args->target_extra_keys : null;
			$list = array();

			if ( $use !== 'N' )
			{
				$oDocumentModel = getModel('document');
				$extra_keys = $oDocumentModel->getExtraKeys($args->target_module_srl);

				// 확장변수 모두 활용
				if ( $use === 'A' )
				{
					$keys = '';
					if ( count($extra_keys) > 0 )
					{
						$print = true;
						foreach ( $extra_keys as $key => $val )
						{
							if ( empty($keys) )
							{
								$keys = $val->eid;
							}
							else
							{
								$keys = $keys . ', ' . $val->eid;
							}
						}
						$list = $extra_keys;
					}
				}
				// 일부만 활용
				else
				{
					$key_arr = array_map('trim', explode(',', $keys));
					$keys = '';
					if ( count($key_arr) > 0 && count($extra_keys) > 0 )
					{
						foreach ( $extra_keys as $key => $val )
						{
							if ( in_array($val->eid, $key_arr) )
							{
								if ( empty($keys) )
								{
									$keys = $val->eid;
								}
								else
								{
									$keys = $keys . ', ' . $val->eid;
								}
								$list[$key] = $val;
							}
						}
						if ( count($list) > 0 )
						{
							$print = true;
						}
						if ( count($list) === count($extra_keys) )
						{
							$use = 'A';
							$keys = null;
						}
					}
				}
			}
			
			$args->use_extra_keys = $use;
			$args->has_extra = $print;
			$args->target_extra_keys = $keys;
			$args->target_extra_list = $list;

			// 기타 변수 정리
			if ( !isset($args->use_email) )
			{
				$args->use_email = 'R';
			}
			if ( !isset($args->use_content) )
			{
				$args->use_content = 'N';
			}
			if ( !isset($args->use_password) )
			{
				$args->use_password = 'R';
			}
			if ( $args->use_password != 'O' )
			{
				$args->rand_password = rand(10000, 99999);
			}
			if ( !isset($args->privacy) )
			{
				$args->privacy = 'N';
			}
			if ( $args->privacy != 'N' )
			{
				$privacy_titles = array();
				$privacy_descs = array();
				if ( strpos($args->privacy_title, '\n') !== false )
				{
					$privacy_titles = array_map('trim', explode('\n', $args->privacy_title));
					if ( $args->privacy == 'I' && !empty($args->privacy_desc) )
					{
						$privacy_descs = array_map('trim', explode('\n', $args->privacy_desc));
					}
					else if ( $args->privacy == 'F' && !empty($args->privacy_file) )
					{
						$privacy_descs = array_map('trim', explode('\n', $args->privacy_file));
					}
					else
					{
						$privacy_descs = $privacy_titles;
					}
				}
				else
				{
					$privacy_titles = array(trim($args->privacy_title));
					if ( $args->privacy == 'I' && !empty($args->privacy_desc) )
					{
						$privacy_descs = array(trim($args->privacy_desc));
					}
					else if ( $args->privacy == 'F' && !empty($args->privacy_file) )
					{
						$privacy_descs = array(trim($args->privacy_file));
					}
					else
					{
						$privacy_descs = $privacy_titles;
					}
				}
				$args->privacy_title_arr = $privacy_titles;
				$args->privacy_desc_arr = $privacy_descs;
			}
			if ( !isset($args->redirect) )
			{
				$args->redirect = 'N';
			}
			if ( $args->redirect != 'U' )
			{
				$args->redirect_url = '';
			}
			else if ( $args->redirect == 'U' && $args->redirect_url == '' )
			{
				$args->redirect = 'N';
			}
			if ( !isset($args->use_recaptcha) )
			{
				$args->use_recaptcha = 'N';
			}

			// 전체 변수 총정리
			Context::set('colorset', $args->colorset);
			Context::set('wi', $args);

			// 템플릿 컴파일
			$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
			$tpl_file = 'form';

			$oTemplate = TemplateHandler::getInstance();
			return $oTemplate->compile($tpl_path, $tpl_file);
		}
		else
		{
			//  ERROR!! 템플릿 컴파일
			$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
			$tpl_file = 'error';

			$oTemplate = TemplateHandler::getInstance();
			return $oTemplate->compile($tpl_path, $tpl_file);
		}
	}
}
?>