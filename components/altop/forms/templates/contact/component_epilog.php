<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

//JS_CORE//
if(class_exists("Bitrix\Main\UI\FileInput", true))
	CJSCore::Init(array("fileinput"));

$captchaCode = $arParams["USE_CAPTCHA"] == "Y" ? $APPLICATION->CaptchaGetCode() : "";
if(!empty($captchaCode)) {?>
	<script type="text/javascript">
		var form = BX("<?=$arResult['ELEMENT_AREA_ID']?>_form"),
			captchaWord = BX.findChild(form, {attribute: {name: "CAPTCHA_WORD"}}, true, false),
			captchaImg = BX.findChild(form, {tagName: "img"}, true, false),
			captchaSid = BX.findChild(form, {attribute: {name: "CAPTCHA_SID"}}, true, false);

		if(!!captchaWord)
			captchaWord.value = "";		
		if(!!captchaImg)
			BX.adjust(captchaImg, {props: {src: "/bitrix/tools/captcha.php?captcha_sid=<?=$captchaCode?>"}, style: {display: ""}});
		if(!!captchaSid)
			captchaSid.value = "<?=$captchaCode?>";
	</script>
<?}?>