<?php

/**
 * @file ap_extra_update.addon.php
 * @author cydemo <cydemo@gmail.com>
 */

if ( !defined('RX_VERSION') )
{
	return;
}

if ( $called_position === 'after_module_proc' && $this->grant->manager && $this->module === 'board' && $this->act === 'dispBoardContent' )
{
	$use_category_update = $use_extra_vars_update = false;

	$category_list = Context::get('category_list');
	
//Chat GPT 수정 2025-07-17	
//if ( $addon_info->category !== 'N' && isset($category_list) && !empty($category_list) && $this->module_info->hide_category !== 'Y' )
if (
    property_exists($addon_info, 'category') &&
    $addon_info->category !== 'N' &&
    isset($category_list) &&
    !empty($category_list) &&
    $this->module_info->hide_category !== 'Y'
)
	
	{
		$use_category_update = true;
	}

	$extra_keys = Context::get('extra_keys');
	
//Chat GPT 수정 2025-07-17		
//if ( $addon_info->eid !== 'N' && !empty($extra_keys) )
if (
    property_exists($addon_info, 'eid') &&
    $addon_info->eid !== 'N' &&
    !empty($extra_keys)
)
	
	{
		$use_extra_vars_update = true;
	}
	if ( $use_extra_vars_update )
	{
		$eid_list = array_map('trim', explode(',', $addon_info->eids));
		$extra_keys_for_update = array();
		foreach ( $this->listConfig as $key => $val )
		{
			if ( strpos($key, 'extra_vars') !== false )
			{
				$extra_idx = str_replace('extra_vars', '', $key);
				if ( !empty($eid_list) )
				{
					if ( in_array($extra_keys[$extra_idx]->eid, $eid_list) )
					{
						$extra_keys_for_update[$extra_idx] = $extra_keys[$extra_idx];
					}
				}
				else
				{
					$extra_keys_for_update[$extra_idx] = $extra_keys[$extra_idx];
				}
			}
		}
		if ( empty($extra_keys_for_update) )
		{
			$use_extra_vars_update = false;
		}
	}

//Chat GPT 수정 2025-07-17	
//	Context::set('extra_keys_for_update', $extra_keys_for_update);
if (isset($extra_keys_for_update)) {
    Context::set('extra_keys_for_update', $extra_keys_for_update);
}

	Context::set('use_list_update', false);
	Context::set('use_category_update', false);
	Context::set('use_extra_vars_update', false);
	if ( $use_category_update || $use_extra_vars_update )
	{
		Context::set('use_list_update', true);
		Context::set('use_category_update', $use_category_update);
		Context::set('use_extra_vars_update', $use_extra_vars_update);
	}

	unset($category_list);
	unset($extra_keys);
	unset($eid_list);
	unset($extra_keys_for_update);
}

if ( $called_position === 'before_display_content' && Context::getResponseMethod() === 'HTML' && Context::get('grant')->manager && Context::get('use_list_update') )
{
	// 애드온 스킨 파일의 경로 확인
	$tpl_file = 'update.html';
	$addon_info->skin = file_exists('./addons/ap_extra_update/skins/' . $addon_info->skin . '/' . $tpl_file) ? $addon_info->skin : 'default';
	$tpl_path = './addons/ap_extra_update/skins/' . $addon_info->skin;

	// 애드온 스킨 파일을 컴파일
	$oTemplate = &TemplateHandler::getInstance();
	$tpl = $oTemplate->compile($tpl_path, $tpl_file);

	$output = $output . $tpl;
}