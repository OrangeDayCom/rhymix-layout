<?php
//url 로 직접 불러오는것을 방지
if ( !strstr($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) && !strstr($_SERVER['HTTP_REFERER'], 'update_extra_vars.php') )
{
	exit;
}

include '../../common/autoload.php';
Context::init();

$module_srl = Context::get('module_srl');
$logged_info = Context::get('logged_info');
if ( $logged_info->is_admin !== 'Y' )
{
	$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
	$grant = ModuleModel::getGrant($module_info, $logged_info);
	if ( !$grant->manager )
	{
		exit;
	}
}

$obj = new stdClass();
$obj->module_srl = $module_srl;
$obj->document_srl = $document_srl = Context::get('document_srl');
$obj->category_srl = $category_srl = Context::get('category_srl');
$obj->valueHTML = '';

$oDocumentController = getController('document');

if ( isset($category_srl) )
{
	$output = executeQuery('addons.ap_extra_update.updateDocumentCategorySrl', $obj);

	if ( $output->toBool() )
	{
		$category_list = DocumentModel::getCategoryList($module_srl);
		$category_info = $category_list[$category_srl];
		if ( $category_srl )
		{
			$obj->valueHTML = '<span';
			if ( $category_info->color && $category_info->color !== 'transparent' )
			{
				$obj->valueHTML .= ' style="color:' . $category_info->color . '"';
			}
			$obj->valueHTML .= '>' . $category_info->title . '</span>';

			$oDocumentController->updateCategoryCount($module_srl, $category_srl);
		}

		$obj->old_category_srl = Context::get('old_category_srl');
		$oDocumentController->updateCategoryCount($module_srl, $obj->old_category_srl);
		$oDocumentController->clearDocumentCache($document_srl);
		$obj->category_list = DocumentModel::getCategoryList($module_srl);
	}
}
else
{
	$obj->var_idx = $var_idx = Context::get('var_idx');
	$obj->value = $value = Context::get('value');

	$extra_keys = DocumentModel::getExtraKeys($module_srl);
	$obj->is_required = $is_required = $extra_keys[$var_idx]->is_required;

	if ( $is_required === 'Y' && !isset($value) )
	{
		exit;
	}

	if ( isset($value) && $value )
	{
		$output = $oDocumentController->updateDocumentExtraVar($module_srl, $document_srl, $var_idx, $value);
		if ( !$output->toBool() )
		{
			exit;
		}

		$extra_vars = DocumentModel::getExtraVars($module_srl, $document_srl);
		$obj->valueHTML = $extra_vars[$var_idx]->getValueHTML();
	}
	else
	{
		$output = $oDocumentController->deleteDocumentExtraVars($module_srl, $document_srl, $var_idx);
		if ( !$output->toBool() )
		{
			exit;
		}
	}
}

echo json_encode($obj);

Context::close();
?>