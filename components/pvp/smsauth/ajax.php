<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
if (! $request->getPost('action') || ! $request->getPost('componentParams')) {
    http_response_code(403);
    die('Forbidden!');
}

$signedComponentParams = [];
$signer = new \Bitrix\Main\Security\Sign\Signer;
try
{
    $unsignedParams = $signer->unsign($request->getPost('componentParams'), 'pvp.smsauth');
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
{
    http_response_code(400);
    die($e->getMessage());
}

$arParams = unserialize(base64_decode($unsignedParams));
?>
<?$APPLICATION->IncludeComponent(
    "pvp:smsauth",
    "",
    $arParams
);?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';?>
