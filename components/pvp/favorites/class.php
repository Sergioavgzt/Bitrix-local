<?php

use PVP\Favorites\Favorites;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class FavoritesComponent extends CBitrixComponent
{
    protected $allowedMethods = ['add', 'delete', 'deleteAll', 'getExistsIdFromList', 'getCount', 'filterList'];
    protected $favoritesObj;

    public function __construct($component = null)
    {
        parent::__construct($component);

        if (! \Bitrix\Main\Loader::includeModule('pvp.favorites')) {
            throw new \Exception('Модуль не найден!');
        }

        $this->favoritesObj = new Favorites();
        $this->userId = (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();
    }

    public function executeComponent()
    {
        $application = \Bitrix\Main\Application::getInstance();
        $request = $application->getContext()->getRequest();

        if ($request->isPost() && $data = $request->getPost('data')) {
            $data = json_decode($data, true);

            if (isset($data['action'])
                && in_array($data['action'], $this->allowedMethods)
                && method_exists($this, $data['action'])) {

                $this->arResult['RESULT'] = $this->{$data['action']}($data['params']);
            }

            if ('AJAX' == $this->arParams['MODE'] && 'json' == $data['mode']) {
                $this->initComponentTemplate('ajaxJson');
                $this->showComponentTemplate();
                return;
            }
        }

        //Load js controller
        $this->initComponentTemplate('controller');
        $this->showComponentTemplate();

        if ('CONTROLLER' == $this->arParams['MODE']) {
            return;
        }

        if ((int)$orderId = $request->get('addFormOrder')) {
            $this->addFromOrder($orderId);


           $response = new \Bitrix\Main\Engine\Response\Redirect($request->getRequestedPage());
           $response->send();
        }

        $this->arResult['PRODUCT_IDS'] = $this->favoritesObj->getForUser($this->userId, $this->arParams['IBLOCK_ID']);
        $this->arResult['FILTER_SECTION'] = $this->favoritesObj->getSectionList();


        $entity = \Bitrix\Iblock\Model\Section::compileEntityByIblock($this->arParams['IBLOCK_ID']);

        $dbItems = $entity::getList([
            'select' => ['ID', 'NAME', 'IBLOCK_ID', 'UF_SECTION_ICON'],
            'order' => ['SORT' => 'ASC'],
            'filter' => [
                'ACTIVE' => 'Y',
                'DEPTH_LEVEL' => 1
            ]
        ])->fetchAll();

        foreach ($dbItems as $item) {
            $item['ICON_SRC'] = '';

            if ($item['UF_SECTION_ICON']) {
                $iconPath = CFile::GetPath($item['UF_SECTION_ICON']);
                $item['ICON_SRC'] = (string)$iconPath;
            }

            $this->arResult['SECTIONS'][] = $item;
        }

        $this->initComponentTemplate('AJAX' == $this->arParams['MODE'] ? 'ajax' : '');
        $this->showComponentTemplate();
    }

    protected function addFromOrder(int $orderId)
    {
        $order = \Bitrix\Sale\Order::load($orderId);
        $userId = \Bitrix\Main\Engine\CurrentUser::get()->getId();

        if (! $order || $order->getUserId() != $userId) {
            return false;
        }

        $productIds = [];

        /**
         * @var Bitrix\Sale\BasketItem $item
         */
        foreach ($order->getBasket()->getBasketItems() as $item) {
            $productIds[] = $item->getProductId();
        }

        $this->add($productIds);
    }

    protected function filterList(array $params)
    {
        $this->favoritesObj->setSectionList($params);
    }

    protected function getExistsIdFromList(array $params)
    {
        $productIds = $this->favoritesObj->getExistsIdFromList($this->userId, $params);

        return $productIds;
    }

    protected function add(array $params)
    {
        foreach ($params as $productId) {
            $this->favoritesObj->add($this->userId, $productId);
        }

        return $params;
    }

    protected function getCount(array $params)
    {
        return $this->favoritesObj->getCount($this->userId);
    }

    protected function delete(array $params)
    {
        foreach ($params as $productId) {
            $this->favoritesObj->delete($this->userId, $productId);
        }

        return $params;
    }

    protected function deleteAll(array $params)
    {
        $this->favoritesObj->deleteAll($this->userId);

        return $params;
    }
}