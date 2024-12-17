<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class SearchPropertyComponent extends CBitrixComponent
{

    public function executeComponent()
	{
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

        if ($request->isPost() && $request->get('q')) {
            $property = \Bitrix\Iblock\PropertyTable::getList([
                'select' => ['ID', 'NAME'],
                'filter' => ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'CODE' => $this->arParams['PROPERTY_CODE']]
            ])->fetchAll();

            $property = $property[0];
            $elementIds = \Bitrix\Iblock\ElementPropertyTable::getList([
                'select' => ['IBLOCK_ELEMENT_ID'],
                'filter' => ['ELEMENT.ACTIVE' => 'Y', 'IBLOCK_PROPERTY_ID' => $property['ID'], '%=VALUE' => '%' . trim($request->get('q')) . '%'],
                'limit' => 8,
            ])->fetchAll();

            $elementIds = array_column($elementIds, 'IBLOCK_ELEMENT_ID');

            $this->arResult['ELEMENT_IDS'] = $elementIds;
            $this->initComponentTemplate('ajax');
            $this->showComponentTemplate();

            return;
        }

        $this->initComponentTemplate();
        $this->showComponentTemplate();
	}
}