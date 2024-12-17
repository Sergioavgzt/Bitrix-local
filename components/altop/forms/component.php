<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
	Bitrix\Main\Application,
	Bitrix\Main\Text\Encoding,
	Bitrix\Iblock;

if(!Loader::includeModule("iblock") || !Loader::includeModule("catalog"))
	return;

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
if($arParams["IBLOCK_ID"] <= 0)
	return;

$arParams["ELEMENT_ID"] = intval($arParams["ELEMENT_ID"]);
$arParams["ELEMENT_AREA_ID"] = trim($arParams["ELEMENT_AREA_ID"]);
if($arParams["ELEMENT_ID"] > 0 && empty($arParams["ELEMENT_AREA_ID"]))
	return;

$arParams["ELEMENT_NAME"] = trim($arParams["ELEMENT_NAME"]);
if(SITE_CHARSET != "utf-8")
	$arParams["ELEMENT_NAME"] = Encoding::convertEncoding($arParams["ELEMENT_NAME"], "utf-8", SITE_CHARSET);

$arParams["ELEMENT_PRICE"] = trim($arParams["ELEMENT_PRICE"]);
if(SITE_CHARSET != "utf-8")
	$arParams["ELEMENT_PRICE"] = Encoding::convertEncoding($arParams["ELEMENT_PRICE"], "utf-8", SITE_CHARSET);

global $USER;
$arParams["IS_AUTHORIZED"] = $USER->IsAuthorized() ? "Y" : "N";
$arSetting = CElektroinstrument::GetFrontParametrsValues(SITE_ID);
$arParams["USE_CAPTCHA"] = $arParams["IS_AUTHORIZED"] != "Y" && $arSetting["FORMS_USE_CAPTCHA"] == "Y" ? "Y" : "N";

$request = Application::getInstance()->getContext()->getRequest();

$arParams["PHONE_MASK"] = $request->getCookie("GEOLOCATION_PHONE_MASK");
if(empty($arParams["PHONE_MASK"]))
	$arParams["PHONE_MASK"] = $arSetting["FORMS_PHONE_MASK"];

$arParams["VALIDATE_PHONE_MASK"] = $request->getCookie("GEOLOCATION_VALIDATE_PHONE_MASK");
if(empty($arParams["VALIDATE_PHONE_MASK"]))
	$arParams["VALIDATE_PHONE_MASK"] = $arSetting["FORMS_VALIDATE_PHONE_MASK"];

$arParams["SHOW_PERSONAL_DATA"] = $arSetting["SHOW_PERSONAL_DATA"];
$arParams["TEXT_PERSONAL_DATA"] = $arSetting["TEXT_PERSONAL_DATA"];

$arParams["PARAMS_STRING"] = array(
	"ELEMENT_NAME" => $arParams["ELEMENT_NAME"],
	"ELEMENT_PRICE" => $arParams["ELEMENT_PRICE"],
	"VALIDATE_PHONE_MASK" => $arParams["VALIDATE_PHONE_MASK"]
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
	$arResult["IBLOCK"]["CODE"] = $arIblock["CODE"];
	$arResult["IBLOCK"]["NAME"] = $arIblock["NAME"];

	//ELEMENT_AREA_ID//
	$arResult["ELEMENT_AREA_ID"] = !empty($arParams["ELEMENT_AREA_ID"]) ? $arParams["ELEMENT_AREA_ID"] : $arResult["IBLOCK"]["CODE"];
	
	//IBLOCK_PROPS//
    /**
     *фильтр с логикой OR не работает, обошел проблему отфильтровав свойства во while
     */
    $rsProps = CIBlock::GetProperties(
        $arIblock["ID"],
        ["SORT" => "ASC", "NAME" => "ASC"],
        ["ACTIVE" => "Y"]);

	while($arProps = $rsProps->fetch()) {
        if ('S' == $arProps['PROPERTY_TYPE'] || 'F' == $arProps['PROPERTY_TYPE']) {
            $arResult["IBLOCK"]["PROPERTIES"][] = $arProps;
        }
	}
	
	if(!isset($arResult["IBLOCK"]["PROPERTIES"]) || empty($arResult["IBLOCK"]["PROPERTIES"])) {
		$this->abortResultCache();
		return;
	}
	
	$arResult["IBLOCK"]["STRING"] = strtr(base64_encode(serialize($arResult["IBLOCK"])), "+/=", "-_,");
	
	//ELEMENT//
	if($arParams["ELEMENT_ID"] > 0) {
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
	}

	//USER//
	if($arParams["IS_AUTHORIZED"] == "Y") {
		$arResult["USER"]["NAME"] = $USER->GetFullName();
		$arResult["USER"]["EMAIL"] = $USER->GetEmail();
	}
	
	$this->setResultCacheKeys(array(
		"ELEMENT_AREA_ID"
	));

	$this->IncludeComponentTemplate();
}?>