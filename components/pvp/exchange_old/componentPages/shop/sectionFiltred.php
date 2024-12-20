<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/**
 * @var \CMain $APPLICATION
 * @var PVP\Exchange\ExchangeComponent $exchangeComponent
 * @var CBitrixComponent $component pvp:exchange
 */

$sortField = isset($arResult['SORT_FIELD']) ? $arResult['SORT_FIELD'] : $exchangeComponent->getCatalogParam("ELEMENT_SORT_FIELD");
$sortOrder = isset($arResult['SORT_ORDER']) ? $arResult['SORT_ORDER'] : $exchangeComponent->getCatalogParam("ELEMENT_SORT_ORDER");
?>
<?php $APPLICATION->IncludeComponent("bitrix:catalog.section", "",
    array(
        "IBLOCK_TYPE" => $exchangeComponent->getCatalogParam("IBLOCK_TYPE"),
        "IBLOCK_ID" => $exchangeComponent->getCatalogParam("IBLOCK_ID"),
        "ELEMENT_SORT_FIELD" => $sortField,
        "ELEMENT_SORT_ORDER" => $sortOrder,
        "ELEMENT_SORT_FIELD2" => "",
        "ELEMENT_SORT_ORDER2" => "",
        "PROPERTY_CODE" => $exchangeComponent->getCatalogParam("LIST_PROPERTY_CODE"),
        "META_KEYWORDS" => $exchangeComponent->getCatalogParam("LIST_META_KEYWORDS"),
        "META_DESCRIPTION" => $exchangeComponent->getCatalogParam("LIST_META_DESCRIPTION"),
        "BROWSER_TITLE" => $exchangeComponent->getCatalogParam("LIST_BROWSER_TITLE"),
        "SET_LAST_MODIFIED" => $exchangeComponent->getCatalogParam("SET_LAST_MODIFIED"),
        "INCLUDE_SUBSECTIONS" => $exchangeComponent->getCatalogParam("INCLUDE_SUBSECTIONS"),
        "SHOW_ALL_WO_SECTION" => $exchangeComponent->getCatalogParam("PRODUCT_TYPE") ? "Y" : "N",
        "BASKET_URL" => $exchangeComponent->getCatalogParam("BASKET_URL"),
        "ACTION_VARIABLE" => $exchangeComponent->getCatalogParam("ACTION_VARIABLE"),
        "PRODUCT_ID_VARIABLE" => $exchangeComponent->getCatalogParam("PRODUCT_ID_VARIABLE"),
        "SECTION_ID_VARIABLE" => $exchangeComponent->getCatalogParam("SECTION_ID_VARIABLE"),
        "PRODUCT_QUANTITY_VARIABLE" => $exchangeComponent->getCatalogParam("PRODUCT_QUANTITY_VARIABLE"),
        "PRODUCT_PROPS_VARIABLE" => $exchangeComponent->getCatalogParam("PRODUCT_PROPS_VARIABLE"),
        "FILTER_NAME" => $exchangeComponent->getCatalogParam("FILTER_NAME"),
        "CACHE_TYPE" => $exchangeComponent->getCatalogParam("CACHE_TYPE"),
        "CACHE_TIME" => $exchangeComponent->getCatalogParam("CACHE_TIME"),
        "CACHE_FILTER" => $exchangeComponent->getCatalogParam("CACHE_FILTER"),
        "CACHE_GROUPS" => $exchangeComponent->getCatalogParam("CACHE_GROUPS"),
        "SET_TITLE" => $exchangeComponent->getCatalogParam("SET_TITLE"),
        "MESSAGE_404" => $exchangeComponent->getCatalogParam("MESSAGE_404"),
        "SET_STATUS_404" => $exchangeComponent->getCatalogParam("SET_STATUS_404"),
        "SHOW_404" => $exchangeComponent->getCatalogParam("SHOW_404"),
        "FILE_404" => $exchangeComponent->getCatalogParam("FILE_404"),
        "DISPLAY_COMPARE" => $exchangeComponent->getCatalogParam("USE_COMPARE"),
        "PAGE_ELEMENT_COUNT" => $exchangeComponent->getCatalogParam("PAGE_ELEMENT_COUNT"),
        "LINE_ELEMENT_COUNT" => $exchangeComponent->getCatalogParam("LINE_ELEMENT_COUNT"),
        "PRICE_CODE" => $exchangeComponent->getCatalogParam("PRICE_CODE"),
        "USE_PRICE_COUNT" => $exchangeComponent->getCatalogParam("USE_PRICE_COUNT"),
        "SHOW_PRICE_COUNT" => $exchangeComponent->getCatalogParam("SHOW_PRICE_COUNT"),
        "PRICE_VAT_INCLUDE" => $exchangeComponent->getCatalogParam("PRICE_VAT_INCLUDE"),
        "USE_PRODUCT_QUANTITY" => $exchangeComponent->getCatalogParam("USE_PRODUCT_QUANTITY"),
        "ADD_PROPERTIES_TO_BASKET" => $exchangeComponent->getCatalogParam("ADD_PROPERTIES_TO_BASKET") ? $exchangeComponent->getCatalogParam("ADD_PROPERTIES_TO_BASKET") : '',
        "PARTIAL_PRODUCT_PROPERTIES" => $exchangeComponent->getCatalogParam("PARTIAL_PRODUCT_PROPERTIES") ? $exchangeComponent->getCatalogParam("PARTIAL_PRODUCT_PROPERTIES") : '',
        "PRODUCT_PROPERTIES" => $exchangeComponent->getCatalogParam("PRODUCT_PROPERTIES"),
        "DISPLAY_TOP_PAGER" => $exchangeComponent->getCatalogParam("DISPLAY_TOP_PAGER"),
        "DISPLAY_BOTTOM_PAGER" => $exchangeComponent->getCatalogParam("DISPLAY_BOTTOM_PAGER"),
        "PAGER_TITLE" => $exchangeComponent->getCatalogParam("PAGER_TITLE"),
        "PAGER_SHOW_ALWAYS" => $exchangeComponent->getCatalogParam("PAGER_SHOW_ALWAYS"),
        "PAGER_TEMPLATE" => $exchangeComponent->getCatalogParam("PAGER_TEMPLATE"),
        "PAGER_DESC_NUMBERING" => $exchangeComponent->getCatalogParam("PAGER_DESC_NUMBERING"),
        "PAGER_DESC_NUMBERING_CACHE_TIME" => $exchangeComponent->getCatalogParam("PAGER_DESC_NUMBERING_CACHE_TIME"),
        "PAGER_SHOW_ALL" => $exchangeComponent->getCatalogParam("PAGER_SHOW_ALL"),
        "PAGER_BASE_LINK_ENABLE" => $exchangeComponent->getCatalogParam("PAGER_BASE_LINK_ENABLE"),
        "PAGER_BASE_LINK" => $exchangeComponent->getCatalogParam("PAGER_BASE_LINK"),
        "PAGER_PARAMS_NAME" => $exchangeComponent->getCatalogParam("PAGER_PARAMS_NAME"),
        "LAZY_LOAD" => $exchangeComponent->getCatalogParam("LAZY_LOAD") ? $exchangeComponent->getCatalogParam("LAZY_LOAD") : "Y",
        "MESS_BTN_LAZY_LOAD" => $exchangeComponent->getCatalogParam("~MESS_BTN_LAZY_LOAD") ? $exchangeComponent->getCatalogParam("~MESS_BTN_LAZY_LOAD") : "",
        "LOAD_ON_SCROLL" => $exchangeComponent->getCatalogParam("LOAD_ON_SCROLL") ? $exchangeComponent->getCatalogParam("LOAD_ON_SCROLL") : "Y",
        "OFFERS_CART_PROPERTIES" => $exchangeComponent->getCatalogParam("OFFERS_CART_PROPERTIES"),
        "OFFERS_FIELD_CODE" => $exchangeComponent->getCatalogParam("LIST_OFFERS_FIELD_CODE"),
        "OFFERS_PROPERTY_CODE" => $exchangeComponent->getCatalogParam("LIST_OFFERS_PROPERTY_CODE"),
        "OFFERS_SORT_FIELD" => $exchangeComponent->getCatalogParam("OFFERS_SORT_FIELD"),
        "OFFERS_SORT_ORDER" => $exchangeComponent->getCatalogParam("OFFERS_SORT_ORDER"),
        "OFFERS_SORT_FIELD2" => $exchangeComponent->getCatalogParam("OFFERS_SORT_FIELD2"),
        "OFFERS_SORT_ORDER2" => $exchangeComponent->getCatalogParam("OFFERS_SORT_ORDER2"),
        "OFFERS_LIMIT" => $exchangeComponent->getCatalogParam("LIST_OFFERS_LIMIT"),
        "SECTION_ID" => (isset($arResult['SECTION_ID']) ? $arResult['SECTION_ID'] : ""),
        "SECTION_CODE" => "",
        "SECTION_URL" => "",
        "DETAIL_URL" => "",
        "USE_MAIN_ELEMENT_SECTION" => $exchangeComponent->getCatalogParam("USE_MAIN_ELEMENT_SECTION"),
        "CONVERT_CURRENCY" => $exchangeComponent->getCatalogParam("CONVERT_CURRENCY"),
        "CURRENCY_ID" => $exchangeComponent->getCatalogParam("CURRENCY_ID"),
        "HIDE_NOT_AVAILABLE" => $exchangeComponent->getCatalogParam("HIDE_NOT_AVAILABLE"),
        "HIDE_NOT_AVAILABLE_OFFERS" => $exchangeComponent->getCatalogParam("HIDE_NOT_AVAILABLE_OFFERS"),
        "PRODUCT_ROW_VARIANTS" => $exchangeComponent->getCatalogParam("LIST_PRODUCT_ROW_VARIANTS"),
        "TYPE" => 'table',
        "ADD_SECTIONS_CHAIN" => "N",
        "COMPARE_PATH" => "",
        "BACKGROUND_IMAGE" => $exchangeComponent->getCatalogParam("SECTION_BACKGROUND_IMAGE") ? $exchangeComponent->getCatalogParam("SECTION_BACKGROUND_IMAGE") : "",
        "DISABLE_INIT_JS_IN_COMPONENT" => $exchangeComponent->getCatalogParam("DISABLE_INIT_JS_IN_COMPONENT") ? $exchangeComponent->getCatalogParam("DISABLE_INIT_JS_IN_COMPONENT") : "",
        "DISPLAY_IMG_WIDTH"	 =>	$exchangeComponent->getCatalogParam("DISPLAY_IMG_WIDTH"),
        "DISPLAY_IMG_HEIGHT" =>	$exchangeComponent->getCatalogParam("DISPLAY_IMG_HEIGHT"),
        "PROPERTY_CODE_MOD" => "",
        "SHOW_MAX_QUANTITY" => $exchangeComponent->getCatalogParam("SHOW_MAX_QUANTITY"),
        "MESS_SHOW_MAX_QUANTITY" => $exchangeComponent->getCatalogParam("~MESS_SHOW_MAX_QUANTITY") ? $exchangeComponent->getCatalogParam("~MESS_SHOW_MAX_QUANTITY") : "",
        "RELATIVE_QUANTITY_FACTOR" => $exchangeComponent->getCatalogParam("RELATIVE_QUANTITY_FACTOR") ? $exchangeComponent->getCatalogParam("RELATIVE_QUANTITY_FACTOR") : "",
        "MESS_RELATIVE_QUANTITY_MANY" => $exchangeComponent->getCatalogParam("~MESS_RELATIVE_QUANTITY_MANY") ? $exchangeComponent->getCatalogParam("~MESS_RELATIVE_QUANTITY_MANY") : "",
        "MESS_RELATIVE_QUANTITY_FEW" => $exchangeComponent->getCatalogParam("~MESS_RELATIVE_QUANTITY_FEW") ? $exchangeComponent->getCatalogParam("~MESS_RELATIVE_QUANTITY_FEW") : "",
        "MAIN_BLOCK_PROPERTY_CODE" => $exchangeComponent->getCatalogParam("DETAIL_MAIN_BLOCK_PROPERTY_CODE"),
        "MAIN_BLOCK_OFFERS_PROPERTY_CODE" => $exchangeComponent->getCatalogParam("DETAIL_MAIN_BLOCK_OFFERS_PROPERTY_CODE"),
        "BUTTON_PAYMENTS_HREF" => $exchangeComponent->getCatalogParam("BUTTON_PAYMENTS_HREF"),
        "BUTTON_CREDIT_HREF" => $exchangeComponent->getCatalogParam("BUTTON_CREDIT_HREF"),
        "BUTTON_DELIVERY_HREF" => $exchangeComponent->getCatalogParam("BUTTON_DELIVERY_HREF"),
    ),
    $component
);?>