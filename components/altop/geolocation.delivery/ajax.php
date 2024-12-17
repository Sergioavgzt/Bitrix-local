<?define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$APPLICATION->ShowAjaxHead();
$APPLICATION->AddHeadScript("/bitrix/js/main/dd.js");

use Bitrix\Main\Application;
	
$request = Application::getInstance()->getContext()->getRequest();

if($request->isPost() && $request->getPost("action") == "geolocationDelivery" && check_bitrix_sessid()) {
	$template = $request->getPost("template");

	$arParams = $request->getPost("arParams");
	if(SITE_CHARSET != "utf-8")
		$arParams = $APPLICATION->ConvertCharsetArray($arParams, "utf-8", SITE_CHARSET);

	$quantity = $request->getPost("quantity");

	$cartProducts = $request->getPost("cartProducts");
	
	$APPLICATION->IncludeComponent("altop:geolocation.delivery", ($template ? $template : ""),
		array(		
			"ELEMENT_ID" => $arParams["ELEMENT_ID"],
			"ELEMENT_COUNT" => ($quantity ? $quantity : $arParams["ELEMENT_COUNT"]),
			"CART_PRODUCTS" => ($cartProducts ? $cartProducts : $arParams["CART_PRODUCTS"]),
			"AJAX_CALL" => "Y",
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"CACHE_TIME" => $arParams["CACHE_TIME"]
		),
		false,
		array("HIDE_ICONS" => "Y")
	);
}?>