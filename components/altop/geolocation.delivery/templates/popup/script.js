//UPDATE_GEOLOCATION_DELIVERY_DETAIL//
BX.UpdateGeolocationDeliveryDetail = function(arParams) {	
	var deliveryList = BX("geolocationDeliveryDetailDeliveryList-" + arParams.PARAMS.ELEMENT_ID),
		deliveryListCont = deliveryList.parentNode,
		qnt = BX("geolocationDeliveryDetailQntInput-" + arParams.PARAMS.ELEMENT_ID).value,
		inCart = BX("geolocationDeliveryDetailCartProducts-" + arParams.PARAMS.ELEMENT_ID),
		inCartVal = inCart.checked ? inCart.value : "N";

	deliveryListCont.innerHTML = "<div class='popup-window-wait'><i class='fa fa-spinner fa-pulse'></i></div>";
	BX.ajax.post(
		arParams.COMPONENT_PATH + "/ajax.php",
		{				
			sessid: BX.bitrix_sessid(),
			action: "geolocationDelivery",
			template: "popup",			
			arParams: arParams.PARAMS,
			quantity: qnt,
			cartProducts: inCartVal
		},
		BX.delegate(function(result)
		{
			deliveryListCont.innerHTML = result;
		},
		this)
	);
};