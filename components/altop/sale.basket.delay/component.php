<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arParams["PATH_TO_DELAY"] = Trim($arParams["PATH_TO_DELAY"]);
if(strlen($arParams["PATH_TO_DELAY"]) <= 0)
	$arParams["PATH_TO_DELAY"] = "/personal/cart/";

if(!CModule::IncludeModule("sale") || !CModule::IncludeModule("catalog")) {
	return;
}

$resBasketDelay = CSaleBasket::GetList(
	array(), 
	array(
		"FUSER_ID" => CSaleBasket::GetBasketUserID(),
		"LID" => SITE_ID,
		"ORDER_ID" => "NULL",
		"DELAY" => "Y",
		"CAN_BUY" => "Y"
	), 
	false, 
	false, 
	array(
		"ID", 
		"QUANTITY"
	)
);

while($ar = $resBasketDelay->Fetch()) {
	$arResult["QUANTITY"] += $ar["QUANTITY"];
}

$this->IncludeComponentTemplate();
?>