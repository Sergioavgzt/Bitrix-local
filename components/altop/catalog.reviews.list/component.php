<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;

if(!Loader::includeModule("iblock"))
	return;

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
$arParams["ELEMENT_ID"] = intval($arParams["ELEMENT_ID"]);
if($arParams["IBLOCK_ID"] <= 0 || $arParams["ELEMENT_ID"] <= 0)
	return;

$arParams["ELEMENT_AREA_ID"] = trim($arParams["ELEMENT_AREA_ID"]);

$arParams["COUNT_REVIEW"] = intval($arParams["COUNT_REVIEW"]);
if(empty($arParams["COUNT_REVIEW"]) || !isset($arParams["COUNT_REVIEW"]) || $arParams["COUNT_REVIEW"] <= 0) $arParams["COUNT_REVIEW"] = 5;

$arFilter = array(
	"ACTIVE" => "Y",
	"IBLOCK_ID" => $arParams["IBLOCK_ID"],
	"PROPERTY_OBJECT_ID" => $arParams["ELEMENT_ID"]
);

$arNavParams = array(
	"nPageSize" => $arParams["COUNT_REVIEW"],
	"bShowAll" => "N"
);
$arNavigation = CDBResult::GetNavParams($arNavParams);

if($this->StartResultCache(false, $arNavigation)) {
	//ITEMS//
	$rsElements = CIBlockElement::GetList(
		array(
			"SORT" => "ASC",
			"ACTIVE_FROM" => "DESC",
			"CREATED" => "DESC"
		),
		$arFilter,
		false,
		$arNavParams,
		array("ID", "IBLOCK_ID", "DATE_ACTIVE_FROM", "PREVIEW_TEXT", "DETAIL_TEXT", "DATE_CREATE", "CREATED_BY")
	);
	while($obElement = $rsElements->GetNextElement()) {
		$arElement = $obElement->GetFields();		
		
		$rsUser = $USER->GetByID($arElement["CREATED_BY"]);
		if($arUser = $rsUser->Fetch()) {
			if(!empty($arUser["PERSONAL_PHOTO"])) {
				$arFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
				if($arFile["WIDTH"] > 57 || $arFile["HEIGHT"] > 57) {
					$arFileTmp = CFile::ResizeImageGet(
						$arFile,
						array("width" => 57, "height" => 57),
						BX_RESIZE_IMAGE_PROPORTIONAL,
						true
					);
					$arElement["CREATED_USER_PERSONAL_PHOTO"] = array(
						"SRC" => $arFileTmp["src"],
						"WIDTH" => $arFileTmp["width"],
						"HEIGHT" => $arFileTmp["height"],
					);
				} else {
					$arElement["CREATED_USER_PERSONAL_PHOTO"] = $arFile;
				}
			}
		}

		$arElement["PROPERTIES"] = $obElement->GetProperties();
		
		$arResult["ITEMS"][] = $arElement;
	}

	//NAVIGATION//
	$arResult["NAV_STRING"] = $rsElements->GetPageNavStringEx($navComponentObject, "", "reviews");

	//ITEMS_COUNT//
	$arResult["ITEMS_COUNT"] = CIBlockElement::GetList(array(), $arFilter, array(), false, array("ID", "IBLOCK_ID"));
	
	$this->IncludeComponentTemplate();
}?>