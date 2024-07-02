<?php
    /**
     * @class orange_traffic_ViewerCafe24
     * @author 
     * @version 
     * @Cafe24 트래픽 현황을 출력하는 위젯
     *
     **/

    class orange_traffic_ViewerCafe24 extends WidgetHandler {

            /**
             * @brief 위젯의 실행 부분
             * ./widgets/위젯/conf/info.xml에 선언한 extra_vars를 args로 받는다
             * 결과를 만든후 print가 아니라 return 해주어야 한다
             **/

            function proc($args){

				//트래픽
				//
				
				$trafficview_url = $args->trafficview_url;
				$trafficview = $trafficview_url;
				ini_set("allow_url_fopen","1");
				$file = file($trafficview);
				$widget_info = new stdClass();
				//$percent = sprintf(strip_tags($file[33]));
				if (empty(sprintf(strip_tags($file[33])))) {
					$percent = 1;
				} else {
					$percent =  sprintf(strip_tags($file[33]));
				}					
				//$hit = sprintf(strip_tags($file[34]));
				if (empty(sprintf(strip_tags($file[34])))) {
					$hit = 1;
				} else {
					$hit = sprintf(strip_tags($file[34]));
				}
				//$sent = sprintf(strip_tags($file[35]));
				if (empty(sprintf(strip_tags($file[35])))) {
					$sent = 1;
				} else {
					$sent =  sprintf(strip_tags($file[35]));
				}				
				$limit = sprintf(strip_tags($file[36]));
				$sent = $sent/1024;
				$widget_info->limit = (int)($limit);
				$widget_info->percent = (int)($percent);
				$widget_info->hit = $hit;
				$widget_info->sent = ((int)($sent*100))/100;
				if($sent == 0) {
					$widget_info->hitpersend = 1;
				} else {
					$widget_info->hitpersend = ((int)($sent/$hit*1000))/100;
				}
				//
				//트래픽 끝


				//하드
				//Simulz (k10206@naver.com) 님 소스입니다.				
				if(!$args->user_space) $args->user_space = 500;
				$save = (int)$args->user_space; //할당받은 계정용량, 단위 MBytes 
				set_time_limit(0);
				$du_cgibin = `du -s ./`; 
				$du_root = `du -s ../`;
				if($du_root) {
					$du_m = (int)$du_root;
				} else {
					$du_m = (int)$du_cgibin;
				}
				$du_m = $du_m/1000;
				$percent_hdd = $du_m/$save*100;
				$percentage_hdd = (int)($percent_hdd);
				if($percentage_hdd < 1) $percentage_hdd = 1;
				if($percentage_hdd > 100) $percentage_hdd = 99;
				$left_hdd = 100-$percentage_hdd;

				$args->percent_hdd = ((int)($percent_hdd*100))/100;
				$args->percentage_hdd = $percentage_hdd;
				$args->left_hdd = $left_hdd;
				$args->used_space = ((int)($du_m*100))/100;

				//
				//하드 끝


				// 템플릿 파일에서 사용할 변수들을 세팅
				Context::set('obj', $args);
                Context::set('widget_info', $widget_info);

                // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
                $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);

                // 템플릿 파일을 지정
                $tpl_file = 'viewer';

                // 템플릿 컴파일
                $oTemplate = &TemplateHandler::getInstance();
                $output = $oTemplate->compile($tpl_path, $tpl_file);
                return $output;
            }
    }
?>