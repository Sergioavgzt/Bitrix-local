<?php
/** @global \CMain $APPLICATION */
define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

if (!\Bitrix\Main\Loader::includeModule('iblock'))
    return;

$signer = new \Bitrix\Main\Security\Sign\Signer;
try {
    $paramString = $signer->unsign($request->get('params') ?: '', 'pvp__search.property');
} catch (\Bitrix\Main\Security\Sign\BadSignatureException $e) {
    die();
}

$parameters = unserialize(base64_decode($paramString), ['allowed_classes' => false]);
?>
<?$APPLICATION->IncludeComponent(
	"pvp:search.property",
	"",
    $parameters
);?>


