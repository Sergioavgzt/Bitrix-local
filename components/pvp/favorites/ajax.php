<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
?>

<?php
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$data = json_decode($request->getPost('data'), true);

if (empty($data['componentParams'])) {
    die('wrong request');
}

$signedComponentParams = [];
$signer = new \Bitrix\Main\Security\Sign\Signer;
try
{
    $unsignedParams = $signer->unsign($data['componentParams'], 'pvp.favorites');
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
{
    die();
}

$arParams = unserialize(base64_decode($unsignedParams));
$arParams['MODE'] = 'AJAX';
?>

<?$APPLICATION->IncludeComponent(
    "pvp:favorites",
    "",
    $arParams
);?>
