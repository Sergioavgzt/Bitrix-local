<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
?>
<?php
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$signedComponentParams = $request->getPost('componentParams');

if (empty($signedComponentParams)) {
    die('wrong request');
}

$signer = new \Bitrix\Main\Security\Sign\Signer;
try
{
    $unsignedParams = $signer->unsign($signedComponentParams, 'pvp.order.exportxls');
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
{
    die();
}

$arParams = unserialize(base64_decode($unsignedParams));
?>
<?$APPLICATION->IncludeComponent(
    "pvp:order.exportxls",
    "",
    $arParams
);?>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
?>
