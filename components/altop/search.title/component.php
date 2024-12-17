<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader,
	Bitrix\Catalog;

if(!IsModuleInstalled("search")) {
	ShowError(GetMessage("CC_BST_MODULE_NOT_INSTALLED"));
	return;
}

if(!Loader::includeModule("catalog") && !Loader::includeModule("search"))
	return;

Loader::IncludeModule("search");

$full_text_engine = COption::GetOptionString("search", "full_text_engine");
if($full_text_engine === "sphinx") {
	$arSphinx = new CSearchSphinx;
	$arSphinx->connect(
		COption::GetOptionString("search", "sphinx_connection"),
		COption::GetOptionString("search", "sphinx_index_name")
	);
}

if(!isset($arParams["PAGE"]) || strlen($arParams["PAGE"])<=0)
	$arParams["PAGE"] = "#SITE_DIR#search/index.php";

$arResult["CATEGORIES"] = array();

$query = ltrim($_POST["q"]);

if (!isset($arParams['HIDE_NOT_AVAILABLE']))
	$arParams['HIDE_NOT_AVAILABLE'] = 'N';
if ($arParams['HIDE_NOT_AVAILABLE'] != 'Y')
	$arParams['HIDE_NOT_AVAILABLE'] = 'N';

if (!isset($arParams['HIDE_NOT_AVAILABLE_OFFERS']))
	$arParams['HIDE_NOT_AVAILABLE_OFFERS'] = 'N';
if ($arParams['HIDE_NOT_AVAILABLE_OFFERS'] != 'Y' && $arParams['HIDE_NOT_AVAILABLE_OFFERS'] != 'L')
	$arParams['HIDE_NOT_AVAILABLE_OFFERS'] = 'N';

if (!isset($arParams['SEARCH_SECTION_ACTIVE']))
	$arParams['SEARCH_SECTION_ACTIVE'] = 'N';

if(!empty($query) && $_REQUEST["ajax_call"] === "y" && (!isset($_REQUEST["INPUT_ID"]) || $_REQUEST["INPUT_ID"] == $arParams["INPUT_ID"]) && CModule::IncludeModule("search")) {
	CUtil::decodeURIComponent($query);

	$arResult["alt_query"] = "";
	if($arParams["USE_LANGUAGE_GUESS"] !== "N") {
		$arLang = CSearchLanguage::GuessLanguage($query);
		if(is_array($arLang) && $arLang["from"] != $arLang["to"])
			$arResult["alt_query"] = CSearchLanguage::ConvertKeyboardLayout($query, $arLang["from"], $arLang["to"]);
	}

	$arResult["query"] = $query;
	$arResult["phrase"] = stemming_split($query, LANGUAGE_ID);

	$arParams["NUM_CATEGORIES"] = intval($arParams["NUM_CATEGORIES"]);
	if($arParams["NUM_CATEGORIES"] <= 0)
		$arParams["NUM_CATEGORIES"] = 1;

	$arParams["TOP_COUNT"] = intval($arParams["TOP_COUNT"]);
	if($arParams["TOP_COUNT"] <= 0)
		$arParams["TOP_COUNT"] = 5;

	if($arParams["ORDER"] == "date"):
		$aSort = array("DATE_CHANGE" => "DESC", "CUSTOM_RANK" => "DESC", "RANK" => "DESC");
	elseif($arParams["ORDER"] == "title"):
		$aSort = array("TITLE_RANK" => "DESC", "TITLE" => "DESC", "CUSTOM_RANK" => "DESC", "RANK" => "DESC", "DATE_CHANGE" => "DESC");
	else:
		$aSort = array("CUSTOM_RANK" => "DESC", "RANK" => "DESC", "DATE_CHANGE" => "DESC");
	endif;	

	$arOthersFilter = array("LOGIC" => "OR");

	for($i = 0; $i < $arParams["NUM_CATEGORIES"]; $i++) {
		$category_title = trim($arParams["CATEGORY_".$i."_TITLE"]);
		if(empty($category_title)) {
			if(is_array($arParams["CATEGORY_".$i]))
				$category_title = implode(", ", $arParams["CATEGORY_".$i]);
			else
				$category_title = trim($arParams["CATEGORY_".$i]);
		}
		if(empty($category_title))
			continue;

		$arResult["CATEGORIES"][$i] = array(
			"TITLE" => htmlspecialcharsbx($category_title),
			"ITEMS" => array()
		);

		$exFILTER = array(
			0 => CSearchParameters::ConvertParamsToFilter($arParams, "CATEGORY_".$i),
		);
		$exFILTER[0]["LOGIC"] = "OR";

		if($arParams["CHECK_DATES"] === "Y")
			$exFILTER["CHECK_DATES"] = "Y";

		$arOthersFilter[] = $exFILTER;
		
		if($full_text_engine === "sphinx") {
			$arResult["SPHINX"] = true;
			$str_query = $arResult["alt_query"] ? $arResult["alt_query"] : $arResult["query"];
			$arSearch = $arSphinx->search(array("SITE_ID" => SITE_ID, "QUERY" => $str_query), $aSort, array(), false);
			$j = 0;
			foreach($arSearch as $arItem) {
				$j++;
				if($j > $arParams["TOP_COUNT"]) {
					$params = array("q" => $arResult["alt_query"]? $arResult["alt_query"]: $arResult["query"]);

					$url = CHTTP::urlAddParams(
						str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
						,$params
						,array("encode"=>true)
					).CSearchTitle::MakeFilterUrl("f", $exFILTER);

					$arResult["CATEGORIES"][$i]["ITEMS"][] = array(
						"NAME" => GetMessage("CC_BST_MORE"),
						"URL" => htmlspecialcharsex($url),
					);
					break;
				} else {
					$arParamItem = CCatalogProduct::GetByID($arItem["item"]);
					$arFieldsAvailable = array(
						"QUANTITY" => $arParamItem["QUANTITY"],
						"QUANTITY_TRACE" => $arParamItem["QUANTITY_TRACE"],
						"CAN_BUY_ZERO" => $arParamItem["CAN_BUY_ZERO"]
					);
					$arAvailableItem = Catalog\ProductTable::calculateAvailable($arFieldsAvailable);
					
					if($arParams['SEARCH_SECTION_ACTIVE'] != "N") {
						$rsElementIn = CIBlockElement::GetList(
							array(),
							array(
								"ID" => $arItem["item"],
								"ACTIVE" => "Y",
								"IBLOCK_ID" => $arParams["IBLOCK_ID"],
								"SECTION_GLOBAL_ACTIVE" => "Y"
							),
							false, 
							false, 
							array("ID", "IBLOCK_ID")
						)->Fetch();
					}
					
					if(($arAvailableItem == 'N' && $arParams['HIDE_NOT_AVAILABLE'] == 'Y') || ($arParams['SEARCH_SECTION_ACTIVE'] != "N" && !$rsElementIn)) {
						$j--;
						continue;
					}
					
					if($arItem["module_id"] == "3674251022") {
						$arItem["module_id"] = "iblock";
					}
					
					$arElement;
					if(substr($arItem["item"], 0, 1) === "S") {
						$arElement = CIBlockSection::GetByID(substr($arItem["item"], 1))->GetNext();
						$arElement["DETAIL_PAGE_URL"] = $arElement["SECTION_PAGE_URL"];						
					}			
					if(substr($arItem["item"], 0, 1) !== "S") {
						$arElement = CIBlockElement::GetByID($arItem["item"])->GetNext();
					}		
					
					$arResult["CATEGORIES"][$i]["ITEMS"][] = array(
						"NAME" => $arElement["NAME"],
						"URL" => $arElement["DETAIL_PAGE_URL"],
						"MODULE_ID" => $arItem["module_id"],
						"PARAM1" => $arItem["param1"],
						"PARAM2" => $arItem["param2"],
						"ITEM_ID" => $arItem["item"],
					);
				}
			}
		} else {
			$j = 0;
			$obTitle = new CSearch;
			$str_query = $arResult["alt_query"] ? $arResult["alt_query"] : $arResult["query"];
			$obTitle->Search(array("SITE_ID" => SITE_ID, "QUERY" => $str_query), $aSort, $exFILTER);
			
			while($ar = $obTitle->Fetch()) {			
				$j++;
				if($j > $arParams["TOP_COUNT"]) {
					$params = array("q" => $arResult["alt_query"]? $arResult["alt_query"]: $arResult["query"]);

					$url = CHTTP::urlAddParams(
						str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
						,$params
						,array("encode"=>true)
					).CSearchTitle::MakeFilterUrl("f", $exFILTER);

					$arResult["CATEGORIES"][$i]["ITEMS"][] = array(
						"NAME" => GetMessage("CC_BST_MORE"),
						"URL" => htmlspecialcharsex($url),
					);
					break;
				} else {
					
					$arParamItem = CCatalogProduct::GetByID($ar["ITEM_ID"]);
					$arFieldsAvailable = array(
						"QUANTITY" => $arParamItem["QUANTITY"],
						"QUANTITY_TRACE" => $arParamItem["QUANTITY_TRACE"],
						"CAN_BUY_ZERO" => $arParamItem["CAN_BUY_ZERO"]
					);
					$arAvailableItem = Catalog\ProductTable::calculateAvailable($arFieldsAvailable);
					
					if($arParams['SEARCH_SECTION_ACTIVE'] != "N") {
						$rsElementIn = CIBlockElement::GetList(
							array(),
							array(
								"ID" => $ar["ITEM_ID"],
								"ACTIVE" => "Y",
								"IBLOCK_ID" => $arParams["IBLOCK_ID"],
								"SECTION_GLOBAL_ACTIVE" => "Y"
							),
							false, 
							false, 
							array("ID", "IBLOCK_ID")
						)->Fetch();
					}
					
					if(($arAvailableItem == 'N' && $arParams['HIDE_NOT_AVAILABLE'] == 'Y') || ($arParams['SEARCH_SECTION_ACTIVE'] != "N" && !$rsElementIn)) {
						$j--;
						continue;
					}
					
					$arResult["CATEGORIES"][$i]["ITEMS"][] = array(
						"NAME" => $ar["TITLE"],
						"URL" => htmlspecialcharsbx($ar["URL"]),
						"MODULE_ID" => $ar["MODULE_ID"],
						"PARAM1" => $ar["PARAM1"],
						"PARAM2" => $ar["PARAM2"],
						"ITEM_ID" => $ar["ITEM_ID"],
					);
				}
			}
		}	
		/* This code adds not fixed keyboard link to the category
		if($arResult["alt_query"] != "")
		{
			$params = array(
				"q" => $arResult["query"],
				"spell" => 1,
			);

			$url = CHTTP::urlAddParams(
				str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
				,$params
				,array("encode"=>true)
			).CSearchTitle::MakeFilterUrl("f", $exFILTER);

			$arResult["CATEGORIES"][$i]["ITEMS"][] = array(
				"NAME" => GetMessage("CC_BST_QUERY_PROMPT", array("#query#"=>$arResult["query"])),
				"URL" => htmlspecialcharsex($url),
			);
		}
		*/
		if(!$j) {
			unset($arResult["CATEGORIES"][$i]);
		}
	}

	if($arParams["SHOW_OTHERS"] === "Y") {
		$arResult["CATEGORIES"]["others"] = array(
			"TITLE" => htmlspecialcharsbx($arParams["CATEGORY_OTHERS_TITLE"]),
			"ITEMS" => array(),
		);

		$j = 0;
		if($full_text_engine === "sphinx") {
			$arResult["SPHINX"] = true;
			$str_other_query = $arResult["alt_query"] ? $arResult["alt_query"] : $arResult["query"];
			$arSearch = $arSphinx->search(array("SITE_ID" => SITE_ID, "QUERY" => $str_query), $aSort, array("LIMIT" => $arParams["TOP_COUNT"]), false);
			foreach($arSearch as $arItem) {
				$j++;
				if($j > $arParams["TOP_COUNT"]) {
					//it's really hard to make it working
					break;
				} else {
					if($arItem["module_id"] == "3674251022") {
						$arItem["module_id"] = "iblock";
					}
					
					$arElement;
					if(substr($arItem["item"], 0, 1) === "S") {
						$arElement = CIBlockSection::GetByID(substr($arItem["item"], 1))->GetNext();
						$arElement["DETAIL_PAGE_URL"] = $arElement["SECTION_PAGE_URL"];						
					}			
					if(substr($arItem["item"], 0, 1) !== "S") {
						$arElement = CIBlockElement::GetByID($arItem["item"])->GetNext();
					}		
						
					$arResult["CATEGORIES"]["others"]["ITEMS"][] = array(
						"NAME" => $arElement["NAME"],
						"URL" => htmlspecialcharsbx($arElement["DETAIL_PAGE_URL"]),
						"MODULE_ID" => $arItem["module_id"],
						"PARAM1" => $arItem["PARAM1"],
						"PARAM2" => $arItem["PARAM2"],
						"ITEM_ID" => $arItem["ITEM_ID"],
					);
				}
			}
		} else {
			$obTitle = new CSearch;
			$str_other_query = $arResult["alt_query"] ? $arResult["alt_query"] : $arResult["query"];
			$obTitle->Search(array("SITE_ID" => SITE_ID, "QUERY" => $str_other_query), $aSort, $arOthersFilter);
			
			while($ar = $obTitle->Fetch()) {			
				$j++;
				if($j > $arParams["TOP_COUNT"]) {
					//it's really hard to make it working
					break;
				} else {
					$arResult["CATEGORIES"]["others"]["ITEMS"][] = array(
						"NAME" => $ar["NAME"],
						"URL" => htmlspecialcharsbx($ar["URL"]),
						"MODULE_ID" => $ar["MODULE_ID"],
						"PARAM1" => $ar["PARAM1"],
						"PARAM2" => $ar["PARAM2"],
						"ITEM_ID" => $ar["ITEM_ID"],
					);
				}
			}
		}

		if(!$j) {
			unset($arResult["CATEGORIES"]["others"]);
		}
	}

	if(!empty($arResult["CATEGORIES"])) {
		$arResult["CATEGORIES"]["all"] = array(
			"TITLE" => "",
			"ITEMS" => array()
		);

		$params = array(
			"q" => $arResult["alt_query"]? $arResult["alt_query"]: $arResult["query"],
		);
		$url = CHTTP::urlAddParams(
			str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
			,$params
			,array("encode"=>true)
		);
		$arResult["CATEGORIES"]["all"]["ITEMS"][] = array(
			"NAME" => GetMessage("CC_BST_ALL_RESULTS"),
			"URL" => $url,
		);
		/*
		if($arResult["alt_query"] != "")
		{
			$params = array(
				"q" => $arResult["query"],
				"spell" => 1,
			);

			$url = CHTTP::urlAddParams(
				str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
				,$params
				,array("encode"=>true)
			);

			$arResult["CATEGORIES"]["all"]["ITEMS"][] = array(
				"NAME" => GetMessage("CC_BST_ALL_QUERY_PROMPT", array("#query#"=>$arResult["query"])),
				"URL" => htmlspecialcharsex($url),
			);
		}
		*/
	}
}

$arResult["FORM_ACTION"] = htmlspecialcharsbx(str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"]));

if($_REQUEST["ajax_call"] === "y" && (!isset($_REQUEST["INPUT_ID"]) || $_REQUEST["INPUT_ID"] == $arParams["INPUT_ID"])) {
	$APPLICATION->RestartBuffer();

	if(!empty($query))
		$this->IncludeComponentTemplate('ajax');
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
	die();
} else {
	$APPLICATION->AddHeadScript($this->GetPath().'/script.js');
	CUtil::InitJSCore(array('ajax'));
	$this->IncludeComponentTemplate();
}?>