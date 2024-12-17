<? if (empty($_SERVER["HTTP_REFERER"]))
    die();

define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\Application,
    Bitrix\Main\Config\Option,
    Bitrix\Sale,
    Bitrix\Sale\Order,
    Bitrix\Sale\DiscountCouponsManager;

if (!Loader::IncludeModule("sale"))
    return;

if (CModule::IncludeModule("altop.elektroinstrument"))
    $arSetting = CElektroinstrument::GetBackParametrsValues(SITE_ID);
else
    return;

if (!CModule::IncludeModule("catalog"))
    return;

Loc::loadMessages(__FILE__);

global $USER, $APPLICATION;

$request = Application::getInstance()->getContext()->getRequest();

$paramsString = $request->getPost("PARAMS_STRING");
if (!empty($paramsString))
    $params = unserialize(base64_decode(strtr($paramsString, "-_,", "+/=")));

$name = $request->getPost("NAME");
$phone = $request->getPost("PHONE");
$email = $request->getPost("EMAIL");
$message = $request->getPost("MESSAGE");
$file = $request->getPost("FILE");

$basket_btn = $request->getPost("BASKET_BTN");

$captchaWord = $request->getPost("CAPTCHA_WORD");
$captchaSid = $request->getPost("CAPTCHA_SID");

$id = $request->getPost("ID");
$props = $request->getPost("PROPS");
$selectProps = $request->getPost("SELECT_PROPS");
$qnt = $request->getPost("QUANTITY");

$buyMode = $request->getPost("BUY_MODE");

if (!empty($basket_btn) && $basket_btn == "Y") {

    $arBasketItems = array();
    $summ = 0;
    $dbBasketItems = CSaleBasket::GetList(
        array(
            "NAME" => "ASC",
            "ID" => "ASC"
        ),
        array(
            "FUSER_ID" => CSaleBasket::GetBasketUserID(),
            "LID" => SITE_ID,
            "ORDER_ID" => "NULL"
        ),
        false,
        false,
        array("ID", "QUANTITY", "PRICE")
    );
    while ($arItems = $dbBasketItems->Fetch()) {
        if (strlen($arItems["CALLBACK_FUNC"]) > 0) {
            CSaleBasket::UpdatePrice($arItems["ID"], $arItems["QUANTITY"]);
            $arItems = CSaleBasket::GetByID($arItems["ID"]);
        }

        $arBasketItems[] = $arItems;
    }

    foreach ($arBasketItems as $item)
        $summ = $summ + $item["QUANTITY"] * $item["PRICE"];
}else{

    $arr_price = CPrice::GetBasePrice($id);

    if (!empty($arr_price)) {
        $arCurrencyInfo = CCurrency::GetByID($arr_price["CURRENCY"]);
        $summ = (int)$arr_price["PRICE"] * (int)$arCurrencyInfo["AMOUNT"];
    }
}


//MIN_PRICE
if ($arSetting["ORDER_MIN_PRICE"] > $summ) {
    $error .= Loc::getMessage("BOC_MIN_PRICE_VALUE") . CurrencyFormat($arSetting["ORDER_MIN_PRICE"], Bitrix\Currency\CurrencyManager::getBaseCurrency()) . "<br />";
}

//CHECKS//
foreach ($params["REQUIRED"] as $arCode) {
    $post = $request->getPost($arCode);
    if (empty($post))
        $error .= Loc::getMessage($arCode . "_NOT_FILLED") . "<br />";
}

//CHECKS_PERSONAL_DATA//
$personalData = $request->getPost("PERSONAL_DATA");
if ($personalData === "N") {
    $error .= Loc::getMessage("FIELD_NOT_FILLED_PERSONAL_DATA") . "<br />";
}

//VALIDATE_PHONE_MASK//
if (!empty($phone)) {
    if (!preg_match($params["VALIDATE_PHONE_MASK"], $phone)) {
        $error .= Loc::getMessage("PHONE_INVALID") . "<br />";
    }
}

if (!empty($captchaSid) && !$APPLICATION->CaptchaCheckCode($captchaWord, $captchaSid))
    $error .= Loc::getMessage("WRONG_CAPTCHA") . "<br />";

if (!empty($error)) {
    $result = array(
        "error" => array(
            "text" => $error,
            "captcha_code" => !empty($captchaSid) ? $APPLICATION->CaptchaGetCode() : ""
        )
    );
    echo Bitrix\Main\Web\Json::encode($result);
    return;
}

//PROPERTIES//
if (!empty($name))
    $name = iconv("UTF-8", SITE_CHARSET, strip_tags(trim($name)));
if (!empty($phone))
    $phone = iconv("UTF-8", SITE_CHARSET, strip_tags(trim($phone)));
if (!empty($email))
    $email = iconv("UTF-8", SITE_CHARSET, strip_tags(trim($email)));
if (!empty($message))
    $message = iconv("UTF-8", SITE_CHARSET, strip_tags(trim($message)));

$locCode = false;
$locId = $request->getCookie("GEOLOCATION_LOCATION_ID");
if ($locId > 0)
    $locCode = CSaleLocation::getLocationCODEbyID($locId);

$fileIds = array();
if (!empty($file) && Loader::IncludeModule("iblock")) {
    foreach ($file as $arFile) {
        $arFile["name"] = iconv("UTF-8", SITE_CHARSET, $arFile["name"]);
        $fileIds[] = CFile::SaveFile(CIBlock::makeFileArray($arFile), "sale/order/properties");
    }
    unset($arFile);
}

//USER//	
if ($params["IS_AUTHORIZED"] != "Y") {
    $rsUser = $USER->GetByLogin("technical_boc");
    if ($arUser = $rsUser->Fetch()) {
        $registeredUserID = $arUser["ID"];
    } else {
        $newLogin = "technical_boc";
        $newEmail = $newLogin . "@" . $newLogin . ".com";
        $newPass = randString(10);

        $arFields = Array(
            "LOGIN" => $newLogin,
            "NAME" => Loc::getMessage("NEW_USER_NAME"),
            "EMAIL" => $newEmail,
            "PASSWORD" => $newPass,
            "CONFIRM_PASSWORD" => $newPass,
            "ACTIVE" => "Y",
            "LID" => SITE_ID
        );
        $registeredUserID = $USER->Add($arFields);
    }
} else {
    $registeredUserID = $USER->GetID();
}

//BASKET//
$basketUserID = Sale\Fuser::getId();

DiscountCouponsManager::init();

function getInfoElement($prod_id)
{
    $elementInfo = CIBlockElement::GetByID($prod_id)->fetch();
    $url = CAllIBlock::ReplaceDetailUrl($elementInfo['DETAIL_PAGE_URL'], $elementInfo, false, 'E');
    $info = array(
        'CATALOG_XML_ID' => $elementInfo['IBLOCK_EXTERNAL_ID'],
        'EXTERNAL_ID' => $elementInfo['EXTERNAL_ID'],
    );

    return $info;
}

if ($buyMode == "ONE") {
    $basket = Sale\Basket::loadItemsForFUser($basketUserID, Bitrix\Main\Context::getCurrent()->getSite())->getOrderableItems();
    foreach ($basket as $basketItem) {
        \CSaleBasket::Delete($basketItem->getId());
    }
    $item = $basket->createItem("catalog", $id);
    $elem = getInfoElement($id);

    $item->setFields(array(
        "QUANTITY" => $qnt,
        "CURRENCY" => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
        "LID" => \Bitrix\Main\Context::getCurrent()->getSite(),
        "PRODUCT_PROVIDER_CLASS" => "CCatalogProductProvider",
        'CATALOG_XML_ID' => $elem['CATALOG_XML_ID'],
        'PRODUCT_XML_ID' => $elem['EXTERNAL_ID'],
    ));
    $basket->save();

    if (!empty($props)) {
        $arProps = unserialize(base64_decode(strtr($props, "-_,", "+/=")));
        foreach ($arProps as $arProp) {
            $arBasketProps[] = $arProp;
        }
    }
    if (!empty($selectProps)) {
        $arSelectProps = explode("||", $selectProps);
        foreach ($arSelectProps as $arSelProp) {
            $arBasketProps[] = unserialize(base64_decode(strtr($arSelProp, "-_,", "+/=")));
        }
    }
    if (isset($arBasketProps) && !empty($arBasketProps)) {
        $basketPropertyCollection = $item->getPropertyCollection();
        $basketPropertyCollection->setProperty($arBasketProps);
        $basketPropertyCollection->save();
    }
}

//PERSON_TYPE//
$arPersonTypes = Sale\PersonType::load(Bitrix\Main\Context::getCurrent()->getSite());
reset($arPersonTypes);
$arPersonType = current($arPersonTypes);
if (!empty($arPersonType))
    $personType = $arPersonType["ID"];
else
    $personType = 1;

//CREATE_ORDER_PROP_FILE//
if (!empty($fileIds)) {
    $arFileProp = CSaleOrderProps::GetList(
        array("SORT" => "ASC"),
        array(
            "PERSON_TYPE_ID" => $personType,
            "CODE" => "BOC_FILE"
        ),
        false,
        false,
        array("ID")
    )->Fetch();

    if (empty($arFileProp)) {
        $propsGroupId = false;
        $rsPropsGroup = CSaleOrderPropsGroup::GetList(
            array("SORT" => "ASC"),
            array("PERSON_TYPE_ID" => $personType),
            false,
            array("nTopCount" => 1),
            array("ID")
        );
        if ($arPropGroup = $rsPropsGroup->Fetch())
            $propsGroupId = $arPropGroup["ID"];

        $arFields = array(
            "PERSON_TYPE_ID" => $personType,
            "NAME" => $params["FILE_FIELD_NAME"],
            "TYPE" => "FILE",
            "REQUIED" => "N",
            "SORT" => "500",
            "USER_PROPS" => "Y",
            "IS_LOCATION" => "N",
            "PROPS_GROUP_ID" => intval($propsGroupId) > 0 ? $propsGroupId : 1,
            "DESCRIPTION" => Loc::getMessage("NEW_USER_NAME"),
            "IS_EMAIL" => "N",
            "IS_PROFILE_NAME" => "N",
            "IS_PAYER" => "N",
            "IS_LOCATION4TAX" => "N",
            "IS_ZIP" => "N",
            "CODE" => "BOC_FILE",
            "IS_FILTERED" => "Y",
            "ACTIVE" => "Y",
            "UTIL" => "Y",
            "INPUT_FIELD_LOCATION" => "0",
            "MULTIPLE" => "Y"
        );
        CSaleOrderProps::Add($arFields);
    }
}

//CREATE_ORDER//
$order = Order::create(Bitrix\Main\Context::getCurrent()->getSite(), $registeredUserID);

//ORDER_SET_PERSON_TYPE//
$order->setPersonTypeId($personType);

//ORDER_SET_BASKET//
$basket = Sale\Basket::loadItemsForFUser($basketUserID, Bitrix\Main\Context::getCurrent()->getSite())->getOrderableItems();
$order->setBasket($basket);

//ORDER_SET_SHIPMENT//
$shipmentCollection = $order->getShipmentCollection();
$shipment = $shipmentCollection->createItem();
$shipment->setField("CURRENCY", $order->getCurrency());

$shipmentItemCollection = $shipment->getShipmentItemCollection();

foreach ($order->getBasket() as $item) {
    $shipmentItem = $shipmentItemCollection->createItem($item);
    $shipmentItem->setQuantity($item->getQuantity());
}

$arDeliveryServiceAll = Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment);
reset($arDeliveryServiceAll);
$deliveryObj = current($arDeliveryServiceAll);
if (!empty($deliveryObj)) {
    $shipment->setFields(array(
        "DELIVERY_ID" => $deliveryObj->getId(),
        "DELIVERY_NAME" => $deliveryObj->isProfile() ? $deliveryObj->getNameWithParent() : $deliveryObj->getName()
    ));
    $shipment->getCollection()->calculateDelivery();
} else
    $shipment->delete();


//ORDER_SET_PAYMENT//
$paymentCollection = $order->getPaymentCollection();
$extPayment = $paymentCollection->createItem();
$extPayment->setField("SUM", $order->getPrice());


$arPaySystemServiceAll = Sale\PaySystem\Manager::getListWithRestrictions($extPayment);
reset($arPaySystemServiceAll);
$arPaySystem = current($arPaySystemServiceAll);
if (!empty($arPaySystem)) {
    $extPayment->setFields(array(
        "PAY_SYSTEM_ID" => $arPaySystem["ID"],
        "PAY_SYSTEM_NAME" => $arPaySystem["NAME"]
    ));
} else
    $extPayment->delete();

$order->doFinalAction(true);

//ORDER_SET_PROPERTIES//
$propertyCollection = $order->getPropertyCollection();

if (!empty($name)) {
    $fioProperty = $propertyCollection->getPayerName();
    if (!empty($fioProperty))
        $fioProperty->setValue($name);
}

if (!empty($phone)) {
    $phoneProperty = $propertyCollection->getPhone();
    if (!empty($phoneProperty))
        $phoneProperty->setValue($phone);
}

if (!empty($email)) {
    $emailProperty = $propertyCollection->getUserEmail();
    if (!empty($emailProperty))
        $emailProperty->setValue($email);
}

if (!empty($locCode)) {
    $locProperty = $propertyCollection->getDeliveryLocation();
    if (!empty($locProperty))
        $locProperty->setValue($locCode);
}

if (!empty($fileIds)) {
    foreach ($propertyCollection as $property) {
        if ($property->getField("CODE") == "BOC_FILE")
            $property->setValue($fileIds);
    }
}

//ORDER_SET_FIELDS//
$order->setField("CURRENCY", Option::get("sale", "default_currency"));

if (!empty($message))
    $order->setField("USER_DESCRIPTION", $message);
$order->setField("COMMENTS", Loc::getMessage("ORDER_COMMENT"));

$order->save();

$orderId = $order->GetId();

//MESSAGE//
if ($orderId > 0) {
    $result = array(
        "success" => array(
            "text" => Loc::getMessage("ORDER_CREATE_SUCCESS")
        )
    );
} else {
    $result = array(
        "error" => array(
            "text" => Loc::getMessage("ORDER_CREATE_ERROR"),
            "captcha_code" => !empty($captchaSid) ? $APPLICATION->CaptchaGetCode() : ""
        )
    );
}

echo Bitrix\Main\Web\Json::encode($result); ?>