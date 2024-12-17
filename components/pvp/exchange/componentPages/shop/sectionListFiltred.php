<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/**
 * @var \CMain $APPLICATION
 * @var PVP\Exchange\ExchangeComponent $exchangeComponent
 * @var CBitrixComponent $component pvp:exchange
 * @var array $arResult
 */
?>

<?php 
$APPLICATION->IncludeComponent(
    "bitrix:catalog.section.list",
    "",
    Array(
        "ADDITIONAL_COUNT_ELEMENTS_FILTER" => "additionalCountFilter",
        "ADD_SECTIONS_CHAIN" => "N",
        "CACHE_FILTER" => "Y",
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "COMPOSITE_FRAME_MODE" => "A",
        "COMPOSITE_FRAME_TYPE" => "AUTO",
        "COUNT_ELEMENTS" => "N",
        "COUNT_ELEMENTS_FILTER" => "CNT_ACTIVE",
        "FILTER_NAME" => "sectionsFilter",
        "HIDE_SECTIONS_WITH_ZERO_COUNT_ELEMENTS" => "N",
		"IBLOCK_ID" => $exchangeComponent->getCatalogParam("IBLOCK_ID"),
		"IBLOCK_TYPE" => $exchangeComponent->getCatalogParam("IBLOCK_TYPE"),
        "SECTION_CODE" => "",
		"SECTION_FIELDS" => ["ID", "CODE", "NAME", "DESCRIPTION", "SORT", "XML_ID", "IBLOCK_SECTION_ID", "DETAIL_PICTURE"],
        "SECTION_ID" => (isset($arResult['SECTION_ID']) ? $arResult['SECTION_ID'] : ''),
        "SECTION_URL" => "",
        "SECTION_USER_FIELDS" => array("UF_MOBILE_APP_IMG"),
        "SHOW_PARENT_NAME" => "Y",
        "TOP_DEPTH" => "5",
        "VIEW_MODE" => "LIST"
    ),
    $component
);
?>
