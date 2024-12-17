<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader,
	Bitrix\Main\Application,
	Bitrix\Main\Text\Encoding,
	Bitrix\Iblock,
	Bitrix\Main\Service\GeoIp;

if(!Loader::includeModule("iblock"))
	return;

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
if($arParams["IBLOCK_ID"] <= 0)
	return;

$arParams["SHOW_CONFIRM"] = trim($arParams["SHOW_CONFIRM"]);
if($arParams["SHOW_CONFIRM"] != "N")
	$arParams["SHOW_CONFIRM"] = "Y";

$arParams["SHOW_DEFAULT_LOCATIONS"] = trim($arParams["SHOW_DEFAULT_LOCATIONS"]);
if($arParams["SHOW_DEFAULT_LOCATIONS"] != "N")
	$arParams["SHOW_DEFAULT_LOCATIONS"] = "Y";

$arParams["SHOW_TEXT_BLOCK"] = trim($arParams["SHOW_TEXT_BLOCK"]);
if($arParams["SHOW_TEXT_BLOCK"] != "N")
	$arParams["SHOW_TEXT_BLOCK"] = "Y";

$arParams["SHOW_TEXT_BLOCK_TITLE"] = trim($arParams["SHOW_TEXT_BLOCK_TITLE"]);
if($arParams["SHOW_TEXT_BLOCK_TITLE"] != "N")
	$arParams["SHOW_TEXT_BLOCK_TITLE"] = "Y";

if(strlen($arParams["TEXT_BLOCK_TITLE"]) <= 0)
	$arParams["TEXT_BLOCK_TITLE"] = GetMessage("GEOLOCATION_TEXT_BLOCK_TITLE_DEFAULT");

if(!isset($arParams["COOKIE_TIME"]))
	$arParams["COOKIE_TIME"] = 36000000;

$arParams["OPTIONS"] = array("GEOLOCATION_CITY", "GEOLOCATION_LOCATION_ID", "GEOLOCATION_CONTACTS_ID");

$request = Application::getInstance()->getContext()->getRequest();
$arParams["GEOLOCATION_CITY"] = $request->getCookie("GEOLOCATION_CITY");
if(SITE_CHARSET != "utf-8")
	$arParams["GEOLOCATION_CITY"] = Encoding::convertEncoding($arParams["GEOLOCATION_CITY"], "utf-8", SITE_CHARSET);
$arParams["GEOLOCATION_CONTACTS_ID"] = intval($request->getCookie("GEOLOCATION_CONTACTS_ID"));

global $arSetting;
$arParams["USE_GEOLOCATION"] = $arSetting["USE_GEOLOCATION"]["VALUE"];
$arParams["GEOLOCATION_REGIONAL_CONTACTS"] = $arSetting["GEOLOCATION_REGIONAL_CONTACTS"]["VALUE"];

$arParams["PARAMS_STRING"] = strtr(base64_encode(addslashes(gzcompress(serialize($arParams),9))), '+/=', '-_,');

$arResult['is_bot'] = preg_match("~(Google|Yahoo|Rambler|Bot|Yandex|Spider|Snoopy|Crawler|Finder|Mail|curl)~i", $_SERVER['HTTP_USER_AGENT']);

if(empty($arParams["GEOLOCATION_CITY"]) && $arParams["MODE_OPERATION"] == "BITRIX" && !$arResult['is_bot'] && $arParams["USE_GEOLOCATION"] == "Y") {
	$ipUser = GeoIp\Manager::getRealIp();
	$geoResult = GeoIp\Manager::getDataResult($ipUser, "ru", array('countryName', 'regionName', 'cityName'));
	if($geoResult && $geoResult->isSuccess()) {
		$arResultGeoIP = $geoResult->getGeoData();
		$arResult['countryName'] = $arResultGeoIP->countryName;
		$arResult['regionName'] = $arResultGeoIP->regionName;
		$arResult['cityName'] = $arResultGeoIP->cityName;
	}
}

if($this->StartResultCache()) {
	//ELEMENT//
	$arFilter = array(
		"ACTIVE" => "Y",
		"IBLOCK_ID" => $arParams["IBLOCK_ID"]
	);
	if($arParams["USE_GEOLOCATION"] == "Y" && $arParams["GEOLOCATION_REGIONAL_CONTACTS"] == "Y" && $arParams["GEOLOCATION_CONTACTS_ID"] > 0)
		$arFilter["ID"] = $arParams["GEOLOCATION_CONTACTS_ID"];

	$arElement = CIBlockElement::GetList(
		array("SORT" => "ASC"),
		$arFilter,
		false,
		array("nTopCount" => 1),
		array("ID", "IBLOCK_ID", "PREVIEW_TEXT")
	)->Fetch();

	if(empty($arElement))
		$this->abortResultCache();
	
	$arResult["CONTACTS"] = $arElement["PREVIEW_TEXT"];	
	
	$this->IncludeComponentTemplate();
}?>