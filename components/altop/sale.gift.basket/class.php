<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

CBitrixComponent::includeComponentClass("bitrix:catalog.viewed.products");

class CSaleGiftBasketComponent extends CCatalogViewedProductsComponent {	
	protected $giftManager;
	private $basket;
	private $productIds;
	
	protected function checkModules() {
		parent::checkModules();
		if(!$this->isSale) {
			throw new SystemException(Loc::getMessage("SGB_SALE_MODULE_NOT_INSTALLED"));
		}

		$this->initGiftManager();
	}
	
	protected function initGiftManager() {
		global $USER;
		$userId = $USER instanceof CAllUser ? $USER->getId() : null;
		$this->giftManager = \Bitrix\Sale\Discount\Gift\Manager::getInstance()->setUserId($userId);
	}
	
	public function onPrepareComponentParams($params) {
		$params = parent::onPrepareComponentParams($params);
		if(empty($params["FULL_DISCOUNT_LIST"])) {
			$params["FULL_DISCOUNT_LIST"] = array();
		}
		if(empty($params["APPLIED_DISCOUNT_LIST"])) {
			$params["APPLIED_DISCOUNT_LIST"] = array();
		}

		return $params;
	}
	
	private function getBasket() {
		if($this->basket === null) {
			$basketStorage = \Bitrix\Sale\Basket\Storage::getInstance(\Bitrix\Sale\Fuser::getId(), SITE_ID);
			$this->basket = $basketStorage->getBasket();
		}

		return $this->basket;
	}
	
	protected function getGiftCollections() {
		$collections = array();
		if(!empty($this->arParams["FULL_DISCOUNT_LIST"])) {
			$collections = $this->giftManager->getCollectionsByBasket(
				$this->getBasket(),
				$this->arParams["FULL_DISCOUNT_LIST"],
				$this->arParams["APPLIED_DISCOUNT_LIST"]
			);
		}

		return $collections;
	}
	
	protected function getProductIds() {
		if($this->productIds !== null) {
			return $this->productIds;
		}

		\Bitrix\Sale\Compatible\DiscountCompatibility::stopUsageCompatible();
		$collections = $this->getGiftCollections();
		\Bitrix\Sale\Compatible\DiscountCompatibility::revertUsageCompatible();

		$this->productIds = array();
		foreach($collections as $collection) {
			foreach($collection as $gift) {
				$this->productIds[] = $gift->getProductId();
			}
			unset($gift);
		}
		unset($collection);

		return $this->productIds;
	}
	
	private function getPureOffers() {
		$pureOffers = array();
		foreach($this->getProductIds() as $productId) {
			if(isset($this->linkItems[$productId])) {
				continue;
			}
			$pureOffer = $this->findPureOfferInItemsByOfferId($productId);
			if(!$pureOffer) {
				continue;
			}
			if(!isset($pureOffers[$pureOffer["LINK_ELEMENT_ID"]])) {
				$pureOffers[$pureOffer["LINK_ELEMENT_ID"]] = array();
			}
			$pureOffers[$pureOffer["LINK_ELEMENT_ID"]][] = $pureOffer;
		}
		unset($productId);

		return $pureOffers;
	}
	
	private function findPureOfferInItemsByOfferId($offerId) {
		if(!empty($this->items[$offerId]["OFFERS"])) {
			//positive search
			foreach($this->items[$offerId]["OFFERS"] as $i => $offer) {
				if($offer["ID"] == $offerId) {
					return $offer;
				}
			}
			unset($offer);
		}

		//if we have two or more offers for one product, then only one of them has OFFERS, all of another don't have.
		foreach($this->items as $item) {
			if(!$item["OFFERS"]) {
				continue;
			}
			foreach($item["OFFERS"] as $offer) {
				if($offer["ID"] == $offerId) {
					return $offer;
				}
			}
			unset($offer);
		}
		unset($offer);

		return null;
	}
	
	private function setPureOffersToProduct(array $pureOffers) {
		$parentElementId = null;
		foreach($pureOffers as $pureOffer) {
			if(!$parentElementId) {
				$parentElementId = $pureOffer["LINK_ELEMENT_ID"];
				$this->items[$parentElementId]["OFFERS"] = $pureOffers;
			} else {
				//we have to delete another offers, because they will repeat base product.
				unset($this->items[$pureOffer["ID"]]);
			}
		}
		unset($pureOffer);

		if($parentElementId) {
			$this->linkItems[$parentElementId]["OFFERS"] = $pureOffers;
		}
	}
	
	final protected function setItemsOffers() {
		$isEnabledCalculationDiscounts = CIBlockPriceTools::isEnabledCalculationDiscounts();
		CIBlockPriceTools::disableCalculationDiscounts();

		parent::setItemsOffers();

		foreach($this->linkItems as &$item) {
			if(!isset($item["OFFERS"])) {
				continue;
			}

			foreach($item["OFFERS"] as &$offer) {
				$this->setGiftDiscountToMinPrice($offer);
			}
			unset($offer);
		}
		unset($item);

		foreach($this->getPureOffers() as $offers) {
			$this->setPureOffersToProduct($offers);
		}
		unset($offerId);

		if($isEnabledCalculationDiscounts) {
			CIBlockPriceTools::enableCalculationDiscounts();
		}
	}

	protected function setItemsPrices() {
		parent::setItemsPrices();

		foreach($this->items as &$item) {
			if(!empty($item["OFFERS"])) {
				continue;
			}

			$this->setGiftDiscountToMinPrice($item);
		}
	}

	protected function formatResult() {
		$this->items = array_slice($this->items, 0, $this->arParams["PAGE_ELEMENT_COUNT"]);
		parent::formatResult();
	}
	
	protected function getPriceDataByItem(array $item) {
		$isEnabledCalculationDiscounts = CIBlockPriceTools::isEnabledCalculationDiscounts();
		CIBlockPriceTools::disableCalculationDiscounts();

		$priceDataByItem = parent::getPriceDataByItem($item);

		if($isEnabledCalculationDiscounts) {
			CIBlockPriceTools::enableCalculationDiscounts();
		}

		return $priceDataByItem;
	}
	
	private function setGiftDiscountToMinPrice(array &$offer) {		
		$offer["MIN_PRICE"]["PRINT_DISCOUNT_VALUE_NOVAT"] = $offer["MIN_PRICE"]["PRINT_DISCOUNT_DIFF"];
		$offer["MIN_PRICE"]["PRINT_DISCOUNT_VALUE"] = $offer["MIN_PRICE"]["PRINT_DISCOUNT_DIFF"];
		$offer["MIN_PRICE"]["PRINT_DISCOUNT_VALUE_VAT"] = $offer["MIN_PRICE"]["PRINT_DISCOUNT_DIFF"];

		$offer["MIN_PRICE"]["DISCOUNT_DIFF"] = $offer["MIN_PRICE"]["VALUE"];
		$offer["MIN_PRICE"]["DISCOUNT_DIFF"] = $offer["MIN_PRICE"]["PRINT_VALUE"];
		$offer["MIN_PRICE"]["DISCOUNT_DIFF_PERCENT"] = 100;
		$offer["MIN_PRICE"]["DISCOUNT_VALUE_NOVAT"] = 0;
		$offer["MIN_PRICE"]["DISCOUNT_VALUE_VAT"] = 0;
		$offer["MIN_PRICE"]["DISCOUNT_VALUE"] = 0;
	}
	
	private function isExtendedCatalogProvider(\Bitrix\Sale\BasketItem $item) {
		return $item->getField("MODULE") === "catalog" && ($item->getProvider() && ($item->getProvider() === "CCatalogProductProvider" || $item->getProvider() === "\Bitrix\Catalog\Product\CatalogProvider" || array_key_exists("CCatalogProductProvider", class_parents($item->getProvider())) || array_key_exists("\Bitrix\Catalog\Product\CatalogProvider", class_parents($item->getProvider()))));
	}
}