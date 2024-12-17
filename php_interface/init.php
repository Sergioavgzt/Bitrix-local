<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/*
ЛОГИРВОАНИЕ ОБМЕНА
*/

// Определяем файл для логирования
define('LOG_FILE', $_SERVER['DOCUMENT_ROOT'] . '/exchange_log1.txt');

// Функция для записи сообщений в лог
function logExchange($message) {
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}

// Обработчик начала обмена
AddEventHandler('catalog', 'OnBeforeCatalogImport1C', function() {
    logExchange("Начало обмена с 1С.");
});

// Обработчик успешного импорта
AddEventHandler('catalog', 'OnSuccessCatalogImport1C', function() {
    logExchange("Импорт из 1С завершен успешно.");
});

// Обработчик ошибок импорта
AddEventHandler('catalog', 'OnErrorCatalogImport1C', function($error) {
    logExchange("Ошибка импорта из 1С: " . $error);
});

// Логирование каждого товара при добавлении
AddEventHandler('catalog', 'OnAfterCatalogImport1C', function($itemId) {
    logExchange("Товар с ID $itemId был добавлен.");
});

// Логирование дополнительных разделов
AddEventHandler('catalog', 'OnAfterAddSection', function($sectionId, $itemId) {
    logExchange("Дополнительный раздел добавлен к товару с ID $itemId: раздел ID $sectionId.");
});

// Логирование попыток добавления дополнительных разделов
function logFailedSectionAddition($itemName, $reason) {
    logExchange("Не удалось добавить дополнительные разделы товару '$itemName' по причине: $reason.");
}

// Пример использования функции для логирования неудачных попыток
// Вызывайте эту функцию в соответствующих местах вашего кода, где происходит добавление разделов
// Например:
logFailedSectionAddition("Пена монтажная ПОЛИТЕХ PRO 750 мл", "ERROR, Unauthorized");

// Логирование завершения обмена
AddEventHandler('catalog', 'OnEndCatalogImport1C', function() {
    logExchange("Завершение обмена с 1С.");
});







if (! defined('LOG_FILENAME')) {
    define('LOG_FILENAME', dirname(__DIR__) . '/logs/bx.log');
}

if (! defined('REGISTER_INFO_LINK')) {
    define('REGISTER_INFO_LINK', '/register/');
}

if (! defined('BX_SECURITY_SKIP_FRAMECHECK')) {
    define('BX_SECURITY_SKIP_FRAMECHECK', true);
}

if (! function_exists('dtf')) {
    function dtf($var)
    {
        $f = fopen(LOG_FILENAME . '.dtf.log', 'a+');
        fwrite($f, var_export($var, true) . PHP_EOL . PHP_EOL);
        fclose($f);
    }
}

if (! function_exists('dd')) {
    function dd($var, $die = true)
    {
        static $firstUse = true;

        if ($firstUse) {
            $firstUse = false;
        }

        if ($firstUse && $die) {
            ob_end_clean();
        }

        echo '<pre>';
        var_dump($var);
        echo '</pre><br>';
        if ($die) die();
    }
}

if (! function_exists('NumberWordEndingsEx')) {
    function NumberWordEndingsEx(int $num, array $arEnds = ['ов', 'ов', '', 'а']) {
        $lang = LANGUAGE_ID;

        if ($lang == 'ru') {
            if (strlen($num) > 1 && substr($num, strlen($num) - 2, 1) == '1') {
                return $arEnds[0];
            } else {
                $c = IntVal(substr($num, strlen($num) - 1, 1));

                if ($c == 0 || ($c >= 5 && $c <= 9)) {
                    return $arEnds[1];
                } elseif ($c == 1) {
                    return $arEnds[2];
                } else {
                    return $arEnds[3];
                }
            }
        } elseif ($lang == 'en') {
            if (IntVal($num) > 1) {
                return 's';
            }
            return '';
        } else {
            return '';
        }
    }
}

\Bitrix\Main\Loader::includeModule('iblock');

if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php'))
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php';

COption::SetOptionString("catalog", "DEFAULT_SKIP_SOURCE_CHECK", "Y");
COption::SetOptionString("sale", "secure_1c_exchange", "N");


use Bitrix\Main;
Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    'ipAddressToOrder'
);

function ipAddressToOrder(Main\Event $event)
{
    /** @var \Bitrix\Sale\Order $order */
    $order = $event->getParameter("ENTITY");
    $isNew = $event->getParameter("IS_NEW");

    if ($isNew) {
        foreach ($order->getPropertyCollection() as $property) {
            if ('ORDER_IP_ADDRESS' == $property->getField('CODE')) {
                $property->setValue($_SERVER['REMOTE_ADDR']);
                $property->save();

                break;
            }
        }
    }
}


//-- Добавление в почтовый шаблон заказ доп. инфы
AddEventHandler("sale", "OnOrderNewSendEmail", "bxModifySaleMails");
function bxModifySaleMails($orderID, &$eventName, &$arFields)
{
  $arOrder = CSaleOrder::GetByID($orderID);
  
  //-- получаем телефоны и адрес
  $order_props = CSaleOrderPropsValue::GetOrderProps($orderID);
  $phone="";
  $index = ""; 
  $country_name = "";
  $city_name = "";  
  $address = "";
  while ($arProps = $order_props->Fetch())
  {
    if ($arProps["CODE"] == "PHONE")
    {
       $phone = htmlspecialchars($arProps["VALUE"]);
    }
    if ($arProps["CODE"] == "LOCATION")
    {
        $arLocs = CSaleLocation::GetByID($arProps["VALUE"]);
        $country_name =  $arLocs["COUNTRY_NAME_ORIG"];
        $city_name = $arLocs["CITY_NAME_ORIG"];
    }

    if ($arProps["CODE"] == "INDEX")
    {
      $index = $arProps["VALUE"];   
    }

    if ($arProps["CODE"] == "ADDRESS")
    {
      $address = $arProps["VALUE"];
    }
  }

  $full_address = $index.", ".$country_name."-".$city_name.", ".$address;

  //-- получаем название службы доставки
  $arDeliv = CSaleDelivery::GetByID($arOrder["DELIVERY_ID"]);
  $delivery_name = "";
  if ($arDeliv)
  {
    $delivery_name = $arDeliv["NAME"];
  }

  //-- получаем название платежной системы   
  $arPaySystem = CSalePaySystem::GetByID($arOrder["PAY_SYSTEM_ID"]);
  $pay_system_name = "";
  if ($arPaySystem)
  {
    $pay_system_name = $arPaySystem["NAME"];
  }

  //-- добавляем новые поля в массив результатов
  $arFields["ORDER_DESCRIPTION"] = $arOrder["USER_DESCRIPTION"]; 
  $arFields["PHONE"] =  $phone;
  $arFields["DELIVERY_NAME"] =  $delivery_name;
  $arFields["PAY_SYSTEM_NAME"] =  $pay_system_name;
  $arFields["FULL_ADDRESS"] = $full_address;   
}

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/OnRegisterClass.php'); //События при регистрации pobedit

AddEventHandler("main", "OnEpilog", "SetSeoData");
    //get_site_param('arrSortBy');
    //get_site_param('arrPerPage');
    //get_site_param('PerPageDefault');

    function get_site_param($code) {
        switch ($code) {
            
            case 'arrSortBy':  //данные для сортировки
                return array (
                    1=> array( 'value'=>1, 'capt'=>'по возрастанию кода', 'field'=>'PROPERTY_KOD_1', 'order'=>'asc'),
                    2=> array( 'value'=>2, 'capt'=>'по убыванию кода', 'field'=>'PROPERTY_KOD_1', 'order'=>'desc'),
                    3=> array( 'value'=>3, 'capt'=>'по возрастанию цены', 'field'=>'catalog_PRICE_2', 'order'=>'asc'),  //
                    4=> array( 'value'=>4, 'capt'=>'по убыванию цены', 'field'=>'catalog_PRICE_2', 'order'=>'desc'),
                );
                break;
                
            case 'arrPerPage':  //список кол-ва товаров на странице на панели сортировки
                return array ( 12,20,40,80 );
                break;
                
            case 'PerPageDefault':  //начальное кол-во товаров на странице (при первом входе юзера)
                return 20; //neet to 20
                break;
                
            //юзается при оформлении заказа
            case 'TovBlockID': //id- блока товаров 
                return 18; break;

            case 'TovBlockID2': //id- блока товаров 
                return 23; break;
            
            case 'id_DOSTAVKA':  //id-в службе доставки (для определения цены, если указан адрес доставки) 
                return 1; break;
            case 'id_SAMOVYVOZ': //id-в службе доставки (для определения цены, если указан самовывоз)
                return 2; break;

            default:
                break; 
        }
        return false;
    }

//глобальные переменные:
// $_SESSION['favorites'] -- список ид-товаров в закладках
// $_SESSION['compare_list'] -- список ид-товаров в сравнении

    function get_site_values($code) {
        switch ($code) {
            case 'compare_list':
                $items = array();
                if ( isset( $_SESSION['CATALOG_COMPARE_LIST']) && 
                     isset( $_SESSION['CATALOG_COMPARE_LIST'][18]) &&
                     isset( $_SESSION['CATALOG_COMPARE_LIST'][18]['ITEMS']) )  
                {
                    foreach ($_SESSION['CATALOG_COMPARE_LIST'][18]["ITEMS"] as $key=>$itm)
                        $items[$key] = $key;   
                }
                return $items;
                break;
        }
    }
    
    function is_file_in_str($str) {
        if (is_array($str)) return false;
        $imgs_exts = array( '.jpg', '.png', '.gif', '.pdf', '.doc' );
        foreach ($imgs_exts as $ext) 
            if ( strpos($str, $ext)!==false) 
                return true;
        return false;
    }
    
    //кол-во и суммарная цена товаров в корзине
    function getBacketInfo()
    {
        if (CModule::IncludeModule("sale") && CModule::IncludeModule("catalog")) 
        {        
          $price = 0;
          $num = 0;  
        
          $dbBasketItems = CSaleBasket::GetList(false, array("FUSER_ID" => CSaleBasket::GetBasketUserID(), 
                                                "LID" => SITE_ID, "ORDER_ID" => "NULL", "DELAY" => "N"), 
                                                false, false, array("ID","QUANTITY", "PRICE"));
            $prices = array();                                    
            $nums = array();                                    
          while ($arItems = $dbBasketItems->Fetch()) {
             $price += $arItems['PRICE']*$arItems['QUANTITY'];
             $prices[] = $arItems['PRICE'];
             $num += $arItems['QUANTITY'];
             $nums[] = $arItems['QUANTITY'];
          }
             
          return array( 
            'price_formated' => number_format($price, 0, '.', ' ').' руб.',
            'num' => $num,
            'price' => $price,
            'prices' => $prices,
            'nums' => $nums
          );
        }
        return false;  
    }    
    


    function nfGetCurPageParam( $strParam = '', $arParamKill = array(), $get_index_page = NULL ){
    
       if( NULL === $get_index_page ){
    
          if( defined( 'BX_DISABLE_INDEX_PAGE' ) )
             $get_index_page = !BX_DISABLE_INDEX_PAGE;
          else
             $get_index_page = TRUE;
    
       }
    
       $sUrlPath = GetPagePath( FALSE, $get_index_page );
       $strNavQueryString = nfDeleteParam( $arParamKill );
    
       if( $strNavQueryString <> '' && $strParam <> '' )
          $strNavQueryString = '&'.$strNavQueryString;
    
       if( $strNavQueryString == '' && $strParam == '' )
          return $sUrlPath;
       else
          return $sUrlPath.'?'.$strParam.$strNavQueryString;
          
    }
    
    
    function nfDeleteParam( $arParam ){
    
       if( sizeof( $_GET ) < 1 )
          return '';
    
       if( sizeof( $arParam ) < 1 )
          return '';
    
       $get = $_GET;
    
       foreach( $arParam as $param ){
    
          $search    = &$get;
          $param     = (array)$param;
          $lastIndex = sizeof( $param ) - 1;
    
          foreach( $param as $c => $key ){
    
             if( array_key_exists( $key, $search ) ){
    
                if( $c == $lastIndex )
                   unset( $search[$key] );
                else
                   $search = &$search[$key];
    
             }
    
          }
    
       }
    
       return str_replace(
          array( '%5B', '%5D' ),
          array( '[', ']' ),
          http_build_query( $get )
       );
    
    } 



// Выполняет: проверку checkbox
function checkbox_verify($_name)
{
    // обязательно прописываем, чтобы функция всегда возвращала результат
    $result=0;
    // проверяем, а есть ли вообще такой checkbox на HTML форме, а то часто промахиваются
    if (isset($_REQUEST[$_name])) {  
        if ($_REQUEST[$_name]=='on') {$result=1;}
    }
    return $result;
}


function get_request($field, $def='') {
   return ( isset($_REQUEST[$field]) && (!empty($_REQUEST[$field]) ) )  ? $_REQUEST[$field] : $def; 
}
function get_request2($fields, $def='') {
   $f = explode(',',$fields); 
   return isset($_REQUEST[$f[0]][$f[1]]) ? $_REQUEST[$f[0]][$f[1]] : $def; 
}

function str_plus ($str, $val) {
    if ($str!='') $str .= ', ';
    $str .= $val;
    return $str;
}
//создание строки адреса   
// adr_values: [index], [region], [raion],[city], [street], [house], [corpus], [flat]
function create_adres_string( $adr_values, $kv_of='кв '  ) {
   $str = '';
   if ( !empty($adr_values[0]) ) $str = 'Индекс '.$adr_values[0];         
   if ( !empty($adr_values[1]) ) $str = str_plus($str, $adr_values[1]);         
   if ( !empty($adr_values[2]) ) $str = str_plus($str, $adr_values[2]);         
   if ( !empty($adr_values[3]) ) $str = str_plus($str, $adr_values[3]);         
   if ( !empty($adr_values[4]) ) $str = str_plus($str, $adr_values[4]);         
   if ( !empty($adr_values[5]) ) $str = str_plus($str, $adr_values[5]);         
   if ( !empty($adr_values[6]) ) $str = str_plus($str, 'корп '.$adr_values[6]);         
   if ( !empty($adr_values[7]) ) $str = str_plus($str, $kv_of.$adr_values[7]);
   return $str;         
}

/*
//AddOrderUserProperty('FIO', get_request('fio'), $upi);
function AddOrderUserProperty($code, $value, $order, $type_id=false) {
      if (!strlen($code)) 
         return false;
      $filter = array('CODE' => $code);
      if ($type_id) 
        $filter['PERSON_TYPE_ID'] = $type_id;
        
      if (CModule::IncludeModule('sale')) 
         if ($arProp = CSaleOrderProps::GetList(array(), $filter )->Fetch()) {
            return CSaleOrderPropsValue::Add(array(
               'NAME' => $arProp['NAME'],
               'CODE' => $arProp['CODE'],
               'ORDER_PROPS_ID' => $arProp['ID'],
               'ORDER_ID' => $order,
               'VALUE' => $value,
            ));
         }
}
*/

//добавляет свойство (код/значение) к заказу, динамически узнавая идентификатор свойства ORDER_PROPS_ID
function AddOrderProperty($code, $value, $order, $type_id=false) {
      if (!strlen($code)) 
         return false;
      $filter = array('CODE' => $code);
      if ($type_id) 
        $filter['PERSON_TYPE_ID'] = $type_id;
        
      if (CModule::IncludeModule('sale')) 
         if ($arProp = CSaleOrderProps::GetList(array(), $filter )->Fetch()) {
            return CSaleOrderPropsValue::Add(array(
               'NAME' => $arProp['NAME'],
               'CODE' => $arProp['CODE'],
               'ORDER_PROPS_ID' => $arProp['ID'],
               'ORDER_ID' => $order,
               'VALUE' => $value,
            ));
         }
}

//добавляет или изменяет свойство (если оно уже есть) к заказу, динамически узнавая идентификатор свойства ORDER_PROPS_ID
function UpdateOrderProperty($code, $value, $order) {
    if (!strlen($code))  return false;
    if (!CModule::IncludeModule('sale')) return false; 
    $filter = array('CODE' => $code, 'ORDER_ID' => $order);
        
    //PR('TRY UPDATE');
    //если ли такое свойство в списке свойств заказа (со значением)
    $aPV = CSaleOrderPropsValue::GetList(array(), $filter);
    if ($arPV = $aPV->Fetch()) {
        //PR($arPV);
        return CSaleOrderPropsValue::Update($arPV['ID'], array( 'VALUE' => $value ));
    } else {
        $filter = array('CODE' => $code);
        //есть ли свойство с таким кодом в образе профиля        
        if ($arProp = CSaleOrderProps::GetList(array(), $filter )->Fetch()) {
            $elm = CSaleOrderPropsValue::Add(array(
                   'NAME' => $arProp['NAME'],
                   'CODE' => $arProp['CODE'],
                   'ORDER_PROPS_ID' => $arProp['ID'],
                   'ORDER_ID' => $order,
                   'VALUE' => $value,
                 ) ); 
            return $elm;
        }
    }
}

//преобразует дату в формат: dd mmm yyyy, где mmm - 3буквы месяца
function rus_date($date) { 
    $months_names = array( 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек' );
    if ($arr = ParseDateTime($date, "DD.MM.YYYY HH:MI:SS"))
    {
        return $arr['DD'].' '.$months_names[(int)$arr['MM']].' '.$arr['YYYY'];
    }
    return 'дата-nvl';
}

function SetSeoData()
{
    // TODO: перхватить и сбросить любой вывод

    global $APPLICATION;
    $dir = $APPLICATION->GetCurDir();
    $uri = $APPLICATION->GetCurUri();
    // $title = CMain::GetTitle();
    $title = $APPLICATION->GetTitle();
    $m_title = $APPLICATION->GetProperty("title");
    $m_descr = $APPLICATION->GetProperty("description");

    //print_r($APPLICATION->arAdditionalChain);
    $bread = $APPLICATION->arAdditionalChain;
    $bread_end = end($bread);
    $bread_end = $bread_end['TITLE'];


   // print_r($title.' 123');

    //$APPLICATION->SetTitle();

    $aSEOData['title'] = '';
    $aSEOData['descr'] = '';
    $aSEOData['keywr'] = '';
    $aSEOData['h1'] = '';

    if(preg_match('{/sales/(.*)}', $uri)){
      $aSEOData['title'] = $title.' | Акции интернет-магазина «Джиллион»';
      $aSEOData['descr'] = $title.'. Акции интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
      $aSEOData['keywr'] = $title.' акции интернет магазин джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
    }

    if(preg_match('{/news/(.*)}', $uri)){
      $aSEOData['title'] = $title.' | Новости интернет-магазина «Джиллион»';
      $aSEOData['descr'] = $title.'. Новости интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
      $aSEOData['keywr'] = $title.' акции интернет магазин джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
    }

    if(preg_match('{/articles/(.*)}', $uri)){
      $aSEOData['title'] = $title.' | Статьи интернет-магазина «Джиллион»';
      $aSEOData['descr'] = $title.'. Статьи интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
      $aSEOData['keywr'] = $title.' статьи интернет магазин джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
    }
    if(preg_match('{/catalog/avtotovary/kovriki_avtomobilnye/(.*)}', $uri)){
      $aSEOData['h1'] = 'Автомобильные коврики '.$title;
    }



    $not_gen = array(
      '/catalog/elektroinstrument/akkumulyatornye_dreli_shurupoverty/komplekty_instrumentov/',
      '/catalog/elektroinstrument/gaykoverty/akkumulyatornye_5/',
      '/catalog/elektroinstrument/izmeritelnyy_instrument_1/dalnomery/',
      '/catalog/elektroinstrument/izmeritelnyy_instrument_1/detektory/',
      '/catalog/elektroinstrument/pylesosy/akkumulyatornye_pylesosy/',
      '/catalog/elektroinstrument/pylesosy/ruchnye_pylesosy/',
      '/catalog/elektroinstrument/sabelnye_pily/pily_alligator/',
      '/catalog/elektroinstrument/stanki/verstaki/',
      '/catalog/elektroinstrument/stanki/zatochnye_stanki/',
      '/catalog/elektroinstrument/stanki/kombinirovannye_pily/',
      '/catalog/elektroinstrument/stanki/lentochnye_pily/',
      '/catalog/elektroinstrument/stanki/nastolnye_pily/',
      '/catalog/elektroinstrument/stanki/nastolnye_shlifovalnye_stanki/',
      '/catalog/elektroinstrument/stanki/otreznye_pily_po_metallu/',
      '/catalog/elektroinstrument/stanki/radialno_konsolnye_pily/',
      '/catalog/elektroinstrument/stanki/struzhkootsos/',
      '/catalog/elektroinstrument/stanki/tortsovochnye_pily/',
      '/catalog/elektroinstrument/stanki/fugovalno_reysusovye_stanki/',
      '/catalog/elektroinstrument/fonari/akkumulyatornye_fonari/',
      '/catalog/elektroinstrument/shlifovalnye_mashinki/universalnye_shlifmashiny/',
      '/catalog/elektroinstrument/elektricheskie_dreli_i_shurupoverty/shurupoverty/',
      '/catalog/elektroinstrument/elektrosteplery/akkumulyatornye_steplery_i_neylery/',
      '/catalog/elektroinstrument/elektrosteplery/skobozabivateli/',
      '/catalog/vsye_dlya_sada/tekhnika/gazonokosilki/gazonokosilki_roboty/',
      '/catalog/vsye_dlya_sada/tekhnika/dvigateli/benzinovye_dvigateli/',
      '/catalog/vsye_dlya_sada/tekhnika/kustorezy/akkumulyatornye_nozhnitsy/',
     // '/catalog/vsye_dlya_sada/tekhnika/motopompy/chistaya_voda/',
      '/catalog/vsye_dlya_sada/tekhnika/nasosy/nasosnye_stantsii/',
      '/catalog/vsye_dlya_sada/tekhnika/sadovye_traktory/raydery/',
      '/catalog/elektroinstrument/elektricheskie_dreli_i_shurupoverty/makita/',
      '/catalog/elektroinstrument/elektricheskie_dreli_i_shurupoverty/skil/',
      '/catalog/elektroinstrument/elektricheskie_dreli_i_shurupoverty/dewalt/',
      '/catalog/elektroinstrument/elektricheskie_dreli_i_shurupoverty/interskol/',
      '/catalog/elektroinstrument/elektricheskie_dreli_i_shurupoverty/hyundai/',
      '/catalog/vsye_dlya_sada/tekhnika/kustorezy/elektricheskie_5/',
      '/catalog/vsye_dlya_sada/tekhnika/kustorezy/benzinovye_4/',
      '/catalog/vsye_dlya_sada/tekhnika/kustorezy/akkumulyatornye_2/',
      '/catalog/vsye_dlya_sada/tekhnika/kustorezy/akkumulyatornye_nozhnitsy/',
      '/catalog/vsye_dlya_sada/tekhnika/trimmery/elektricheskie_8/',
      '/catalog/vsye_dlya_sada/tekhnika/opryskivateli_1/benzinovye_6/',
      '/catalog/vsye_dlya_sada/tekhnika/snegouborochnaya_tekhnika/benzinovye_7/',
      '/catalog/vsye_dlya_sada/tekhnika/snegouborochnaya_tekhnika/elektricheskie_7/',
      '/catalog/vsye_dlya_sada/tekhnika/trimmery/akkumulyatornye_3/',
      '/catalog/vsye_dlya_sada/tekhnika/trimmery/benzinovye_8/',
      '/catalog/vsye_dlya_sada/tekhnika/tsepnye_elektricheskie_pily/akkumulyatornye_4/',
      '/catalog/vsye_dlya_sada/tekhnika/tsepnye_elektricheskie_pily/elektricheskie_9/',


);
    
    if(!in_array($uri, $not_gen) && 
       !preg_match('{/catalog/vsye_dlya_sada/vse_dlya_poliva/(.*)}', $uri) &&
       !preg_match('{/catalog/vsye_dlya_sada/sadovyy_inventar/(.*)}', $uri) 
       ){
            if(count($bread)==3 && preg_match('{/catalog/elektroinstrument/(.*)}', $uri))
            {
                $m_h1=preg_replace('# в интернет-магазине(.*)с доставкой по России#siU', '', $m_title);
                $m_h1=str_replace(')', '', $m_h1);
                $m_h1=str_replace('(', '', $m_h1);
                $m_h1=str_replace('»', '', $m_h1);

                $aSEOData['h1'] = $m_h1;
            }

            if(count($bread)==4 && preg_match('{/catalog/vsye_dlya_sada/tekhnika/(.*)}', $uri))
            {
                $m_h1=preg_replace('# в интернет-магазине(.*)с доставкой по России#siU', '', $m_title);
                $m_h1=str_replace(')', '', $m_h1);
                $m_h1=str_replace('(', '', $m_h1);
                $m_h1=str_replace('»', '', $m_h1);

                $aSEOData['h1'] = $m_h1;
            }


        }

    $stat_h1 = array(
      '/catalog/elektroinstrument/uglovye_shlifmashiny_bolgarki/akkumulyatornye_11/' => 'Угловые шлифмашины (болгарки) аккумуляторные', 
      '/catalog/elektroinstrument/uglovye_shlifmashiny_bolgarki/elektricheskie_16/' => 'Угловые шлифмашины (болгарки) электрические', 
      '/catalog/elektroinstrument/frezery/lamelnye/' => 'Фрезеры ламельные', 
      '/catalog/elektroinstrument/frezery/okantovochnye_kromochnye/' => 'Фрезеры окантовочные', 
      '/catalog/silovaya_tekhnika/generatory_elektrostantsii/benzinovye/' => 'Бензиновые генераторы', 
      '/catalog/silovaya_tekhnika/generatory_elektrostantsii/gazovye/' => 'Газовые генераторы', 
      '/catalog/silovaya_tekhnika/generatory_elektrostantsii/dizelnye/' => 'Дизельные генераторы', 
      '/catalog/silovaya_tekhnika/generatory_elektrostantsii/invertornye/' => 'Инверторные генераторы', 
      '/catalog/silovaya_tekhnika/svarochnye_apparaty/invertory/' => 'Сварочные аппараты-инверторы', 
      '/catalog/klimaticheskoe_oborudovanie/teplovye_pushki/gazovye_1/' => 'Газовые тепловые пушки', 
      '/catalog/klimaticheskoe_oborudovanie/teplovye_pushki/elektricheskie/' => 'Электрические тепловые пушки',
      );
    if(isset($stat_h1[$uri]))
    {
        $aSEOData['h1'] = $stat_h1[$uri];
    }

    $rashodnie_matrial_h1 = array(
        '/catalog/raskhodnye_materialy/dlya_vozdukhoduvok/',
        '/catalog/raskhodnye_materialy/dlya_generatorov/',
        '/catalog/raskhodnye_materialy/dlya_kultivatorov/',
        '/catalog/raskhodnye_materialy/dlya_kustorezov/',
        '/catalog/raskhodnye_materialy/dlya_motoburov/',
        '/catalog/raskhodnye_materialy/dlya_opryskivateley/',
        '/catalog/raskhodnye_materialy/dlya_trimmerov/',
        '/catalog/raskhodnye_materialy/dlya_tsepnykh_pil/',
        '/catalog/raskhodnye_materialy/dlya_kompressorov/',
        '/catalog/raskhodnye_materialy/dlya_mnogofunktsionalnogo_instrumenta/',
        '/catalog/raskhodnye_materialy/dlya_perforatorov/',
        '/catalog/raskhodnye_materialy/dlya_pilnykh_i_otreznykh_rabot/',
        '/catalog/raskhodnye_materialy/dlya_rubankov/',
        '/catalog/raskhodnye_materialy/dlya_statsionarnogo_instrumenta/',
        '/catalog/raskhodnye_materialy/dlya_termofenov/',
        '/catalog/raskhodnye_materialy/dlya_ushm/',
        '/catalog/raskhodnye_materialy/dlya_elektronozhnits/',
);
    if(in_array($uri, $rashodnie_matrial_h1)){
      $bread_h1 = $bread[1];
      $bread_kategor = $bread[0];
      $aSEOData['h1'] = $bread_kategor['TITLE'].' '.strtolower($bread_h1['TITLE']);
      $aSEOData['title'] = $aSEOData['h1'].': купить по выгодным ценам в интернет-магазине «Джиллион»';
      $aSEOData['descr'] = $aSEOData['h1'].' по выгодным ценам в интернет-магазине «Джиллион». Предлагаем широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
    }




    switch ($uri)
    {
    case '/articles/': // это ЧПУ
        $aSEOData['title'] = 'Статьи и полезная информация от интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Статьи и полезная информация от компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'статьи и полезная информация от интернет магазин джиллион';
      break;

    case '/news/': // это ЧПУ
        $aSEOData['title'] = 'Новости интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Новости компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'новости компании джиллион';
      break;

    case '/about/': // это ЧПУ
        $aSEOData['title'] = 'О компании «Джиллион» | Продажа ручного и электроинструмента, садовой техники и расходных материалов';
        $aSEOData['descr'] = 'О компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'о компании джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
      break;

    case '/sales/': // это ЧПУ
        $aSEOData['title'] = 'Акции интернет-магазина «Джиллион» | Продажа ручного и электроинструмента, садовой техники и расходных материалов';
        $aSEOData['descr'] = 'Акции интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'акции интернет магазин джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
      break;

    case '/novinki/': // это ЧПУ
        $aSEOData['title'] = 'Новинки интернет-магазина «Джиллион» | Продажа ручного и электроинструмента, садовой техники и расходных материалов';
        $aSEOData['descr'] = 'Новинки интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'новинки интернет магазин джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
      break;

    case '/dostavka_oplata/': // это ЧПУ
        $aSEOData['title'] = 'Оплата и доставка товара из интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Оплата и доставка товара из интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'оплата и доставка товара от интернет магазин джиллион';
      break;

    case '/contacts/': // это ЧПУ
        $aSEOData['title'] = 'Контакты компании «Джиллион» | Продажа ручного и электроинструмента, садовой техники и расходных материалов';
        $aSEOData['descr'] = 'Контакты компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'контакты компании джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
      break;

    case '/personal/cart/': // это ЧПУ
        $aSEOData['title'] = 'Корзина | Интернет-магазин «Джиллион»';
        $aSEOData['descr'] = 'Корзина интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'корзина интернет магазина джиллион';
      break;

    case '/articles/statya_1/': // это ЧПУ
        $aSEOData['title'] = 'Сотрудники компании «Джиллион» сняли на видео испытание абразивных кругов | Статьи и полезная информация (видео)';
        $aSEOData['descr'] = 'Сотрудники компании «Джиллион» сняли на видео испытание абразивных кругов. Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'видео наши сотрудники сняли на видео испытание абразивных кругов дисков статьи и полезная информация от джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
      break;

    case '/news/novye_postupleniya_na_sayte/': // это ЧПУ
        $aSEOData['title'] = 'Новые поступления в интернет-магазин «Джиллион» | Новости';
        $aSEOData['descr'] = 'Новости: новые поступления в интернет-магазин «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'новые поступления в интернет-магазин джиллион';
      break;

    case '/news/novye_sayt_gotov/': // это ЧПУ
        $aSEOData['title'] = 'Новый сайт компании «Джиллион» готов | Новости';
        $aSEOData['descr'] = 'Новости: новый сайт компании «Джиллион» готов. Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'новости компании джиллион сайт';
      break;
    


     case '/catalog/elektroinstrument/lobziki/': // это ЧПУ
        $aSEOData['title'] = 'Электролобзики — купить по выгодным ценам в интернет-магазине «Джиллион» | Стоимость электролобзиков';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить электролобзики по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'электролобзик цена интернет магазин купить стоимость';
      break;


    case '/catalog/elektroinstrument/perforatory/': // это ЧПУ
        $aSEOData['title'] = 'Перфораторы — купить по выгодным ценам в интернет-магазине «Джиллион» | Стоимость перфораторов';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить перфораторы по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'перфоратор купить недорого цена москва стоимость магазин';
      break;


    case '/catalog/elektroinstrument/gaykoverty/': // это ЧПУ
        $aSEOData['title'] = 'Гайковерты электрические по выгодным ценам в интернет-магазине «Джиллион» | Купить сетевой гайковерт';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить электрические гайковерты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'цена гайковерт электрический купить цена сетевой интернет магазин';
      break;


    case '/catalog/elektroinstrument/gaykoverty/akkumulyatornye_5/': // это ЧПУ
        $aSEOData['title'] = 'Аккумуляторные гайковерты — купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить аккумуляторные гайковерты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'гайковерт аккумуляторный купить цена';
      break;


    case '/catalog/vsye_dlya_sada/tekhnika/tsepnye_elektricheskie_pily/': // это ЧПУ
        $aSEOData['title'] = 'Цепные электрические пилы — купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить цепные электрические пилы по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'цепная электропила цена электрическая пила купить недорого';
      break;


    case '/catalog/elektroinstrument/shtroborezy_i_borozdodely/': // это ЧПУ
        $aSEOData['title'] = 'Штроборезы и бороздоделы — купить по выгодным ценам в интернет-магазине «Джиллион» | Стоимость штроборезов и бороздоделов';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить штроборезы и бороздоделы по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'штроборез стоимость купить цена недорого бороздодел';
      break;


    case '/articles/kak_vybrat_snegouborochnuyu_mashinu/': // это ЧПУ
        $aSEOData['title'] = 'Как выбрать снегоуборочную технику для приусадебного участка? | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Как выбрать снегоуборочную технику для приусадебного участка? Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'как выбрать снегоуборочную технику для приусадебного участка статьи интернет магазин джиллион';
        $aSEOData['h1'] = 'Как выбрать снегоуборочную технику для приусадебного участка?';
      break;

    case '/articles/preimuschestva_teplovyh_pushek/': // это ЧПУ
        $aSEOData['title'] = 'Преимущества тепловых пушек | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Преимущества тепловых пушек. Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'преимущества тепловых пушек статьи интернет магазин джиллион';
        $aSEOData['h1'] = 'Преимущества тепловых пушек';
      break;

    case '/articles/osnovnye_parametry_vybora_dreli/': // это ЧПУ
        $aSEOData['title'] = 'Как выбрать дрель? | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Как выбрать дрель? Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'как выбрать дрель статьи интернет магазин джиллион';
        $aSEOData['h1'] = 'Как выбрать дрель?';
      break;

    case '/articles/chto_vhodit_v_ponyatie_ruchnoj_instrument/': // это ЧПУ
        $aSEOData['title'] = 'Что такое «ручной инструмент»? | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Что такое «ручной инструмент»? Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'что такое ручной инструмент статьи интернет магазин джиллион';
        $aSEOData['h1'] = 'Что такое «ручной инструмент»?';
      break;

    case '/articles/gde_neobhodima_vozduhoduvka/': // это ЧПУ
        $aSEOData['title'] = 'Для чего нужна воздуходувка? | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Для чего нужна воздуходувка? Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'для чего нужна воздуходувка статьи интернет магазин джиллион';
        $aSEOData['h1'] = 'Для чего нужна воздуходувка?';
      break;

      case '/news/my_uvelichivaem_assortiment/':
        $aSEOData['title'] = 'Мы расширяем ассортимент | Новости интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Мы расширяем ассортимент. Новости интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'мы расширяем ассортимент новости интернет магазин джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
      break;


    case '/articles/po_kakim_parametram_vybirat_benzopilu/': // это ЧПУ
        $aSEOData['title'] = 'Как выбирать бензопилу | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Как выбирать бензопилу. Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'как выбирать бензопилу статьи интернет магазин джиллион';
      break;


    case '/articles/drel_neobhodimyj_instrument_v_kazhdom_dome/': // это ЧПУ
        $aSEOData['title'] = 'Дрель – необходимый инструмент в каждом доме | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Дрель – необходимый инструмент в каждом доме. Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'дрель необходимый инструмент в каждом доме статьи интернет магазин джиллион';
      break;


    case '/articles/shurupovert_nezamenimyj_pomoschnik_v_dome/': // это ЧПУ
        $aSEOData['title'] = 'Шуруповерт – незаменимый помощник в доме | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'шуруповерт незаменимый помощник в доме статьи интернет магазин джиллион';
        $aSEOData['keywr'] = 'Шуруповерт – незаменимый помощник в доме. Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
      break;


    case '/articles/kak_ne_oshibitsya_s_ruchnym_instrumentom/': // это ЧПУ
        $aSEOData['title'] = 'Как не ошибиться с выбором ручного инструмента | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Как не ошибиться с выбором ручного инструмента. Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'как не ошибиться с выбором ручного инструмента статьи интернет магазин джиллион';
      break;


    case '/articles/vybiraem_vozduhoduvku/': // это ЧПУ
        $aSEOData['title'] = 'Выбираем воздуходувку | Статьи интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Выбираем воздуходувку. Интернет-магазин «Джиллион» предлагает купить строительные и садовые инструменты по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'выбираем воздуходувку статьи интернет магазин джиллион';
      break;



      case '/catalog/elektroinstrument/sabelnye_pily/pily_alligator/':
        $aSEOData['h1'] = 'Сабельные пилы «Аллигатор»';
        break;

      case '/catalog/vsye_dlya_sada/tekhnika/motopompy/chistaya_voda/':
        $aSEOData['title'] = 'Мотопомпы для чистой воды по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить мотопомпы для чистой воды по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'мотопомпа для чистой воды';
        $aSEOData['h1'] = 'Мотопомпы для чистой воды';
        break;

      case '/catalog/silovaya_tekhnika/kompressory/remennye/':
        $aSEOData['h1'] = 'Ременные компрессоры';
        break;

      case '/catalog/tovary_dlya_otdykha/kemping/':
        $aSEOData['h1'] = 'Товары для кемпинга';
        break;

    case '/catalog/avtotovary/': // это ЧПУ
        $aSEOData['title'] = 'Автотовары  по выгодным ценам в интернет-магазине «Джиллион»';
      $aSEOData['descr'] = 'Автотовары  по выгодным ценам от компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
       break;


    case '/catalog/raskhodnye_materialy/akkumulyatory/': // это ЧПУ
        $aSEOData['title'] = 'Аккумуляторы и другие расходные материалы по выгодным ценам в интернет-магазине «Джиллион»';
      $aSEOData['descr'] = 'Аккумуляторы и другие расходные материалы по выгодным ценам в интернет-магазине «Джиллион». Предлагаем широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
       break;


    case '/catalog/raskhodnye_materialy/krugi_otreznye_i_diski/': // это ЧПУ
        $aSEOData['title'] = 'Круги отрезные и диски и другие расходные материалы по выгодным ценам в интернет-магазине «Джиллион»';
      $aSEOData['descr'] = 'Круги отрезные и диски и другие расходные материалы по выгодным ценам в интернет-магазине «Джиллион». Предлагаем широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
       break;


    // case '/catalog/raskhodnye_materialy/krugi_otreznye_i_diski/diski_almaznye/': // это ЧПУ
    //     $aSEOData['title'] = 'Диски алмазные по выгодным ценам в интернет-магазине «Джиллион»';
    //   $aSEOData['descr'] = 'Диски алмазные по выгодным ценам от компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
    //    break;


    // case '/catalog/raskhodnye_materialy/krugi_otreznye_i_diski/diski_pilnye_po_derevu/': // это ЧПУ
    //     $aSEOData['title'] = 'Диски пильные по дереву по выгодным ценам в интернет-магазине «Джиллион»';
    //   $aSEOData['descr'] = 'Диски пильные по дереву по выгодным ценам от компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
    //    break;


    // case '/catalog/raskhodnye_materialy/krugi_otreznye_i_diski/krugi_otreznye_po_kamnyu/': // это ЧПУ
    //     $aSEOData['title'] = 'Круги отрезные по камню по выгодным ценам в интернет-магазине «Джиллион»';
    //   $aSEOData['descr'] = 'Круги отрезные по камню по выгодным ценам от компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
    //    break;


    // case '/catalog/raskhodnye_materialy/krugi_otreznye_i_diski/krugi_otreznye_po_metallu/': // это ЧПУ
    //     $aSEOData['title'] = 'Круги отрезные по металлу по выгодным ценам в интернет-магазине «Джиллион»';
    //   $aSEOData['descr'] = 'Круги отрезные по металлу по выгодным ценам от компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
    //    break;


    case '/catalog/raskhodnye_materialy/zaryadnye_ustroystva/': // это ЧПУ
        $aSEOData['title'] = 'Зарядные устройства и другие расходные материалы по выгодным ценам в интернет-магазине «Джиллион»';
      $aSEOData['descr'] = 'Зарядные устройства и другие расходные материалы по выгодным ценам в интернет-магазине «Джиллион». Предлагаем широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
       break;


    case '/catalog/silovaya_tekhnika/svarochnye_apparaty/': // это ЧПУ
        $aSEOData['title'] = 'Сварочные аппараты по выгодным ценам в интернет-магазине «Джиллион»';
      $aSEOData['descr'] = 'Сварочные аппараты по выгодным ценам от компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
       break;


    case '/catalog/tovary_dlya_otdykha/': // это ЧПУ
        $aSEOData['title'] = 'Товары для отдыха по выгодным ценам в интернет-магазине «Джиллион»';
      $aSEOData['descr'] = 'Товары для отдыха по выгодным ценам от компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
       break;


    case '/catalog/vsye_dlya_sada/sadovyy_inventar/opryskivateli/': // это ЧПУ
        $aSEOData['title'] = 'Ручные и ранцевые опрыскиватели по выгодным ценам в интернет-магазине «Джиллион»';
      $aSEOData['descr'] = 'Ручные и ранцевые опрыскиватели по выгодным ценам от компании «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
       break;

    case '/news/novie_punkty_vidachi/': // это ЧПУ
        $aSEOData['title'] = 'Новые пункты выдачи! | Новости интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Новые пункты выдачи! Новости интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'новые пункты выдачи новости интернет магазина джиллион';
      break;

    case '/news/vesennaya_aktsiya/': // это ЧПУ
        $aSEOData['title'] = 'Весенние скидки на садовый инвентарь и товары для полива | Новости интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Весенние скидки на садовый инвентарь и товары для полива. Новости интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'весенние скидки на садовый инвентарь и товары для полива новости интернет магазина джиллион';
      break;
      
    case '/catalog/vsye_dlya_sada/tekhnika/gazonokosilki/benzinovye_2/': // это ЧПУ
        $aSEOData['title'] = 'Бензиновые газонокосилки: купить по выгодным ценам в интернет-магазине «Джиллион» | Продажа бензогазонокосилок';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить газонокосилки бензиновые по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'газонокосилки бензиновые цена купить продажа стоимость';
      break;

    
    case '/catalog/vsye_dlya_sada/tekhnika/gazonokosilki/elektricheskie_2/': // это ЧПУ
        $aSEOData['title'] = 'Электрические газонокосилки: купить по выгодным ценам в интернет-магазине «Джиллион» | Продажа недорогих электрогазонокосилок';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить газонокосилки бензиновые по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'газонокосилки бензиновые цена купить продажа стоимость недорогие';
      break;

    
    case '/catalog/vsye_dlya_sada/tekhnika/kultivatory_i_motobloki/': // это ЧПУ
        $aSEOData['title'] = 'Культиваторы и мотоблоки: купить по выгодным ценам в интернет-магазине «Джиллион» | Продажа недорогих культиваторов';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить культиваторы и мотоблоки по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'культиватор купить цена недорогой продажа стоимость';
      break;

    
    case '/catalog/vsye_dlya_sada/tekhnika/kultivatory_i_motobloki/elektricheskie_4/': // это ЧПУ
        $aSEOData['title'] = 'Электрические культиваторы и мотоблоки: купить по выгодным ценам в интернет-магазине «Джиллион» | Продажа недорогих электрокультиваторов';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить электрические культиваторы и мотоблоки по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'культиватор электрический купить цена недорогой продажа стоимость';
      break;

    
    case '/catalog/vsye_dlya_sada/tekhnika/kultivatory_i_motobloki/benzinovye_3/': // это ЧПУ
        $aSEOData['title'] = 'Бензиновые культиваторы и мотоблоки: купить по выгодным ценам в интернет-магазине «Джиллион» | Продажа недорогих бензокультиваторов';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить бензиновые культиваторы и мотоблоки по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'культиватор бензиновый купить цена недорогой продажа стоимость';
      break;

    case '/catalog/vsye_dlya_sada/tekhnika/izmelchiteli/': // это ЧПУ
        $aSEOData['title'] = 'Садовые измельчители: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить садовые измельчители по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'садовый измельчитель купить цена интернет магазин';
      break;


    case '/catalog/vsye_dlya_sada/tekhnika/kustorezy/vysotorezy/': // это ЧПУ
        $aSEOData['title'] = 'Высоторезы: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить высоторезы по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'высоторез купить цена интернет магазин';
        $aSEOData['h1'] = 'Высоторезы';
      break;


    case '/catalog/vsye_dlya_sada/tekhnika/motopompy/': // это ЧПУ
        $aSEOData['title'] = 'Мотопомпы: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить мотопомпы по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'мотопомпа купить цена';
      break;


    case '/catalog/vsye_dlya_sada/tekhnika/motopompy/benzinovye_5/': // это ЧПУ
        $aSEOData['title'] = 'Мотопомпы бензиновые: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить мотопомпы бензиновые по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'мотопомпа бензиновая купить цена';
      break;

    case '/catalog/vsye_dlya_sada/tekhnika/motopompy/dizelnye_1/': // это ЧПУ
        $aSEOData['title'] = 'Мотопомпы дизельные: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить мотопомпы дизельные по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'мотопомпа дизельная цена купить';
      break;


    case '/catalog/vsye_dlya_sada/tekhnika/nasosy/nasosnye_stantsii/': // это ЧПУ
        $aSEOData['title'] = 'Насосные станции: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить насосные станции по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'насосная станция купить цена москва';
      break;


    case '/catalog/vsye_dlya_sada/tekhnika/nasosy/pogruzhnye/': // это ЧПУ
        $aSEOData['title'] = 'Насосы погружные: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить насосы погружные по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'насос погружной купить цена';
      break;


    case '/catalog/vsye_dlya_sada/tekhnika/nasosy/poverkhnostnye/': // это ЧПУ
        $aSEOData['title'] = 'Поверхностные насосы: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить поверхностные насосы по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'поверхностный насос купить цена в москве';
        $aSEOData['h1'] = 'Поверхностные насосы';
      break;


    case '/catalog/vsye_dlya_sada/tekhnika/nasosy/': // это ЧПУ
        $aSEOData['title'] = 'Насосы садовые: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить насосы садовые по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'садовые насосы цена продажа купить';
      break;

      case '/catalog/avtotovary/kovriki_avtomobilnye/': // это ЧПУ
        $aSEOData['title'] = 'Коврики автомобильные по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Коврики автомобильные в интернет-магазине «Джиллион». Предлагаем широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['h1'] = 'Коврики автомобильные';
      break;
      case '/catalog/lakokrasochnaya_produktsiya/': // это ЧПУ
        $aSEOData['title'] = 'Лакокрасочная продукция по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Лакокрасочная продукция в интернет-магазине «Джиллион». Предлагаем широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
      break;
      case '/catalog/svet_i_elektrika/': // это ЧПУ
        $aSEOData['title'] = 'Свет и электрика | Интернет-магазин «Джиллион»';
        $aSEOData['descr'] = 'Свет и электрика. Интернет-магазин «Джиллион» предлагает широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
      break;
      case '/catalog/raskhodnye_materialy/': // это ЧПУ
        $aSEOData['title'] = 'Расходные материалы по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Расходные материалы в интернет-магазине «Джиллион». Предлагаем широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
      break;
      case '/catalog/raskhodnye_materialy/aksessuary_dlya_moek/': // это ЧПУ
        $aSEOData['title'] = 'Аксессуары для моек и другие расходные материалы по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Аксессуары для моек и другие расходные материалы по выгодным ценам в интернет-магазине «Джиллион». Предлагаем широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
      break;

      case '/catalog/raskhodnye_materialy/dlya_benzopil/': // это ЧПУ
        $aSEOData['title'] = 'Расходные материалы для бензопил: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Расходные материалы для бензопил по выгодным ценам в интернет-магазине «Джиллион». Предлагаем широкий ассортимент ручного и электрического инструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['h1'] = 'Расходные материалы для бензопил';
      break;


    case '/catalog/vsye_dlya_sada/vse_dlya_poliva/shlangi_polivochnye/': // это ЧПУ
        $aSEOData['title'] = 'Шланги поливочные: купить по выгодным ценам  в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить шланги поливочные по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'купить поливочный шланг цена для дачи';
      break;


    case '/catalog/lakokrasochnaya_produktsiya/antiseptik/': // это ЧПУ
        $aSEOData['title'] = 'Антисептики для древесины в интернет-магазине «Джиллион» | Купить антисептики для дерева по выгодным ценам';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить антисептики для древесины по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'антисептик для древесины дерева цена купить наружных работ грунтовочный';
        $aSEOData['h1'] = 'Антисептики для древесины';
      break;


    case '/catalog/lakokrasochnaya_produktsiya/grunt/': // это ЧПУ
        $aSEOData['title'] = 'Грунтовки для внутренних и наружных работ в интернет-магазине «Джиллион» | Купить грунтовки по выгодным ценам';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить грунтовки для внутренних и наружных работ по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'грунтовка цена купить для дерева наружная универсальная глубокой пола обоев внутренних работ';
        $aSEOData['h1'] = 'Грунтовки';
      break;


    case '/catalog/lakokrasochnaya_produktsiya/kraska/': // это ЧПУ
        $aSEOData['title'] = 'Краска влагостойкая в интернет-магазине «Джиллион» | Купить влагостойкую моющуюся краску по выгодным ценам';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить краску влагостойкую по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'краска влагостойкая для ванной стен купить фасадная цена наружная моющаяся';
        $aSEOData['h1'] = 'Краска влагостойкая';
      break;


    case '/catalog/lakokrasochnaya_produktsiya/lak/': // это ЧПУ
        $aSEOData['title'] = 'Лак в интернет-магазине «Джиллион» | Купить универсальный лак по выгодной цене';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить лак по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'купить лак для деревянного пола паркетных цена универсальный';
      break;


    case '/catalog/lakokrasochnaya_produktsiya/rastvoriteli/': // это ЧПУ
        $aSEOData['title'] = 'Растворители для краски, лаков: купить по выгодным ценам в интернет-магазине «Джиллион»';
        $aSEOData['descr'] = 'Интернет-магазин «Джиллион» предлагает купить растворители для краски, лаков по выгодным ценам. У нас широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'растворитель купить цена для краски лаков';
      break;



    }

    switch ($dir) {
      case '/news/my_uvelichivaem_assortiment/':
        $aSEOData['title'] = 'Мы расширяем ассортимент | Новости интернет-магазина «Джиллион»';
        $aSEOData['descr'] = 'Мы расширяем ассортимент. Новости интернет-магазина «Джиллион». Предлагаем широкий ассортимент ручного и электроинструмента, садовой техники и расходных материалов высокого качества. Быстрое и качественное обслуживание, гибкая ценовая политика. Доставка во все регионы России, возможен самовывоз.';
        $aSEOData['keywr'] = 'мы расширяем ассортимент новости интернет магазин джиллион продажа ручного и электроинструмента садовой техники и расходных материалов';
      break;  
    }

    // Постраничный вывод
    if(isset($_GET['PAGEN_1']) )
    {
        if(!empty($aSEOData['title'])) $aSEOData['title'] .= ' | Страница '.$_GET['PAGEN_1'];
            else {
              $aSEOData['title'] = $m_title.' | Страница '.$_GET['PAGEN_1'];
            }

        if(!empty($aSEOData['descr'])) $aSEOData['descr'] .= ' | Страница '.$_GET['PAGEN_1'];
            else {
              $aSEOData['descr'] = $m_descr.' Страница '.$_GET['PAGEN_1'];
            }
    }

    

    if(isset($_GET['PAGEN_2']))
    {
        if(!empty($aSEOData['title'])) $aSEOData['title'] .= ' | Страница '.$_GET['PAGEN_2'];
            else {
             $aSEOData['title'] = $m_title.' | Страница '.$_GET['PAGEN_2'];
            }

        if(!empty($aSEOData['descr'])) $aSEOData['descr'] .= ' | Страница '.$_GET['PAGEN_2'];
            else {
              $aSEOData['descr'] = $m_descr.' Страница '.$_GET['PAGEN_2'];
            }    
    }

    if(isset($_GET['PAGEN_3']))
    {
        if(!empty($aSEOData['title'])) $aSEOData['title'] .= ' | Страница '.$_GET['PAGEN_3'];
            else {
              $aSEOData['title'] = $m_title.' | Страница '.$_GET['PAGEN_3'];
            }

        if(!empty($aSEOData['descr'])) $aSEOData['descr'] .= ' | Страница '.$_GET['PAGEN_3'];
            else {
              $aSEOData['descr'] = $m_descr.' Страница '.$_GET['PAGEN_3'];
            }  
    }

    if($uri == '/sitemap/?PAGEN_1=1'){
      $aSEOData['title'] ='Карта сайта | Страница 2';
    }

    if($uri == '/sitemap/?PAGEN_1=2'){
      $aSEOData['title'] ='Карта сайта | Страница 3';
    }

    // Установка новых значений
   /*if(!empty($aSEOData['title'])) $APPLICATION->SetPageProperty('title', $aSEOData['title']);
    if(!empty($aSEOData['descr'])) $APPLICATION->SetPageProperty('description', $aSEOData['descr']);
    if(!empty($aSEOData['keywr'])) $APPLICATION->SetPageProperty('keywords', mb_strtolower($aSEOData['keywr']));
    if(!empty($aSEOData['h1']))    $APPLICATION->SetTitle($aSEOData['h1']);*/
}

//проверка работы отправки писем из bitrix
function custom_mail($to,$subject,$body,$headers) {
    $f=fopen($_SERVER["DOCUMENT_ROOT"]."/maillog.txt", "a+");
    fwrite($f, print_r(array('TO' => $to, 'SUBJECT' => $subject, 'BODY' => $body, 'HEADERS' => $headers),1)."\n========\n");
    fclose($f);

    #return mail($to,$subject,$body,$headers);
//    $to .= ',ulyanov@100up.ru';
    return mail($to,$subject,$body,$headers);
}

// fix UF_PHONE_VALID to "always ok" - never ask for input phone
AddEventHandler("main", "OnBeforeUserUpdate", "OnBeforeUserUpdateHandlerFixUF", 10000);
function OnBeforeUserUpdateHandlerFixUF(&$arFields){
    $arFields["UF_PHONE_VALID"] = 1;
}
CModule::IncludeModule("sale");
if($_GET["delete"] == "all"){
    CSaleBasket::DeleteAll(CSaleBasket::GetBasketUserID());
}

AddEventHandler("main", "OnBeforeUserRegister", Array("MyClass", "OnBeforeUserRegisterHandler"));class MyClass
{
   function OnBeforeUserRegisterHandler(&$arFields)
    {
          $arFields["ACTIVE"] = "N";
           
    }
}
 
//добавление в админке ссылки на кастомную страницу списка заказов
AddEventHandler("main", "OnBuildGlobalMenu", "extraMenu");
function extraMenu(&$adminMenu, &$moduleMenu) {      
    $moduleMenu[] = array(
        "parent_menu" => "global_menu_store",
        "section"      => "webgk.accordpost_export",
        "sort"          => 1,
        "url"          => "sale_order_ext.php?lang=".LANG,
        "text"          => 'Cписок заказов с доп. столбцами',
        "title"          => 'Cписок заказов с доп. столбцами',
        "icon"          => "form_menu_icon",
        "page_icon"   => "form_page_icon",
        "items_id"      => "menu_webgk.accordpost_export",
        "items"          => array()
    );
}

Main\EventManager::getInstance()->addEventHandler('sale', 'OnSaleOrderBeforeSaved', 'minPriceConstraint');
function minPriceConstraint(Main\Event $event)
{
    $dbItems = \Bitrix\Catalog\GroupTable::getList([
        'select' => ['ID'],
        'filter' => ['NAME' => 'Минимальная'],
    ])->fetchAll();

    if (! $dbItems) {
        return;
    }

    $catalogGroupId = $dbItems[0]['ID'];

    /**
     * @var \Bitrix\Sale\Order $order
     */
    $order = $event->getParameter('ENTITY');
    $basket = $order->getBasket();

    $productIds = [];
    foreach ($basket->getBasketItems() as $basketItem) {
        $productIds[] = $basketItem->getField('PRODUCT_ID');
    }

    $dbItems = \Bitrix\Catalog\PriceTable::getList([
        'select' => ['PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE'],
        'filter' => ['PRODUCT_ID' => $productIds, 'CATALOG_GROUP_ID' => $catalogGroupId],
    ])->fetchAll();

    $minPrices = array_column($dbItems, null, 'PRODUCT_ID');

    /**
     * @var \Bitrix\Sale\BasketItem $basketItem
     */
    foreach($basket->getBasketItems() as $basketItem) {
        $minPrice = empty($minPrices[$basketItem->getField('PRODUCT_ID')]['PRICE']) ? 0 : $minPrices[$basketItem->getField('PRODUCT_ID')]['PRICE'];

        if ($minPrice && $minPrice > $basketItem->getPrice()) {
            $basketItem->setPrice($minPrice, true);
            $basketItem->setField('BASE_PRICE', $minPrice);
            $basketItem->setField('DISCOUNT_PRICE', 0);

        }
    }
}


//Отправка экселя на почту после оформления заказа
//\Bitrix\Main\EventManager::getInstance()->addEventHandler('sale', 'OnSaleOrderSaved', ['ExcelSender', 'sendToManager']);
AddEventHandler('main', 'OnBeforeEventAdd', ['ExcelSender', 'sendToManager']);
class ExcelSender {
    public static function sendToManager(&$event, &$lid, &$arFields)
    {
        if ($event == 'SALE_NEW_ORDER') {
            \Bitrix\Main\Loader::includeModule('sale');
            $orderId = substr($arFields['ORDER_ID'], 2);
            $order = \Bitrix\Sale\Order::load($orderId);
            if ($order) {
                $data = [];
                $arFields['INN'] = \Bitrix\Main\UserTable::getList([
                    'filter' => [
                        'ID' => $order->getField('USER_ID')
                    ],
                    'select' => [
                        'UF_INN'
                    ]
                ])->fetchAll()[0]['UF_INN'];
                $arFields['CITY'] = \Bitrix\Main\UserTable::getList([
                    'filter' => [
                        'ID' => $order->getField('USER_ID')
                    ],
                    'select' => [
                        'UF_GOROD'
                    ]
                ])->fetchAll()[0]['UF_GOROD'];
                $catalogIblockId = \Bitrix\Iblock\IblockTable::getList([
                    'filter' => [
                        'CODE' => 'catalog_main'
                    ],
                    'select' => [
                        'ID'
                    ]
                ])->fetchAll()[0]['ID'];

                $arFields['ORDER_PROPERTY_VALUES'] = '<br><br>';
                foreach ($order->getPropertyCollection() as $property) {
                    if ($property->getField('VALUE')) {
                        $arFields['ORDER_PROPERTY_VALUES'] .= $property->getField('NAME') . ': ' . $property->getField('VALUE') . '<br>';
                    }
                }

                if ($order->getField('USER_DESCRIPTION')) {
                    $arFields['ORDER_PROPERTY_VALUES'] .= '<br><br>Комментарий: ' . $order->getField('USER_DESCRIPTION');
                }

                foreach ($order->getBasket()->getBasketItems() as $basketItem) {
                    $productId = $basketItem->getProductId();
                    $vendorCode = CIBlockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID' => $catalogIblockId,
                            'ID'        => $productId,
                        ],
                        false,
                        false,
                        [
                            'ID', 'IBLOCK_ID', 'PROPERTY_CML2_ARTICLE'
                        ]
                    )->Fetch()['PROPERTY_CML2_ARTICLE_VALUE'];
                    $data['BASKET'][] = [
                        'VENDOR_CODE' => $vendorCode,
                        'QUANTITY'    => $basketItem->getQuantity()
                    ];
                }
                if ($data['BASKET']) {
                    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/orders_excel/' . $order->getId() . '.xlsx';
                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setCellValue('A1', 'Количество');
                    $sheet->setCellValue('B1', 'Артикул');
                    foreach ($data['BASKET'] as $rowNumber => $productData) {
                        $sheet->setCellValue('A' . ($rowNumber + 2), $productData['QUANTITY']);
                        $sheet->setCellValue('B' . ($rowNumber + 2), $productData['VENDOR_CODE']);
                    }
                    $writer = new Xlsx($spreadsheet);
                    $writer->save($filePath);
                    $arFields['EXCEL_FILE'] = ($_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . '/upload/orders_excel/' . $order->getId() . '.xlsx';
                }
            }
        }
    }
}
/*
If (4 == rand(0,4) ) 
sleep(15);
*/