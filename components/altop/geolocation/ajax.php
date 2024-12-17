<?define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $APPLICATION;

$APPLICATION->ShowAjaxHead();
$APPLICATION->AddHeadScript("/bitrix/js/main/dd.js");

use Bitrix\Main\Loader,
	Bitrix\Main\Application,
	Bitrix\Main\Web\Cookie,
	Bitrix\Main\Text\Encoding;

if(!Loader::IncludeModule("iblock") || !Loader::IncludeModule("sale"))
	return;

$context = Application::getInstance()->getContext();
$request = $context->getRequest();

if($request->isPost() && check_bitrix_sessid()) {
	$action = $request->getPost("action");

	$arParams = $request->getPost("arParams");
	if(!empty($arParams))
		$arParams = unserialize(gzuncompress(stripslashes(base64_decode(strtr($arParams, '-_,', '+/=')))));

	//DELETE_GEOLOCATION_COOKIES//
	$flush = false;
	foreach($arParams["OPTIONS"] as $arOption) {
		if($request->getCookie($arOption)) {
			if(!$flush)
				$flush = true;
			$cookie = new Cookie($arOption, null, time() - 3600);
			$cookie->setDomain(SITE_SERVER_NAME);
			$cookie->setHttpOnly(false);
			$context->getResponse()->addCookie($cookie);			
			unset($cookie);
		}		
	}
	if($flush)
		$context->getResponse()->flush("");
	
	switch($action) {
		case "searchLocation":
			//GEOLOCATION_COUNTRY//
			$country = $request->getPost("country");
			if(SITE_CHARSET != "utf-8")
				$country = Encoding::convertEncoding($country, "utf-8", SITE_CHARSET);
			
			//GEOLOCATION_REGION//
			$region = $request->getPost("region");
			if(SITE_CHARSET != "utf-8")
				$region = Encoding::convertEncoding($region, "utf-8", SITE_CHARSET);
			
			//GEOLOCATION_CITY//
			$city = $request->getPost("city");
			if(SITE_CHARSET != "utf-8")
				$city = Encoding::convertEncoding($city, "utf-8", SITE_CHARSET);
			if(empty($city))
				return;
			
			//GEOLOCATION_LOCATION_ID//
			$locationId = false;
			$rsLocation = Bitrix\Sale\Location\LocationTable::getList(array(
				"filter" => array(
					"=NAME.NAME" => $city,
					"=NAME.LANGUAGE_ID" => LANGUAGE_ID
				),
				"select" => array("ID", "NAME_RU" => "NAME.NAME")
			));
			if($arLocation = $rsLocation->fetch())
				$locationId = $arLocation["ID"];
			unset($arLocation, $rsLocation);
			
			//GEOLOCATION_CONTACTS_ID//
			//GEOLOCATION_CONTACTS//
			//PHONE_MASK//
			//VALIDATE_PHONE_MASK//
			$contactsList = false;
			$rsElements = CIBlockElement::GetList(
				array(
					"SORT" => "ASC"
				), 
				array(
					"ACTIVE" => "Y",
					"IBLOCK_ID" => intval($arParams["IBLOCK_ID"])
				), 
				false, 
				false, 
				array("ID", "IBLOCK_ID", "PREVIEW_TEXT")
			);				
			while($obElement = $rsElements->GetNextElement()) {
				$arElement = $obElement->GetFields();
				$arElement["PROPERTIES"] = $obElement->GetProperties();
				$contactsList[] = $arElement;
			}
			unset($arElement, $obElement, $rsElements);

			//GEOLOCATION_CONTACTS_ID//
			//GEOLOCATION_CONTACTS//
			$contactsId = $contacts = false;
			foreach($contactsList as $arElement) {
				if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($city, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
					$contactsId = $arElement["ID"];
					$contacts = $arElement["PREVIEW_TEXT"];					
					break;
				}
			}
			if(empty($contactsId) && !empty($region)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($region, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$contactsId = $arElement["ID"];
						$contacts = $arElement["PREVIEW_TEXT"];						
						break;
					}
				}
			}
			if(empty($contactsId) && !empty($country)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($country, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$contactsId = $arElement["ID"];
						$contacts = $arElement["PREVIEW_TEXT"];						
						break;
					}
				}
			}

			//PHONE_MASK//
			//VALIDATE_PHONE_MASK//
			$phoneMask = $validatePhoneMask = false;			
			foreach($contactsList as $arElement) {
				if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($city, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
					$phoneMask = $arElement["PROPERTIES"]["PHONE_MASK"]["VALUE"];
					$validatePhoneMask = $arElement["PROPERTIES"]["VALIDATE_PHONE_MASK"]["VALUE"];
					break;
				}
			}			
			if(empty($phoneMask) && empty($validatePhoneMask) && !empty($region)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($region, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$phoneMask = $arElement["PROPERTIES"]["PHONE_MASK"]["VALUE"];
						$validatePhoneMask = $arElement["PROPERTIES"]["VALIDATE_PHONE_MASK"]["VALUE"];
						break;
					}
				}
			}
			if(empty($phoneMask) && empty($validatePhoneMask) && !empty($country)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($country, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$phoneMask = $arElement["PROPERTIES"]["PHONE_MASK"]["VALUE"];
						$validatePhoneMask = $arElement["PROPERTIES"]["VALIDATE_PHONE_MASK"]["VALUE"];
						break;
					}
				}
			}
			
			//SEARCH_RESULT//
			$searchResult = array(
				"city" => $city,
				"contacts" => $arParams["GEOLOCATION_REGIONAL_CONTACTS"] == "Y" ? (!empty($contacts) ? $contacts : false) : false
			);
			if(SITE_CHARSET != "utf-8")
				$searchResult = Encoding::convertEncoding($searchResult, SITE_CHARSET, "utf-8");

			//SET_GEOLOCATION_COOKIES//			
			$cookie = new Cookie("GEOLOCATION_CITY", $searchResult["city"], time() + $arParams["COOKIE_TIME"]);
			$cookie->setDomain(SITE_SERVER_NAME);
			$cookie->setHttpOnly(false);
			$context->getResponse()->addCookie($cookie);			
			unset($cookie);			
			if(!empty($locationId)) {
				$cookie = new Cookie("GEOLOCATION_LOCATION_ID", $locationId, time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);				
				unset($cookie);
			}			
			if(!empty($contactsId)) {
				$cookie = new Cookie("GEOLOCATION_CONTACTS_ID", $contactsId, time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);				
				unset($cookie);
			}			
			if(!empty($phoneMask)) {
				$cookie = new Cookie("GEOLOCATION_PHONE_MASK", $phoneMask, time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);				
				unset($cookie);
			}			
			if(!empty($validatePhoneMask)) {
				$cookie = new Cookie("GEOLOCATION_VALIDATE_PHONE_MASK", $validatePhoneMask, time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);				
				unset($cookie);
			}
			$context->getResponse()->flush("");
			
			echo json_encode($searchResult);
			break;		
		case "setLocation":
			//GEOLOCATION_LOCATION_ID//
			$locationId = $request->getPost("locationId");
			if(intval($locationId) <= 0)
				return;
			
			//GEOLOCATION_COUNTRY_REGION_SUBREGION_CITY_VILLAGE//
			$country = $region = $subregion = $city = $village = false;
			$rsLocations = Bitrix\Sale\Location\LocationTable::getList(array(
				"filter" => array(
					"=ID" => $locationId, 
					"=PARENTS.NAME.LANGUAGE_ID" => LANGUAGE_ID,
					"=PARENTS.TYPE.NAME.LANGUAGE_ID" => LANGUAGE_ID,
				),
				"select" => array(
					"I_ID" => "PARENTS.ID",
					"I_NAME_RU" => "PARENTS.NAME.NAME",
					"I_TYPE_CODE" => "PARENTS.TYPE.CODE",
					"I_TYPE_NAME_RU" => "PARENTS.TYPE.NAME.NAME"
				),
				"order" => array(
					"PARENTS.DEPTH_LEVEL" => "asc"
				)
			));
			while($arLocation = $rsLocations->fetch()) {
				if($arLocation["I_TYPE_CODE"] == "COUNTRY")
					$country = $arLocation["I_NAME_RU"];
				elseif($arLocation["I_TYPE_CODE"] == "REGION")
					$region = $arLocation["I_NAME_RU"];
				elseif($arLocation["I_TYPE_CODE"] == "SUBREGION")
					$subregion = $arLocation["I_NAME_RU"];
				elseif($arLocation["I_TYPE_CODE"] == "CITY")
					$city = $arLocation["I_NAME_RU"];
				elseif($arLocation["I_TYPE_CODE"] == "VILLAGE")
					$village = $arLocation["I_NAME_RU"];
			}
			unset($arLocation, $rsLocations);
			
			//GEOLOCATION_CONTACTS_ID//
			//PHONE_MASK//
			//VALIDATE_PHONE_MASK//
			$contactsList = false;
			$rsElements = CIBlockElement::GetList(
				array(
					"SORT" => "ASC"
				), 
				array(
					"ACTIVE" => "Y",
					"IBLOCK_ID" => intval($arParams["IBLOCK_ID"])
				), 
				false, 
				false, 
				array("ID", "IBLOCK_ID", "PREVIEW_TEXT")
			);				
			while($obElement = $rsElements->GetNextElement()) {
				$arElement = $obElement->GetFields();
				$arElement["PROPERTIES"] = $obElement->GetProperties();					
				$contactsList[] = $arElement;
			}
			unset($arElement, $obElement, $rsElements);

			//GEOLOCATION_CONTACTS_ID//
			$contactsId = false;
			if(!empty($village)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($village, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$contactsId = $arElement["ID"];						
						break;
					}
				}
			}
			if(empty($contactsId) && !empty($city)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($city, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$contactsId = $arElement["ID"];						
						break;
					}
				}
			}
			if(empty($contactsId) && !empty($subregion)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($subregion, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$contactsId = $arElement["ID"];						
						break;
					}
				}
			}
			if(empty($contactsId) && !empty($region)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($region, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$contactsId = $arElement["ID"];						
						break;
					}
				}
			}
			if(empty($contactsId) && !empty($country)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($country, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$contactsId = $arElement["ID"];						
						break;
					}
				}
			}

			//PHONE_MASK//
			//VALIDATE_PHONE_MASK//
			$phoneMask = $validatePhoneMask = false;
			if(!empty($village)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($village, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$phoneMask = $arElement["PROPERTIES"]["PHONE_MASK"]["VALUE"];
						$validatePhoneMask = $arElement["PROPERTIES"]["VALIDATE_PHONE_MASK"]["VALUE"];
						break;
					}
				}
			}
			if(empty($phoneMask) && empty($validatePhoneMask) && !empty($city)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($city, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$phoneMask = $arElement["PROPERTIES"]["PHONE_MASK"]["VALUE"];
						$validatePhoneMask = $arElement["PROPERTIES"]["VALIDATE_PHONE_MASK"]["VALUE"];
						break;
					}
				}
			}
			if(empty($phoneMask) && empty($validatePhoneMask) && !empty($subregion)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($subregion, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$phoneMask = $arElement["PROPERTIES"]["PHONE_MASK"]["VALUE"];
						$validatePhoneMask = $arElement["PROPERTIES"]["VALIDATE_PHONE_MASK"]["VALUE"];
						break;
					}
				}
			}
			if(empty($phoneMask) && empty($validatePhoneMask) && !empty($region)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($region, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$phoneMask = $arElement["PROPERTIES"]["PHONE_MASK"]["VALUE"];
						$validatePhoneMask = $arElement["PROPERTIES"]["VALIDATE_PHONE_MASK"]["VALUE"];
						break;
					}
				}
			}
			if(empty($phoneMask) && empty($validatePhoneMask) && !empty($country)) {
				foreach($contactsList as $arElement) {
					if(!empty($arElement["PROPERTIES"]["LOCATION"]["VALUE"]) && in_array($country, $arElement["PROPERTIES"]["LOCATION"]["VALUE"])) {
						$phoneMask = $arElement["PROPERTIES"]["PHONE_MASK"]["VALUE"];
						$validatePhoneMask = $arElement["PROPERTIES"]["VALIDATE_PHONE_MASK"]["VALUE"];
						break;
					}
				}
			}
			
			//SET_RESULT//
			$setResult = array(
				"village" => $village,
				"city" => $city,
				"subregion" => $subregion,
				"region" => $region,
				"country" => $country
			);
			if(SITE_CHARSET != "utf-8")
				$setResult = Encoding::convertEncoding($setResult, SITE_CHARSET, "utf-8");

			//SET_GEOLOCATION_COOKIES//
			if(!empty($setResult["village"])) {
				$cookie = new Cookie("GEOLOCATION_CITY", $setResult["village"], time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);			
				unset($cookie);
			} elseif(!empty($setResult["city"])) {
				$cookie = new Cookie("GEOLOCATION_CITY", $setResult["city"], time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);			
				unset($cookie);
			} elseif(!empty($setResult["subregion"])) {
				$cookie = new Cookie("GEOLOCATION_CITY", $setResult["subregion"], time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);			
				unset($cookie);
			} elseif(!empty($setResult["region"])) {
				$cookie = new Cookie("GEOLOCATION_CITY", $setResult["region"], time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);			
				unset($cookie);
			} elseif(!empty($setResult["country"])) {
				$cookie = new Cookie("GEOLOCATION_CITY", $setResult["country"], time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);			
				unset($cookie);
			}
			$cookie = new Cookie("GEOLOCATION_LOCATION_ID", $locationId, time() + $arParams["COOKIE_TIME"]);
			$cookie->setDomain(SITE_SERVER_NAME);
			$cookie->setHttpOnly(false);
			$context->getResponse()->addCookie($cookie);			
			unset($cookie);
			if(!empty($contactsId)) {
				$cookie = new Cookie("GEOLOCATION_CONTACTS_ID", $contactsId, time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);			
				unset($cookie);
			}
			if(!empty($phoneMask)) {
				$cookie = new Cookie("GEOLOCATION_PHONE_MASK", $phoneMask, time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);			
				unset($cookie);
			}
			if(!empty($validatePhoneMask)) {
				$cookie = new Cookie("GEOLOCATION_VALIDATE_PHONE_MASK", $validatePhoneMask, time() + $arParams["COOKIE_TIME"]);
				$cookie->setDomain(SITE_SERVER_NAME);
				$cookie->setHttpOnly(false);
				$context->getResponse()->addCookie($cookie);			
				unset($cookie);
			}
			$context->getResponse()->flush("");
			break;
	}
	die();
}?>