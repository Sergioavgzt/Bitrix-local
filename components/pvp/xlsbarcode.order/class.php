<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Currency\CurrencyManager;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
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
class XlsBarcodeOrder extends CBitrixComponent
{

    /** @var ErrorCollection */
    //protected $errorCollection;

    public function onPrepareComponentParams($arParams)
    {
        //$this->errorCollection = new ErrorCollection();
    }

    public function executeComponent()
    {
        $application = \Bitrix\Main\Application::getInstance();
        $request = $application->getContext()->getRequest();
        $session = $application->getSession();

        $meesageKey = 'pvp.xlsbarcode.order.messages';

        if ($request->isPost() && $file = $request->getFile('excel_file')) {
            $reader = new Xls();

            try {
                $spreadsheet = $reader->load($file['tmp_name']);
            } catch(\Exception $e) {
                $session->set($meesageKey, [['TYPE' => 'ERROR', 'MESSAGE' => 'Неверный тип файла!']]);
            }
        }

        if (isset($spreadsheet)) {
            $sheet = $spreadsheet->getActiveSheet();
            $priceProducts = $sheet->toArray();
            //remove titles
            unset($priceProducts[0]);

            $priceProducts = array_column($priceProducts, null, 1);

            $dbItems = CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => 26, 'PROPERTY_SHTRIKHKOD' => array_keys($priceProducts), 'ACTIVE' => 'Y'],
                false,
                false,
                ['ID', 'NAME', 'PROPERTY_SHTRIKHKOD']
            );

            $products = [];
            while ($res = $dbItems->GetNext()) {
                $products[$res['PROPERTY_SHTRIKHKOD_VALUE']] = $res;
            }

            $flashMessages = [];

            foreach (array_keys($priceProducts) as $barcode) {
                if (empty($products[$barcode])) {
                    $flashMessages[] = ['TYPE' => 'ERROR', 'MESSAGE' => $barcode . ': товар не найден.'];
                }
            }

            foreach ($products as $barcode => $product) {
                $result = Bitrix\Catalog\Product\Basket::addProduct([
                    'PRODUCT_ID' => $product['ID'],
                    'QUANTITY' => $priceProducts[$barcode][2],
                    'CURRENCY' => CurrencyManager::getBaseCurrency(),
                    'LID' => SITE_ID,
                    'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
                ]);

                if ($result->isSuccess()) {
                    $flashMessages[] = ['TYPE' => 'OK', 'MESSAGE' => 'Товар ' . $barcode . '(' . $product['NAME'] . '): ' . ' добавлен в корзину'];
                } else {
                    foreach ($result->getErrors() as $error) {
                        $flashMessages[] = ['TYPE' => 'ERROR', 'MESSAGE' => $barcode . '(' . $product['NAME'] . '): ' . $error->getMessage()];
                    }
                }
            }

            $session->set($meesageKey, $flashMessages);
            header('Location:' . $request->getRequestUri());
            die();
        }

        if ($session->has($meesageKey)) {
            foreach ($session->get($meesageKey) as $message) {
                if ('OK' == $message['TYPE']) {
                    ShowMessage($message);
                } else {
                    ShowError($message['MESSAGE']);
                }
            }

            $session->remove($meesageKey);
        }

        $this->includeComponentTemplate();
    }
}