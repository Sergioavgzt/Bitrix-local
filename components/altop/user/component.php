<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams["PATH_TO_PERSONAL"] = trim($arParams["PATH_TO_PERSONAL"]);
if($arParams["PATH_TO_PERSONAL"] == "")
	$arParams["PATH_TO_PERSONAL"] = SITE_DIR."personal/";

global $USER;

if($this->StartResultCache(false, $userId = intval($USER->GetID()))) {
	if($userId > 0) {
		$rsUser = CUser::GetByID($userId);
		if($arUser = $rsUser->Fetch()) {
			//PERSONAL_PHOTO//
			if($arUser["PERSONAL_PHOTO"] > 0) {
				$arFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
				if($arFile["WIDTH"] > 90 || $arFile["HEIGHT"] > 90) {
					$arFileTmp = CFile::ResizeImageGet(
						$arFile,
						array("width" => 90, "height" => 90),
						BX_RESIZE_IMAGE_PROPORTIONAL,
						true
					);
					$arResult["USER"]["PERSONAL_PHOTO"] = array(
						"SRC" => $arFileTmp["src"],
						"WIDTH" => $arFileTmp["width"],
						"HEIGHT" => $arFileTmp["height"],
					);	
				} else {
					$arResult["USER"]["PERSONAL_PHOTO"] = $arFile;
				}
			}
		}
		
		//FIO//
		$arResult["USER"]["FIO"] = $USER->GetFullName();
		
		//LOGIN//
		$arResult["USER"]["LOGIN"] = $USER->GetLogin();
		$arResult['USER']['ID'] = $USER->GetID();
		$groupsIDArray = [];
		$userGroups = \Bitrix\Main\UserGroupTable::getList([
		    'filter' => [
		        'USER_ID' => $arResult['USER']['ID']
            ],
            'select' => [
                'GROUP_ID'
            ]
        ])->fetchAll();
		foreach ($userGroups as $userGroup) {
		    $groupsIDArray[] = $userGroup['GROUP_ID'];
        }
		$groupsData = \Bitrix\Main\GroupTable::getList([
		    'filter' => [
		        'ID' => $groupsIDArray
            ],
            'select' => [
                'NAME'
            ]
        ])->fetchAll();
		foreach ($groupsData as $groupName) {
		    if (strpos($groupName['NAME'], '%') !== false && intval($groupName['NAME']) !== 2) {
		        $arResult['USER']['DISCOUNT'] = intval($groupName['NAME']);
            }
        }
	} else {
		$this->abortResultCache();
		return;
	}

	$this->IncludeComponentTemplate();
}?>