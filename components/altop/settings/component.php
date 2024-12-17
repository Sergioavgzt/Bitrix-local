<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Application;

$moduleClass = "CElektroinstrument";
$moduleID = "altop.elektroinstrument";

if(!Loader::IncludeModule($moduleID))
	return;

$arParams["SITE_BACKGROUNDS"] = array("TREE", "YELLOW_POLYGONS", "TURQUOISE_POLYGONS", "PURPLE_POLYGONS", "POLYGONS", "CONCRETE", "BRICKS", "CLOTH", "TILE", "CHAIN_ARMOUR", "MATERIAL");

$arParams["MODULE_ID"] = $moduleID;

//SET_OPTION_SITE_BACKGROUND//
foreach($arParams["SITE_BACKGROUNDS"] as $arSiteBg) {
	if(!Option::get($moduleID, "SITE_BACKGROUND_".$arSiteBg)) {
		$arFile = CFile::MakeFileArray(dirname(__FILE__)."/images/".mb_strtolower($arSiteBg).".jpg");
		$arFile["MODULE_ID"] = $moduleID;
		$arSiteBgPic = CFile::SaveFile($arFile, $moduleID);
		if($arSiteBgPic > 0) {
			$arSiteBgPicIds[] = $arSiteBgPic;
			Option::set($moduleID, "SITE_BACKGROUND_".$arSiteBg, $arSiteBgPic);
		}
	}
}
if(!Option::get($moduleID, "SITE_BACKGROUND_PICTURE_IDS") && count($arSiteBgPicIds) > 0)
	Option::set($moduleID, "SITE_BACKGROUND_PICTURE_IDS", serialize($arSiteBgPicIds));

//RESULT//
$arResult = array();
$arFrontParametrs = $moduleClass::GetFrontParametrsValues(SITE_ID);
foreach($moduleClass::$arParametrsList as $blockCode => $arBlock) {
	foreach($arBlock["OPTIONS"] as $optionCode => $arOption) {				
		/***depricated
		//PERSONAL_DATA//
		if($optionCode == "SHOW_PERSONAL_DATA") {
			$arResult[$optionCode]["TITLE"] = $arBlock["TITLE"];
			$arResult[$optionCode]["IN_SETTINGS_PANEL"] = $arOption["IN_SETTINGS_PANEL"];
			$arResult[$optionCode]["TYPE"] = $arOption["TYPE"];
			$arResult[$optionCode]["CHEKBOX"][$optionCode] = array("TITLE" => $arOption["TITLE"],"CURRENT" => $arBackParametrs[$optionCode][0]);
		} else {
			$arResult[$optionCode] = $arOption;
			$arResult[$optionCode]["VALUE"] = $arFrontParametrs[$optionCode];
			//CURRENT for compatibility with old versions
			if($arResult[$optionCode]["LIST"]){
				foreach($arResult[$optionCode]["LIST"] as $variantCode => $variantTitle){
					if(!is_array($variantTitle)){
						$arResult[$optionCode]["LIST"][$variantCode] = array("TITLE" => $variantTitle);
					}
					if($arResult[$optionCode]["TYPE"] == "selectbox"){
						if($arResult[$optionCode]["VALUE"] == $variantCode){
							$arResult[$optionCode]["LIST"][$variantCode]["CURRENT"] = "Y";
						}
					} elseif($arResult[$optionCode]["TYPE"] == "multiselectbox"){
						if(in_array($variantCode, $arResult[$optionCode]["VALUE"])){
							$arResult[$optionCode]["LIST"][$variantCode]["CURRENT"] = "Y";
						}
					}
				}
			}
		}***/
		$arResult[$optionCode] = $arOption;
		$arResult[$optionCode]["VALUE"] = $arFrontParametrs[$optionCode];
		if($arResult[$optionCode]["LIST"]) {
			foreach($arResult[$optionCode]["LIST"] as $variantCode => $variantTitle){
				if(!is_array($variantTitle)) {
					$arResult[$optionCode]["LIST"][$variantCode] = array("TITLE" => $variantTitle);
				}
				if($arResult[$optionCode]["TYPE"] == "selectbox") {
					if($arResult[$optionCode]["VALUE"] == $variantCode){
						$arResult[$optionCode]["LIST"][$variantCode]["CURRENT"] = "Y";
					}
				} elseif($arResult[$optionCode]["TYPE"] == "multiselectbox") {
					if(in_array($variantCode, $arResult[$optionCode]["VALUE"])){
						$arResult[$optionCode]["LIST"][$variantCode]["CURRENT"] = "Y";
					}
				}
			}
		}
	}
}

//COLOR_SCHEME//
$colorScheme = $arResult["COLOR_SCHEME"]["VALUE"];

if($colorScheme != "CUSTOM")
	$themeColor = $arResult["COLOR_SCHEME"]["LIST"][$colorScheme]["COLOR"];
else
	$themeColor = $arResult["COLOR_SCHEME_CUSTOM"]["VALUE"];

$APPLICATION->AddHeadString("<meta name='theme-color' content='".$themeColor."' />");

if($colorScheme != "YELLOW") {
	$docRoot = Application::getDocumentRoot();
	$file = SITE_TEMPLATE_PATH."/schemes/".$colorScheme.($colorScheme == "CUSTOM" ? "_".SITE_ID : "")."/colors.min.css";
	if(!file_exists($docRoot.$file))
		$moduleClass::GenerateColorScheme();
	$APPLICATION->SetAdditionalCSS($file, true);
}
unset($file, $docRoot, $colorScheme);

//CUSTOM_CSS//
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH."/custom.css", true);

//SITE_BACKGROUND//
if($arResult["SITE_BACKGROUND"]["VALUE"] == "Y" && $arResult["SITE_BACKGROUND_PICTURE"]["VALUE"] > 0) {
	$arFile = CFile::GetFileArray($arResult["SITE_BACKGROUND_PICTURE"]["VALUE"]);
	if(is_array($arFile)) {
		$APPLICATION->SetPageProperty(
			"backgroundImage",
			" style=\"background-image: url('".CHTTP::urnEncode($arFile["SRC"], "UTF-8")."')\""
		);
	}
}

//FALLING_SNOW//
if(in_array("FALLING_SNOW", $arResult["GENERAL_SETTINGS"]["VALUE"])) {
	$moduleClass::StartFallingSnow(SITE_TEMPLATE_PATH);
}

//SETTINGS_PANEL//
global $USER;
if($USER->IsAdmin() && $arResult["SHOW_SETTINGS_PANEL"]["VALUE"] == "Y") {
	$this->IncludeComponentTemplate();
}

return $arResult;?>