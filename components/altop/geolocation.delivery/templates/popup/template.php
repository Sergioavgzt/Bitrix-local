<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

$frame = $this->createFrame("geolocation_delivery_detail_".$arParams["ELEMENT_ID"])->begin("");

use Bitrix\Main\Localization\Loc;?>

<div class="geolocation-delivery-detail__params">
	<div class="geolocation-delivery-detail__col"><?=Loc::getMessage("GEOLOCATION_DELIVERY_QUANTITY_PRODUCTS")?></div>
	<div class="geolocation-delivery-detail__col">
		<div class="geolocation-delivery-detail__qnt">
			<a id="geolocationDeliveryDetailMinus-<?=$arParams['ELEMENT_ID']?>" class="minus" href="javascript:void(0)"><span>-</span></a>
			<input id="geolocationDeliveryDetailQntInput-<?=$arParams['ELEMENT_ID']?>" class="quantity" name="quantity" value="<?=$arParams['ELEMENT_COUNT']?>" type="text" />
			<a id="geolocationDeliveryDetailPlus-<?=$arParams['ELEMENT_ID']?>" class="plus" href="javascript:void(0)"><span>+</span></a>
		</div>
	</div>
	<div class="geolocation-delivery-detail__col">
		<div class="geolocation-delivery-detail__option">
			<input id="geolocationDeliveryDetailCartProducts-<?=$arParams['ELEMENT_ID']?>" name="cart-products"<?=($arParams["CART_PRODUCTS"] == "Y" ? " checked='checked'" : "");?> value="Y" type="checkbox" />
			<label for="geolocationDeliveryDetailCartProducts-<?=$arParams['ELEMENT_ID']?>"><span class="check-cont"><span class="check"><i class="fa fa-check"></i></span></span><span class="check-title"><?=Loc::getMessage("GEOLOCATION_DELIVERY_CART_PRODUCTS")?></span></label>
		</div>
	</div>
</div>
<div id="geolocationDeliveryDetailDeliveryList-<?=$arParams['ELEMENT_ID']?>" class="geolocation-delivery-detail__delivery-list">
	<?foreach($arResult["DELIVERY"] as $delivery_id => $arDelivery):?>
		<div class="geolocation-delivery-detail__delivery-item">
			<div class="geolocation-delivery-detail__delivery-logo-wrap-wrap geolocation-delivery-detail__cell">
				<div class="geolocation-delivery-detail__delivery-logo-wrap">
					<div class="geolocation-delivery-detail__delivery-logo">						
						<?if(is_array($arDelivery["LOGOTIP"])):?>
							<img src="<?=$arDelivery['LOGOTIP']['SRC']?>" width="<?=$arDelivery['LOGOTIP']['WIDTH']?>" height="<?=$arDelivery['LOGOTIP']['HEIGHT']?>" alt="<?=$arDelivery['NAME']?>" />
						<?else:?>
							<img src="<?=SITE_TEMPLATE_PATH?>/images/no-photo.jpg" width="30" height="30" alt="<?=$arDelivery['NAME']?>" />
						<?endif;?>
					</div>
				</div>
			</div>
			<div class="geolocation-delivery-detail__cell">
				<div class="geolocation-delivery-detail__delivery-name"><?=$arDelivery["NAME"]?></div>
				<?if(!empty($arDelivery["DESCRIPTION"])):?>
					<div class="geolocation-delivery-detail__delivery-descr"><?=$arDelivery["DESCRIPTION"]?></div>
				<?endif;?>
			</div>
			<div class="geolocation-delivery-detail__delivery-period geolocation-delivery-detail__cell"><?=$arDelivery["PERIOD_TEXT"]?></div>
			<div class="geolocation-delivery-detail__delivery-price geolocation-delivery-detail__cell"><?=($arDelivery["PRICE"] > 0 ? $arDelivery["PRICE_FORMATED"] : "<span class='geolocation-delivery-detail__delivery-price-free'>".Loc::getMessage("GEOLOCATION_DELIVERY_PRICE_FREE")."</span>");?></div>
		</div>
	<?endforeach;?>
</div>

<?//JS_PARAMS//
$arJSParams["COMPONENT_PATH"] = $this->__component->__path;
$arJSParams["COMPONENT_TEMPLATE"] = $this->GetFolder();
$arJSParams["PARAMS"] = $arParams;
$arJSParams = CUtil::PhpToJSObject($arJSParams);?>

<script type="text/javascript">
	//CHANGE_GEOLOCATION_DELIVERY_DETAIL_QNT_INPUT//
	BX.bind(BX("geolocationDeliveryDetailQntInput-<?=$arParams['ELEMENT_ID']?>"), "bxchange", function() {
		BX.UpdateGeolocationDeliveryDetail(<?=$arJSParams?>);
	});

	//CLICK_GEOLOCATION_DELIVERY_DETAIL_MINUS//
	BX.bind(BX("geolocationDeliveryDetailMinus-<?=$arParams['ELEMENT_ID']?>"), "click", function() {
		var qntInput = BX("geolocationDeliveryDetailQntInput-<?=$arParams['ELEMENT_ID']?>");
		if(qntInput.value > <?=$arResult["CATALOG_MEASURE_RATIO"]?>) {
			qntInput.value = parseFloat(qntInput.value) - <?=$arResult["CATALOG_MEASURE_RATIO"]?>;
			BX.UpdateGeolocationDeliveryDetail(<?=$arJSParams?>);
		}
	});

	//CLICK_GEOLOCATION_DELIVERY_DETAIL_PLUS//
	BX.bind(BX("geolocationDeliveryDetailPlus-<?=$arParams['ELEMENT_ID']?>"), "click", function() {
		var qntInput = BX("geolocationDeliveryDetailQntInput-<?=$arParams['ELEMENT_ID']?>");
		qntInput.value = parseFloat(qntInput.value) + <?=$arResult["CATALOG_MEASURE_RATIO"]?>;
		BX.UpdateGeolocationDeliveryDetail(<?=$arJSParams?>);
	});

	//CHANGE_GEOLOCATION_DELIVERY_DETAIL_CART_PRODUCTS//
	BX.bind(BX("geolocationDeliveryDetailCartProducts-<?=$arParams['ELEMENT_ID']?>"), "change", function() {
		BX.UpdateGeolocationDeliveryDetail(<?=$arJSParams?>);
	});
</script>

<?$frame->end();?>