<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use PhpOffice\PhpSpreadsheet\Spreadsheet,
    Bitrix\Sale\Order,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Fuser,
    Bitrix\Main\ErrorCollection,
    Bitrix\Main\Error;

/**
 * Компонент служит для создания заказа путем загрузки xls-файла в личный кабинет
 * Class ExcelOrder
 */
class ExcelOrder extends CBitrixComponent
{

    /** @var ErrorCollection */
    protected $errorCollection;

    public function onPrepareComponentParams($arParams)
    {
        $this->errorCollection = new ErrorCollection();
    }

    public function executeComponent()
    {

        global $APPLICATION;
        global $USER;

        if ($_REQUEST['IS_AJAX'] === 'Y') {
            $APPLICATION->RestartBuffer();
            if (isset($_FILES['excel_file']['tmp_name']) &&  0 === $_FILES['excel_file']['error']) {

                $productArray = [];
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

                try {
                    $spreadsheet = $reader->load($_FILES['excel_file']['tmp_name']);
                } catch (\Exception $e) {
                    ShowError('Не удалось прочитать таблицу.');

                    die();
                }

                $activeSheetArray = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                foreach ($activeSheetArray as $rowNumber => $sheetRow) {
                    if ($rowNumber === 1) {
                        foreach ($sheetRow as $column) {
                            if (is_string($column)) {
                                $rows = array_slice($spreadsheet->getActiveSheet()->toArray(null, true, true, true), 1);
                            } else {
                                $rows = $activeSheetArray;
                            }
                            break;
                        }
                    }
                }
                foreach ($rows as $row) {
                    if ($row['A'] && $row['B']) {
                        if (!$productArray[$row['A']]) {
                            $productArray[$row['A']] = (int)$row['B'];
                        } else {
                            $productArray[$row['A']] += (int)$row['B'];
                        }
                    }
                }
                if ($productArray) {
                    $basket = Basket::loadItemsForFUser(Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
                    if (!$basket->getBasketItems()) {
                        $basket = Basket::create(SITE_ID);
                    }
                    foreach ($productArray as $productVendorCode => $quantity) {
                        $productId = CIBlockElement::GetList(
                            [],
                            [
                                'IBLOCK_CODE'           => 'catalog_main',
                                'PROPERTY_CML2_ARTICLE' => trim($productVendorCode),
                                'ACTIVE' => 'Y'
                            ],
                            false,
                            false,
                            [
                                'IBLOCK_ID',
                                'ID'
                            ])->Fetch()['ID'];
                        if ($productId) {
                            if ($item = $basket->getExistsItem('catalog', $productId)) {
                                $item->setField('QUANTITY', $item->getQuantity() + $quantity);
                            } else {
                                $r = Bitrix\Catalog\Product\Basket::addProduct([
                                    'PRODUCT_ID'             => $productId,
                                    'QUANTITY'               => $quantity,
                                    'CURRENCY'               => Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                                    'LID'                    => Bitrix\Main\Context::getCurrent()->getSite(),
                                    'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
                                ]);
                                if (!$r->isSuccess()) {
                                    foreach ($r->getErrorMessages() as $error) {
                                        ShowError('Артикул ' . $productVendorCode . ' ошибка: ' . $error);
                                    }
                                } else {
                                    ShowMessage(['TYPE' => 'OK', 'MESSAGE' => 'Товар с артикулом ' . $productVendorCode . ' добавлен в корзину']);
                                }
                            }
                        } else {
                            ShowError('Товар с артикулом ' . $productVendorCode . ' не найден');
                        }
                    }
                } else {
                    ShowError('Неверный формат файла');
                }
            }
            die();
        }

        $this->includeComponentTemplate();
    }
}