<?php
/**
 * Monstro Estúdio e Groh & Partners.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/copyleft/gpl.html
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This package designed for Magento COMMUNITY edition
 * Monstro Estúdio e Groh & Partners does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Monstro Estúdio e Groh & Partners does not provide extension support in case of
 * incorrect edition usage.
 * =================================================================
 *
 * @category   Monstroestudio
 * @package    Monstroestudio_Moip
 * @copyright  Copyright (c) 2010-2014 Monstro Estúdio e Groh & Partners Brasil Co. (http://www.aheadworks.com)
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Monstroestudio_Moip_Model_Observer{
	public function updateStreetLines($observer){
		if(Mage::helper('moip')->isActive()){
			$model = new Mage_Core_Model_Config();
			$model->saveConfig('customer/address/street_lines', "4", 'default', 0);

		}
	}

	public function setDiscount($observer) {
		$type = false;
		if($this->getOnepage()->getQuote()->getPayment()->getMoipMethod() == 'boleto'){
			$type = 'boleto';
		}else if($this->getOnepage()->getQuote()->getPayment()->getMoipMethod() == 'transferencia'){
			$type = 'transf';
		}

		$quote=$observer->getEvent()->getQuote();

		if(!$quote || !$type){
			return false;
		}
		$quoteid = $quote->getId();

		if($type == 'boleto'){
			$descTitle = 'Boleto';
		}else{
			$descTitle = 'Transferência Bancária';
		}

		if ($quoteid && (float)Mage::helper('moip')->getConfig('desconto_'.$type) > 0) {
			$discountAmount = ((float)Mage::helper('moip')->getConfig('desconto_'.$type)/100)*$quote->getBaseSubtotal();

			if ($discountAmount>0) {
				$total=$quote->getBaseSubtotal();
				$quote->setSubtotal(0);
				$quote->setBaseSubtotal(0);

				$quote->setSubtotalWithDiscount(0);
				$quote->setBaseSubtotalWithDiscount(0);

				$quote->setGrandTotal(0);
				$quote->setBaseGrandTotal(0);


				$canAddItems = $quote->isVirtual()? ('billing') : ('shipping');
				foreach ($quote->getAllAddresses() as $address) {

					$address->setSubtotal(0);
					$address->setBaseSubtotal(0);

					$address->setGrandTotal(0);
					$address->setBaseGrandTotal(0);

					$address->collectTotals();

					$quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
					$quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());

					$quote->setSubtotalWithDiscount((float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount());
					$quote->setBaseSubtotalWithDiscount((float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount());

					$quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
					$quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());

					$quote->save();

					$quote->setGrandTotal($quote->getBaseSubtotal()-$discountAmount)
					->setBaseGrandTotal($quote->getBaseSubtotal()-$discountAmount)
					->setSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
					->setBaseSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
					->save();


					if ($address->getAddressType()==$canAddItems) {
						$address->setSubtotalWithDiscount((float) $address->getSubtotalWithDiscount()-$discountAmount);
						$address->setGrandTotal((float) $address->getGrandTotal()-$discountAmount);
						$address->setBaseSubtotalWithDiscount((float) $address->getBaseSubtotalWithDiscount()-$discountAmount);
						$address->setBaseGrandTotal((float) $address->getBaseGrandTotal()-$discountAmount);
						if ($address->getDiscountDescription()) {
							$address->setDiscountAmount(-($address->getDiscountAmount()-$discountAmount));
							$address->setDiscountDescription($address->getDiscountDescription().', Desconto '.$descTitle.': '.Mage::helper('moip')->getConfig('desconto_boleto').'%');
							$address->setBaseDiscountAmount(-($address->getBaseDiscountAmount()-$discountAmount));
						}else {
							$address->setDiscountAmount(-($discountAmount));
							$address->setDiscountDescription('Desconto '.$descTitle.': '.Mage::helper('moip')->getConfig('desconto_'.$type).'%');
							$address->setBaseDiscountAmount(-($discountAmount));
						}
						$address->save();
					}
				}

				foreach ($quote->getAllItems() as $item) {
					//We apply discount amount based on the ratio between the GrandTotal and the RowTotal
					$rat=$item->getPriceInclTax()/$total;
					$ratdisc=$discountAmount*$rat;
					$item->setDiscountAmount(($item->getDiscountAmount()+$ratdisc) * $item->getQty());
					$item->setBaseDiscountAmount(($item->getBaseDiscountAmount()+$ratdisc) * $item->getQty())->save();

				}
			}
		}
	}

	public function setCCTax($observer) {
		if($this->getOnepage()->getQuote()->getPayment()->getMoipMethod() == 'cc' && ($this->getOnepage()->getQuote()->getPayment()->getParcelas() > (float)Mage::helper('moip')->getConfig('parcelas_s_juros') || $this->getOnepage()->getQuote()->getPayment()->getSafeParcelas() > (float)Mage::helper('moip')->getConfig('parcelas_s_juros'))){
			$quote=$observer->getEvent()->getQuote();
			if(!$quote){
				return false;
			}
			$quoteid = $quote->getId();

			if ($quoteid) {
				$discountAmount = -($this->getJurosComposto($quote->getGrandTotal(), (int)$this->getOnepage()->getQuote()->getPayment()->getParcelas()) - $quote->getGrandTotal());

				if ($discountAmount<0) {
					$total=$quote->getBaseSubtotal();
					$quote->setSubtotal(0);
					$quote->setBaseSubtotal(0);

					$quote->setSubtotalWithDiscount(0);
					$quote->setBaseSubtotalWithDiscount(0);

					$quote->setGrandTotal(0);
					$quote->setBaseGrandTotal(0);


					$canAddItems = $quote->isVirtual()? ('billing') : ('shipping');
					foreach ($quote->getAllAddresses() as $address) {

						$address->setSubtotal(0);
						$address->setBaseSubtotal(0);

						$address->setGrandTotal(0);
						$address->setBaseGrandTotal(0);

						$address->collectTotals();

						$quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
						$quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());

						$quote->setSubtotalWithDiscount((float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount());
						$quote->setBaseSubtotalWithDiscount((float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount());

						$quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
						$quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());

						$quote->save();

						$quote->setGrandTotal($quote->getBaseSubtotal()-$discountAmount)
						->setBaseGrandTotal($quote->getBaseSubtotal()-$discountAmount)
						->setSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
						->setBaseSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
						->save();


						if ($address->getAddressType()==$canAddItems) {
							$address->setSubtotalWithDiscount((float) $address->getSubtotalWithDiscount()-$discountAmount);
							$address->setGrandTotal((float) $address->getGrandTotal()-$discountAmount);
							$address->setBaseSubtotalWithDiscount((float) $address->getBaseSubtotalWithDiscount()-$discountAmount);
							$address->setBaseGrandTotal((float) $address->getBaseGrandTotal()-$discountAmount);
							if ($address->getDiscountDescription()) {
								$address->setDiscountAmount(-($address->getDiscountAmount()-$discountAmount));
								$address->setDiscountDescription($address->getDiscountDescription().', Juros de parcelas do cartão de crédito');
								$address->setBaseDiscountAmount(-($address->getBaseDiscountAmount()-$discountAmount));
							}else {
								$address->setDiscountAmount(-($discountAmount));
								$address->setDiscountDescription('Juros de parcelas do cartão de crédito');
								$address->setBaseDiscountAmount(-($discountAmount));
							}
							$address->save();
						}
					}

					foreach ($quote->getAllItems() as $item) {
						//We apply discount amount based on the ratio between the GrandTotal and the RowTotal
						$rat=$item->getPriceInclTax()/$total;
						$ratdisc=$discountAmount*$rat;
						$item->setDiscountAmount(($item->getDiscountAmount()+$ratdisc) * $item->getQty());
						$item->setBaseDiscountAmount(($item->getBaseDiscountAmount()+$ratdisc) * $item->getQty())->save();

					}
				}
			}
		}
	}

	protected function getJurosComposto($valor, $parcelas) {
		$juros = (float)Mage::helper('moip')->getConfig('juros_parcela')/100;

		return ($juros/(1-(1/pow((1+$juros),(int)$parcelas))))*$valor*(int)$parcelas;
	}


	/**
	 * Get checkout session namespace
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}

	/**
	 * Get current quote
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	public function getQuote() {
		return $this->getCheckout()->getQuote();
	}

	/**
	 * Get one page checkout model
	 *
	 * @return Mage_Checkout_Model_Type_Onepage
	 */
	public function getOnepage() {
		return Mage::getSingleton('checkout/type_onepage');
	}
}