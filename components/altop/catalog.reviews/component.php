<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader,
	Bitrix\Iblock;

if(!Loader::includeModule("iblock"))
	return;

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
$arParams["ELEMENT_ID"] = intval($arParams["ELEMENT_ID"]);
if($arParams["IBLOCK_ID"] <= 0 || $arParams["ELEMENT_ID"] <= 0)
	return;

$arParams["ELEMENT_AREA_ID"] = trim($arParams["ELEMENT_AREA_ID"]);
if(empty($arParams["ELEMENT_AREA_ID"]))
	return;

$arParams["COMMENT_URL"] = trim($arParams["COMMENT_URL"]);

global $USER, $APPLICATION;
$arSetting = CElektroinstrument::GetFrontParametrsValues(SITE_ID);

$arParams["IS_ADMIN"] = $USER->IsAdmin() ? "Y" : "N";
$arParams["PRE_MODERATION"] = $arParams["IS_ADMIN"] != "Y" && $arSetting["CATALOG_REVIEWS_PRE_MODERATION"] == "Y" ? "Y" : "N";

$arParams["IS_AUTHORIZED"] = $USER->IsAuthorized() ? "Y" : "N";
$arParams["USE_CAPTCHA"] = $arParams["IS_AUTHORIZED"] != "Y" && $arSetting["FORMS_USE_CAPTCHA"] == "Y" ? "Y" : "N";

$arParams["PROPERTIES"] = array("NAME", "MESSAGE");

$arParams["PARAMS_STRING"] = array(	
	"COMMENT_URL" => $arParams["COMMENT_URL"],
	"PRE_MODERATION" => $arParams["PRE_MODERATION"],
	"PROPERTIES" => $arParams["PROPERTIES"]
);
$arParams["PARAMS_STRING"] = strtr(base64_encode(serialize($arParams["PARAMS_STRING"])), "+/=", "-_,");

if($this->StartResultCache()) {
	//IBLOCK//
	$arIblock = CIBlock::GetList(array("SORT" => "ASC"), array("ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y"))->Fetch();
	
	if(empty($arIblock)) {
		$this->abortResultCache();
		return;
	}
	
	$arResult["IBLOCK"]["ID"] = $arIblock["ID"];
	
	//IBLOCK_PROPS//
	$rsProps = CIBlock::GetProperties($arIblock["ID"], array("SORT" => "ASC", "NAME" => "ASC"), array("ACTIVE" => "Y"));
	while($arProps = $rsProps->fetch()) {
		$arResult["IBLOCK"]["PROPERTIES"][] = $arProps;
	}
	
	if(!isset($arResult["IBLOCK"]["PROPERTIES"]) || empty($arResult["IBLOCK"]["PROPERTIES"])) {
		$this->abortResultCache();
		return;
	}
	
	$arResult["IBLOCK"]["STRING"] = strtr(base64_encode(serialize($arResult["IBLOCK"])), "+/=", "-_,");

	//ELEMENT//
	$arElement = CIBlockElement::GetList(
		array(),
		array(
			"ID" => $arParams["ELEMENT_ID"]
		),
		false,
		false,
		array("ID", "IBLOCK_ID", "NAME", "PREVIEW_PICTURE", "DETAIL_PICTURE")
	)->Fetch();

	if(empty($arElement)) {
		$this->abortResultCache();
		return;
	}
	
	$arResult["ELEMENT"]["ID"] = $arElement["ID"];
	$arResult["ELEMENT"]["NAME"] = $arElement["NAME"];

	$arResult["ELEMENT"]["STRING"] = strtr(base64_encode(serialize($arResult["ELEMENT"])), "+/=", "-_,");

	if($arElement["PREVIEW_PICTURE"] <= 0 && $arElement["DETAIL_PICTURE"] <= 0) {
		$mxResult = CCatalogSku::GetProductInfo($arElement["ID"]);
		if(is_array($mxResult)) {
			$arElement = Iblock\ElementTable::getList(array(
				"select" => array(
					"ID", "IBLOCK_ID", "PREVIEW_PICTURE", "DETAIL_PICTURE"
				),
				"filter" => array(
					"ID" => $mxResult["ID"]
				)
			))->Fetch();
		}
	}

	if($arElement["PREVIEW_PICTURE"] > 0) {
		$arFile = CFile::GetFileArray($arElement["PREVIEW_PICTURE"]);
		if($arFile["WIDTH"] > 178 || $arFile["HEIGHT"] > 178) {
			$arFileTmp = CFile::ResizeImageGet(
				$arFile,
				array("width" => 178, "height" => 178),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				true
			);		
			$arResult["ELEMENT"]["PREVIEW_PICTURE"] = array(
				"SRC" => $arFileTmp["src"],
				"WIDTH" => $arFileTmp["width"],
				"HEIGHT" => $arFileTmp["height"],
			);
		} else {
			$arResult["ELEMENT"]["PREVIEW_PICTURE"] = $arFile;
		}
	} elseif($arElement["DETAIL_PICTURE"] > 0) {
		$arFile = CFile::GetFileArray($arElement["DETAIL_PICTURE"]);
		if($arFile["WIDTH"] > 178 || $arFile["HEIGHT"] > 178) {
			$arFileTmp = CFile::ResizeImageGet(
				$arFile,
				array("width" => 178, "height" => 178),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				true
			);		
			$arResult["ELEMENT"]["PREVIEW_PICTURE"] = array(
				"SRC" => $arFileTmp["src"],
				"WIDTH" => $arFileTmp["width"],
				"HEIGHT" => $arFileTmp["height"],
			);
		} else {
			$arResult["ELEMENT"]["PREVIEW_PICTURE"] = $arFile;
		}
	}
	
	//USER//
	if($arParams["IS_AUTHORIZED"] == "Y") {
		$arResult["USER"]["NAME"] = $USER->GetFullName();
	}
	
	$this->IncludeComponentTemplate();
}?>