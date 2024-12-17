<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CheckCodeComponent extends CBitrixComponent
{
	public function executeComponent()
	{
        if (! \Bitrix\Main\Loader::includeModule('pvp.checkcode')) {
            throw new \Exception('Модуль не найден: pvp.checkcode');
        }

        $checkLogObj = new \PVP\CheckCode\CheckLog();

        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

        $this->arResult['DATE_TO'] = (new DateTime())->format('d.m.Y');
        $this->arResult['DATE_FROM'] = (new DateTime("yesterday"))->format('d.m.Y');
        $this->arResult['RESULT'] = [];

        if ($request->isPost()) {
            $dateFrom = new \Bitrix\Main\Type\DateTime($request->getPost('date_from'));
            $dateTo = new \Bitrix\Main\Type\DateTime($request->getPost('date_to'));

            $this->arResult['DATE_TO'] = $dateTo->format('d.m.Y');
            $this->arResult['DATE_FROM'] = $dateFrom->format('d.m.Y');

            $result = \PVP\CheckCode\OrderChecksTable::getList([
                    'order' => ['DATE_CREATE' => 'DESC'],
                    'filter' => [
                        '>=DATE_CREATE' => $dateFrom,
                        '<=DATE_CREATE' => $dateTo,
                    ],
                    'limit' => 500,
            ])->fetchAll();

            if ('export-excel' == $request->getPost('action')) {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setCellValue('A1', 'Дата');
                $sheet->setCellValue('B1', 'Заказ #');
                $sheet->setCellValue('C1', 'Статус');

                $index = 2;
                foreach ($result as $row) {
                    $sheet->setCellValue('A' . $index, $row['DATE_CREATE']->format('d.m.Y H:i:s'));
                    $sheet->setCellValue('B' . $index, $row['ORDER_ID']);
                    $sheet->setCellValue('C' . $index, $row['RESULT'] ? '+' : '-');

                    $index++;
                }

                $xlsWriter = new PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);

                $fileName = __DIR__ . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . uniqid();

                $xlsWriter->save($fileName);

                if (file_exists($fileName)) {
                    // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
                    // если этого не сделать файл будет читаться в память полностью!
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    // заставляем браузер показать окно сохранения файла
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . basename($fileName) . '.xls');
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($fileName));
                    // читаем файл и отправляем его пользователю
                    readfile($fileName);
                    unlink($fileName);
                    exit;
                }
            }

            $this->arResult['RESULT'] = $result;
        }

        $this->initComponentTemplate();
        $this->showComponentTemplate();
	}
}