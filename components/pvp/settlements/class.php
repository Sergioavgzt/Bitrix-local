<?php

use Bitrix\Sale\Internals\UserPropsTable;
use Bitrix\Sale\Internals\UserPropsValueTable;

class CheckCodeComponent extends CBitrixComponent
{
	public function executeComponent()
	{
        /** @var CUser $USER $ */
        global $USER;

        if (! $USER->IsAuthorized()) {
            return;
        }

        $profiles = $this->getProfiles($USER);
        $profiles = array_column($profiles, null, 'ID');

        $profileId = $this->request->getPost('profileId');

        if ($profileId) {
            $this->arResult['PROFILE_ID'] = array_key_exists($profileId, $profiles) ? $profileId : 0;
        } else { //по умолчанию выводим первый профиль
            foreach ($profiles as $profile) {
                $this->arResult['PROFILE_ID'] = $profile['ID'];
                break;
            }
        }

        $this->arResult['PROFILES'] = $profiles;

        if ($this->arResult['PROFILE_ID']) {
            $this->arResult['CURRENT_DEBT'] = $this->getPropertyValue($this->arResult['PROFILE_ID'], $this->arParams['CURRENT_DEBT']);
            $this->arResult['OVERDUE_DEBT'] = $this->getPropertyValue($this->arResult['PROFILE_ID'], $this->arParams['OVERDUE_DEBT']);
            $this->arResult['PRODUCT_LIMIT'] = $this->getPropertyValue($this->arResult['PROFILE_ID'], $this->arParams['PRODUCT_LIMIT']);
            $this->arResult['REMAINING_LIMIT'] = $this->getPropertyValue($this->arResult['PROFILE_ID'], $this->arParams['REMAINING_LIMIT']);
        }

        $this->initComponentTemplate();
        $this->showComponentTemplate();
	}

    protected function getProfiles(\CUser $user): array
    {
        $dbItems = UserPropsTable::getList([
            'filter' => ['USER_ID' => $user->GetID()]
        ])->fetchAll();

        return (array)$dbItems;
    }

    protected function getPropertyValue(int $profileId, array $propertyIds): int
    {
        $dbItems = UserPropsValueTable::getList([
            'select' => ['VALUE'],
            'filter' => [
                'USER_PROPS_ID' => $this->arResult['PROFILE_ID'],
                'ORDER_PROPS_ID' => $propertyIds
            ]
        ])->fetchAll();

        if($dbItems) {
            return (int)$dbItems[0]['VALUE'];
        }

        return 0;
    }
}