<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/**
 * @var \CMain $APPLICATION
 * @var PVP\Exchange\ExchangeComponent $exchangeComponent
 * @var CBitrixComponent $component pvp:exchange
 * @var array $arResult
 */
?>

<?$APPLICATION->IncludeComponent("pvp:sale.personal.order.list",	"",
    array(
        "PATH_TO_DETAIL" => "/personal/orders/#ID#/",
        "PATH_TO_CANCEL" => "/personal/cancel/#ID#/",
        "PATH_TO_CATALOG" => "/catalog/",
        "PATH_TO_COPY" => "/personal/orders/?COPY_ORDER=Y&ID=#ID#",
        "PATH_TO_BASKET" => "/personal/cart",
        "PATH_TO_PAYMENT" => "/personal/order/payment",
        "SAVE_IN_SESSION" => "Y",
        "ORDERS_PER_PAGE" => "20",
        "SET_TITLE" => "Y",
        "ID" => NULL,
        "NAV_TEMPLATE" => "",
        "ACTIVE_DATE_FORMAT" => "d.m.Y",
        "HISTORIC_STATUSES" => array(0=>"F",),
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => "36000000",
        "CACHE_GROUPS" => "Y",
    ),
    $component
);?>