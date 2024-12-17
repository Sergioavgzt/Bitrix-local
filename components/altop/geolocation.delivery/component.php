<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader,
	Bitrix\Main\Application,
	Bitrix\Main\Text\Encoding,
	Bitrix\Sale,
	Bitrix\Main\Type\Collection;

if(!Loader::includeModule("catalog") || !Loader::includeModule("sale"))
	return;

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams["ELEMENT_ID"] = intval($arParams["ELEMENT_ID"]);
if($arParams["ELEMENT_ID"] <= 0)
	return;

$arParams["ELEMENT_COUNT"] = floatval($arParams["ELEMENT_COUNT"]);

if(empty($arParams["CART_PRODUCTS"]))
	$arParams["CART_PRODUCTS"] = "N";
elseif($arParams["CART_PRODUCTS"] != "Y")
	$arParams["CART_PRODUCTS"] = "N";

if(empty($arParams["AJAX_CALL"]))
	$arParams["AJAX_CALL"] = "N";
elseif($arParams["AJAX_CALL"] != "Y")
	$arParams["AJAX_CALL"] = "N";

$request = Application::getInstance()->getContext()->getRequest();
$arParams["GEOLOCATION_CITY"] = $request->getCookie("GEOLOCATION_CITY");
if(SITE_CHARSET != "utf-8")
	$arParams["GEOLOCATION_CITY"] = Encoding::convertEncoding($arParams["GEOLOCATION_CITY"], "utf-8", SITE_CHARSET);
$arParams["GEOLOCATION_LOCATION_ID"] = $request->getCookie("GEOLOCATION_LOCATION_ID");

global $USER;
$userId = intval($USER->GetID());

$basket = false;
if($arParams["CART_PRODUCTS"] == "Y")
	$basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite())->getOrderableItems();

if($this->StartResultCache(false, array($userId, $basket))) {
	if($arParams["AJAX_CALL"] == "Y" && $arParams["GEOLOCATION_LOCATION_ID"] > 0) {
		//CATALOG_MEASURE_RATIO//
		$rsRatio = CCatalogMeasureRatio::getList(
			array(),
			array("PRODUCT_ID" => $arParams["ELEMENT_ID"]),
			false,
			false,
			array("PRODUCT_ID", "RATIO")
		);
		if($arRatio = $rsRatio->Fetch()) {			
			$intRatio = intval($arRatio["RATIO"]);
			$dblRatio = doubleval($arRatio["RATIO"]);
			$mxRatio = ($dblRatio > $intRatio ? $dblRatio : $intRatio);
			if(CATALOG_VALUE_EPSILON > abs($mxRatio))
				$mxRatio = 1;
			elseif(0 > $mxRatio)
				$mxRatio = 1;
			if($arParams["ELEMENT_COUNT"] <= 0)
				$arParams["ELEMENT_COUNT"] = $mxRatio;
			$arResult["CATALOG_MEASURE_RATIO"] = $mxRatio;
		}
		unset($arRatio, $rsRatio);

		if($arParams["ELEMENT_COUNT"] <= 0)
			$arParams["ELEMENT_COUNT"] = 1;
		if(!isset($arResult["CATALOG_MEASURE_RATIO"]))
			$arResult["CATALOG_MEASURE_RATIO"] = 1;
		
		//BASKET//
		if($arParams["CART_PRODUCTS"] != "Y")
			$basket = Sale\Basket::create(Bitrix\Main\Context::getCurrent()->getSite());

		if($item = $basket->getExistsItem("catalog", $arParams["ELEMENT_ID"])) {
			$item->setField("QUANTITY", $item->getQuantity() + $arParams["ELEMENT_COUNT"]);
		} else {
			$item = $basket->createItem("catalog", $arParams["ELEMENT_ID"]);
			$item->setFields(array(
				"QUANTITY" => $arParams["ELEMENT_COUNT"],
				"CURRENCY" => Bitrix\Currency\CurrencyManager::getBaseCurrency(),
				"LID" => Bitrix\Main\Context::getCurrent()->getSite(),
				"PRODUCT_PROVIDER_CLASS" => "CCatalogProductProvider"
			));
		}
		unset($item);

		//ORDER//
		$order = Bitrix\Sale\Order::create(Bitrix\Main\Context::getCurrent()->getSite(), $userId > 0 ? $userId : 1);
		$order->setBasket($basket);

		//ORDER_SHIPMENT//
		$shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->createItem();
		$shipmentItemCollection = $shipment->getShipmentItemCollection();
		$shipment->setField("CURRENCY", $order->getCurrency());

		foreach($order->getBasket() as $item) {
			$shipmentItem = $shipmentItemCollection->createItem($item);
			$shipmentItem->setQuantity($item->getQuantity());
		}
		unset($item);

		//ORDER_PROPERTIES//
		$propertyCollection = $order->getPropertyCollection();
		
		$propLocation = $propertyCollection->getDeliveryLocation();
		if(!empty($propLocation))
			$propLocation->setValue(CSaleLocation::getLocationCODEbyID($arParams["GEOLOCATION_LOCATION_ID"]));

		//ORDER_DELIVERY//
		$arDeliveryServiceAll = Bitrix\Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment);
		if(!empty($arDeliveryServiceAll)) {
			foreach($arDeliveryServiceAll as $deliveryObj) {
				$shipment->setFields(array(
					"DELIVERY_ID" => $deliveryObj->getId(),
					"DELIVERY_NAME" => $deliveryObj->isProfile() ? $deliveryObj->getNameWithParent() : $deliveryObj->getName(),
					"CURRENCY" => $order->getCurrency()
				));
				$calcResult = $deliveryObj->calculate($shipment);
				if($calcResult->isSuccess()) {
					$arDelivery["ID"] = $deliveryObj->getId();
					$arDelivery["NAME"] = $deliveryObj->isProfile() ? $deliveryObj->getNameWithParent() : $deliveryObj->getName();
					$arDelivery["DESCRIPTION"] = $deliveryObj->getDescription();
					$arDelivery["LOGOTIP"] = CFile::GetFileArray($deliveryObj->getLogotip());
					$arDelivery["PRICE"] = $calcResult->getPrice();
					$arDelivery["PRICE_FORMATED"] = SaleFormatCurrency($calcResult->getPrice(), $order->getCurrency());
					$arDelivery["PERIOD_TEXT"] = $calcResult->getPeriodDescription();
					
					$arResult["DELIVERY"][$deliveryObj->getId()] = $arDelivery;
				}
			}
			unset($deliveryObj);
		}
		
		if(!empty($arResult["DELIVERY"]))
			Collection::sortByColumn($arResult["DELIVERY"], array("PRICE" => SORT_ASC));
		else
			$this->abortResultCache();
	}
	$this->IncludeComponentTemplate();
}?>