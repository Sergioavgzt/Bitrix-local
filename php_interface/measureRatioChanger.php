<?php
$_SERVER["DOCUMENT_ROOT"] = dirname(__DIR__, 2);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('iblock');
$start = microtime(true);
$catalogIblockID = \Bitrix\Iblock\IblockTable::getList([
    'filter' => [
        'CODE' => 'MAIN_CATALOG'
    ],
    'select' => [
        'ID'
    ]
])->fetchAll()[0]['ID'];
$elementsWithRatio = [];
$activeElementsRes = CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID' => $catalogIblockID,
        'ACTIVE' => 'Y',
        '!PROPERTY_MINIMALNOE_KOLICHESTVO' => false
    ],
    false,
    false,
    [
        'ID',
        'PROPERTY_MINIMALNOE_KOLICHESTVO'
    ]
);
while ($activeElement = $activeElementsRes->GetNext()) {
    $elementsWithRatio[$activeElement['ID']] = $activeElement['PROPERTY_MINIMALNOE_KOLICHESTVO_VALUE'];
}
foreach ($elementsWithRatio as $productId => $newRatioValue) {
    $ratioRes = CCatalogMeasureRatio::getList(
        [],
        [
            'IBLOCK_ID' => $catalogIblockID,
            'PRODUCT_ID' => $productId
        ],
        false,
        false
    );
    if ((int)$ratioRes->SelectedRowsCount() > 0) {
        while ($ratio = $ratioRes->GetNext()) {
            $currentRatioId = $ratio['ID'];
            $currentRationValue = $ratio['RATIO'];
        }
        if ($newRatioValue !== $currentRationValue) {
            $res = \Bitrix\Catalog\MeasureRatioTable::update($currentRatioId, [
                'RATIO' => $newRatioValue
            ]);
        }
    } else {
        $res = \Bitrix\Catalog\MeasureRatioTable::add([
            'PRODUCT_ID' => $arFields['ID'],
            'RATIO' => $newRatioValue
        ]);
    }
}
print_r(microtime(true) - $start);