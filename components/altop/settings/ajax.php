<?define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader,
	Bitrix\Main\Application;

$moduleClass = "CElektroinstrument";
$moduleID = "altop.elektroinstrument";

if(!Loader::IncludeModule($moduleID))
	return;

$request = Application::getInstance()->getContext()->getRequest();

if($request->isAjaxRequest() && $request->isPost() && $request->getPost("CHANGE_THEME") == "Y" && check_bitrix_sessid()) {	
	$moduleClass::UpdateParametrsValues();	
	$theme = $request->getPost("THEME");
	if($theme != "default") {
		$colorScheme = $request->getPost("COLOR_SCHEME");
		if($colorScheme == "CUSTOM")
			$moduleClass::GenerateColorScheme();
	}
}