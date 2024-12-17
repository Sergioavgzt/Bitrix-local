<?php

class CheckCodeComponent extends CBitrixComponent
{
	public function executeComponent()
	{
        if (! \Bitrix\Main\Loader::includeModule('pvp.checkcode')) {
            throw new \Exception('Модуль не найден: pvp.checkcode');
        }

        $checkCodeObj = new \PVP\CheckCode\CheckCode();

        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        $orderExists = false;
        $code = '';

        if ($request->isPost()) {
            $orderId = $request->getPost('order_id');
            $orderExists = $checkCodeObj->isOrderExists($orderId);
            $code = (int)$request->getPost('code');

            if ($orderId && $orderExists && $code) {
                if ($checkCodeObj->check($orderId, $code)) {
                    $this->arResult['RESULT']['CLASS'] = 'success';
                    $this->arResult['RESULT']['MESSAGE'] = 'Заказ подтвержден!';
                } else {
                    $this->arResult['RESULT']['CLASS'] = 'error';
                    $this->arResult['RESULT']['MESSAGE'] = 'Ошибка подтверждения!';
                }
            }
        } else {
            if ($orderId = $request->get('ORDER_ID')) {
                $orderExists = $checkCodeObj->isOrderExists($orderId);
            }
        }

        $this->arResult['ORDER_ID'] = $orderExists ? $orderId : '*****';
        $this->arResult['CODE'] = $code;

        if (! $orderExists) {
            $this->arResult['RESULT']['CLASS'] = 'error';
            $this->arResult['RESULT']['MESSAGE'] = 'Заказ не найден';
        }

        $this->initComponentTemplate();
        $this->showComponentTemplate();
	}
}