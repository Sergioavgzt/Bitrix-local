<?define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Mail\Event;

if(!Loader::IncludeModule("iblock"))
	return;

Loc::loadMessages(__FILE__);

global $APPLICATION, $USER;

$request = Application::getInstance()->getContext()->getRequest();

$paramsString = $request->getPost("PARAMS_STRING");
if(!empty($paramsString))
	$params = unserialize(base64_decode(strtr($paramsString, "-_,", "+/=")));

$iblockString = $request->getPost("IBLOCK_STRING");
if(!empty($iblockString))
	$iblock = unserialize(base64_decode(strtr($iblockString, "-_,", "+/=")));

$elementString = $request->getPost("ELEMENT_STRING");
if(!empty($elementString))
	$element = unserialize(base64_decode(strtr($elementString, "-_,", "+/=")));

$name = $request->getPost("NAME");
$message = $request->getPost("MESSAGE");

$captchaWord = $request->getPost("CAPTCHA_WORD");
$captchaSid = $request->getPost("CAPTCHA_SID");

//CHECKS//
foreach($params["PROPERTIES"] as $arCode) {
	$post = $request->getPost($arCode);
	if(empty($post))
		$error .= Loc::getMessage($arCode."_NOT_FILLED")."<br />";
}

if(!empty($captchaSid) && !$APPLICATION->CaptchaCheckCode($captchaWord, $captchaSid))
	$error .= Loc::getMessage("WRONG_CAPTCHA")."<br />";

if(!empty($error)) {
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
$name = iconv("UTF-8", SITE_CHARSET, strip_tags(trim($name)));
$message = iconv("UTF-8", SITE_CHARSET, strip_tags(trim($message)));

$arProps = array(
	"OBJECT_ID" => $element["ID"],
	"USER_ID" => $name,
	"USER_IP" => $_SERVER["REMOTE_ADDR"],
	"COMMENT_URL" => $params["COMMENT_URL"]
);

//NEW_ELEMENT//
$el = new CIBlockElement;

$arFields = array(
	"NAME" => Loc::getMessage("IBLOCK_ELEMENT_NAME").ConvertTimeStamp(time(), "FULL"),
	"IBLOCK_ID" => $iblock["ID"],
	"ACTIVE" => $params["PRE_MODERATION"] != "Y" ? "Y" : "N",
	"ACTIVE_FROM" => ConvertTimeStamp(false, "FULL"),		
	"DETAIL_TEXT" => $message,
	"CREATED_BY" => $USER->GetID(),
	"PROPERTY_VALUES" => $arProps,
);

if($el->Add($arFields)) {
	//MAIL_EVENT//	
	$eventName = "ALTOP_FORM_catalog_review_".SITE_ID;

	$eventDesc = $messBody = "";	
	foreach($iblock["PROPERTIES"] as $key => $arProp) {
		$eventDesc .= "#".$arProp["CODE"]."# - ".$arProp["NAME"]."\n";
		$messBody .= $arProp["NAME"].": "."#".$arProp["CODE"]."#\n";		
	}	
	$eventDesc .= Loc::getMessage("MAIL_EVENT_DESCRIPTION");
	$messBody .= Loc::getMessage("MAIL_MESSAGE_BODY");
	
	//MAIL_EVENT_TYPE//
	$arEvent = CEventType::GetByID($eventName, LANGUAGE_ID)->Fetch();
	if(empty($arEvent)) {
		$et = new CEventType;
		$arEventFields = array(
			"LID" => LANGUAGE_ID,
			"EVENT_NAME" => $eventName,
			"NAME" => Loc::getMessage("MAIL_EVENT_TYPE_NAME"),
			"DESCRIPTION" => $eventDesc
		);
		$et->Add($arEventFields);		
	}

	//MAIL_EVENT_MESSAGE//
	$arMess = CEventMessage::GetList($by = "site_id", $order = "desc", array("TYPE_ID" => $eventName))->Fetch();
	if(empty($arMess)) {
		$em = new CEventMessage;
		$arMess = array();
		$arMess["ID"] = $em->Add(
			array(
				"ACTIVE" => "Y",
				"EVENT_NAME" => $eventName,
				"LID" => SITE_ID,
				"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
				"EMAIL_TO" => "#EMAIL_TO#",
				"BCC" => "",
				"SUBJECT" => Loc::getMessage("MAIL_EVENT_MESSAGE_SUBJECT"),
				"BODY_TYPE" => "text",
				"MESSAGE" => Loc::getMessage("MAIL_EVENT_MESSAGE_MESSAGE_HEADER").$messBody.Loc::getMessage("MAIL_EVENT_MESSAGE_MESSAGE_FOOTER")
			)
		);		
	}

	//SEND_MAIL//
	$arProps["OBJECT_ID"] = $element["ID"]." (".$element["NAME"].")";
	$arProps["MESSAGE"] = $message;
	$arProps["EMAIL_TO"] = Option::get("main", "email_from");
	
	Event::send(array(
		"EVENT_NAME" => $eventName,
		"LID" => SITE_ID,
		"C_FIELDS" => $arProps,
	));
	
	$result = array(
		"success" => array(
			"text" => $params["PRE_MODERATION"] != "Y" ? Loc::getMessage("SUCCESS_MESSAGE") : Loc::getMessage("PRE_MODERATION_MESSAGE")
		)
	);
} else {	
	$result = array(
		"error" => array(
			"text" => Loc::getMessage("ERROR_MESSAGE")."<br />".$el->LAST_ERROR,
			"captcha_code" => !empty($captchaSid) ? $APPLICATION->CaptchaGetCode() : ""
		)
	);
}

echo Bitrix\Main\Web\Json::encode($result);?>