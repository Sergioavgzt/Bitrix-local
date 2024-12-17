<?php

use Bitrix\Sale;
use Bitrix\Sale\Fuser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class OrderExportXlsComponent extends CBitrixComponent
{
	public function executeComponent()
	{
        if (! $this->request->isPost() || ! $this->request->getPost('pvp_order_exportxls') || 1 != $this->request->getPostList()->count()) {
            $this->initComponentTemplate();
            $this->showComponentTemplate();

            return;
        }

        if ('BASKET' == $this->arParams['MODE'] && 'ORDER' == $this->arParams['MODE']) {
            die('wrong request');
        }

        $rowCount = 1;

        $fUserId = Fuser::getId();

        $tmpFileName = uniqid($fUserId . '-') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        $activeWorksheet->getDefaultColumnDimension()->setWidth(50, 'pt');

        if ('BASKET' == $this->arParams['MODE']) {
            $basket = \Bitrix\Sale\Basket::loadItemsForFUser($fUserId, \Bitrix\Main\Context::getCurrent()->getSite());
            $rowCount++; //Так в тз

            $discounts = Sale\Discount::buildFromBasket($basket, new Sale\Discount\Context\Fuser($fUserId));
        } else {
            if (empty($this->arParams['ORDER_ID'])) {
                die('wrong request');
            }

            $order = \Bitrix\Sale\Order::load((int)$this->arParams['ORDER_ID']);
            $basket = $order->getBasket();

            $discounts = Sale\Discount::buildFromOrder($order);

            $style = $activeWorksheet->getStyle('A' . $rowCount);
            $this->setBaseCellStyle($style);

            $style = $activeWorksheet->getStyle('B' . $rowCount);
            $this->setBaseCellStyle($style);

            $activeWorksheet->setCellValue('C' . $rowCount, 'Заказ № ' . $order->getId() . ' от ' . $order->getDateInsert());
            $style = $activeWorksheet->getStyle('C' . $rowCount);
            $this->setBaseCellStyle($style);
            $style->getAlignment()->setWrapText(true);
            $activeWorksheet->getColumnDimension('C')->setWidth(200, 'pt');

            $style = $activeWorksheet->getStyle('D' . $rowCount);
            $this->setBaseCellStyle($style);

            $style = $activeWorksheet->getStyle('E' . $rowCount);
            $this->setBaseCellStyle($style);

            $style = $activeWorksheet->getStyle('F' . $rowCount);
            $this->setBaseCellStyle($style);

            $style = $activeWorksheet->getStyle('G' . $rowCount);
            $this->setBaseCellStyle($style);
            $rowCount++;
        }

        $discounts->calculate();
        $prices = $discounts->getApplyResult(true);

        $prices = $prices['PRICES']['BASKET'];

        $items = $basket->toArray();

        $articles = [];
        if (! empty($this->arParams['PROPERTY_CODE'])) {
            $productIds = array_column($items, 'PRODUCT_ID');
            $articles = [];

            $dbItems = CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'ID' => $productIds],
                false,
                false,
                ['ID', 'PROPERTY_' . $this->arParams['PROPERTY_CODE']]
            );

            while($res = $dbItems->GetNext()) {
                $articles[$res['ID']] = $res['PROPERTY_' . $this->arParams['PROPERTY_CODE'] .'_VALUE'];
            }

        }

        $activeWorksheet->setCellValue('A' . $rowCount, '№');
        $style = $activeWorksheet->getStyle('A' . $rowCount);
        $this->setBaseCellStyle($style);

        $activeWorksheet->setCellValue('B' . $rowCount, 'Код');
        $style = $activeWorksheet->getStyle('B' . $rowCount);
        $this->setBaseCellStyle($style);

        $activeWorksheet->setCellValue('C' . $rowCount, 'Наименование');
        $style = $activeWorksheet->getStyle('C' . $rowCount);
        $this->setBaseCellStyle($style);
        $style->getAlignment()->setWrapText(true);
        $activeWorksheet->getColumnDimension('C')->setWidth(200, 'pt');

        $activeWorksheet->setCellValue('D' . $rowCount, 'Цена, шт');
        $style = $activeWorksheet->getStyle('D' . $rowCount);
        $this->setBaseCellStyle($style);

        $activeWorksheet->setCellValue('E' . $rowCount, 'Кол-во');
        $style = $activeWorksheet->getStyle('E' . $rowCount);
        $this->setBaseCellStyle($style);

        $activeWorksheet->setCellValue('F' . $rowCount, 'Сумма, руб');
        $style = $activeWorksheet->getStyle('F' . $rowCount);
        $this->setBaseCellStyle($style);
        $style->getAlignment()->setWrapText(true);

        $activeWorksheet->setCellValue('G' . $rowCount, 'Сумма руб, со скидкой');
        $style = $activeWorksheet->getStyle('G' . $rowCount);
        $this->setBaseCellStyle($style);
        $style->getAlignment()->setWrapText(true);


        $rowCount++;

        $count = 1;
        $totalSum = 0;
        $totalSumWithDiscount = 0;

        /** @var Bitrix\Sale\BasketItem $item */
        foreach ($basket->getBasketItems() as $item) {
            /**
             * Фиксим потерянные товары с фалгом CAN_BUY = N
             */
            if ('BASKET' == $this->arParams['MODE'] && ! $item->canBuy()) {
                continue;
            }

            $article = empty($articles[$item->getProductId()]) ? '' : $articles[$item->getProductId()];

            $discountPrice = empty($prices[$item->getId()]) ? $item->getPrice() : $prices[$item->getId()]['PRICE'];

            $sumWithDiscount =  $discountPrice * $item->getQuantity();
            $totalSumWithDiscount += $sumWithDiscount;

            $sum = $item->getBasePrice() * $item->getQuantity();
            $totalSum += $sum;

            $activeWorksheet->setCellValue('A' . $rowCount, $count++);
            $style = $activeWorksheet->getStyle('A' . $rowCount);
            $this->setBaseCellStyle($style);

            $activeWorksheet->setCellValue('B' . $rowCount, $article);
            $style = $activeWorksheet->getStyle('B' . $rowCount);
            $this->setBaseCellStyle($style);

            $activeWorksheet->setCellValue('C' . $rowCount, $item->getField('NAME'));
            $style = $activeWorksheet->getStyle('C' . $rowCount);
            $this->setBaseCellStyle($style);$style->getAlignment()->setWrapText(true);
            $activeWorksheet->getColumnDimension('C')->setWidth(160, 'pt');

            $activeWorksheet->setCellValue('D' . $rowCount, round($item->getBasePrice(), 2));
            $style = $activeWorksheet->getStyle('D' . $rowCount);
            $this->setBaseCellStyle($style);

            $activeWorksheet->setCellValue('E' . $rowCount, round($item->getQuantity()));
            $style = $activeWorksheet->getStyle('E' . $rowCount);
            $this->setBaseCellStyle($style);

            $activeWorksheet->setCellValue('F' . $rowCount, $sum);
            $style = $activeWorksheet->getStyle('F' . $rowCount);
            $this->setBaseCellStyle($style);

            $activeWorksheet->setCellValue('G' . $rowCount, round($sumWithDiscount, 2));
            $style = $activeWorksheet->getStyle('G' . $rowCount);
            $this->setBaseCellStyle($style);
            $style->getAlignment()->setWrapText(true);

            $rowCount++;
        }

        $activeWorksheet->setCellValue('D' . $rowCount, 'Итого:');
        $this->setBaseCellStyle($activeWorksheet->getStyle('D' . $rowCount));

        $activeWorksheet->setCellValue('E' . $rowCount, round($totalSum, 2));
        $this->setBaseCellStyle($activeWorksheet->getStyle('E' . $rowCount));

        $activeWorksheet->setCellValue('F' . $rowCount, 'Итого со скидкой:');
        $style = $activeWorksheet->getStyle('F' . $rowCount);
        $this->setBaseCellStyle($style);
        $style->getAlignment()->setWrapText(true);

        $activeWorksheet->setCellValue('G' . $rowCount, round($totalSumWithDiscount, 2));
        $this->setBaseCellStyle($activeWorksheet->getStyle('G' . $rowCount));

        $writer = new Xlsx($spreadsheet);

        //Вероятно тут несколько буферов
        while(ob_end_clean()){}

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $tmpFileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');

        exit;
    }

    protected function setBaseCellStyle(PhpOffice\PhpSpreadsheet\Style\Style $style): void
    {
        $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    }
}