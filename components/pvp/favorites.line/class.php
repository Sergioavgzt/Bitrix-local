<?php

class FavoritesLineComponent extends CBitrixComponent
{
	public function executeComponent()
	{
        if (! \Bitrix\Main\Loader::includeModule('pvp.favorites')) {
            throw new \Exception('Модуль не найден: pvp.favorites');
        }

        $favorites = new PVP\Favorites\Favorites();

        $this->arResult['QUANTITY'] = $favorites->getCount(\Bitrix\Main\Engine\CurrentUser::get()->getId());

        $this->initComponentTemplate();
        $this->showComponentTemplate();
	}
}