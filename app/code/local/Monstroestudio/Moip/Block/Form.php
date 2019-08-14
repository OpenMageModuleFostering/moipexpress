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
class Monstroestudio_Moip_Block_Form extends Mage_Payment_Block_Form {

	private $_helper;
	private $_methods;

	protected function _construct() {
		$this->_helper = Mage::helper('moip');
		$this->_methods = explode(',', $this->_helper->getConfig('methods'));

		$this->setTemplate('monstroestudio/moip/form.phtml');

		parent::_construct();
	}

	public function isCCAvailable() {
		return in_array('cc', $this->_methods);
	}
	public function isBoletoAvailable() {
		return in_array('boleto', $this->_methods);
	}
	public function isTransferenciaAvailable() {
		return in_array('deposito', $this->_methods);
	}

	public function isBoletoValidated(){
		if(Mage::helper('moip')->getConfig('bloquear_boleto_estoque')){
			$quote = Mage::getSingleton('checkout/session')->getQuote();
			$cartItems = $quote->getAllVisibleItems();

			foreach($cartItems as $item){
				if((int)Mage::getModel('cataloginventory/stock_item')->loadByProduct(Mage::getModel('catalog/product')->load($item->getProduct()->getId()))->getQty() <= (int)Mage::getStoreConfig('cataloginventory/item_options/notify_stock_qty')){
					return false;
				}
			}
		}
		return true;
	}

	public function getMoipSafe() {
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			return $this->generateSafeOptions(Mage::getModel('moip/safe')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer()->getId()));
		}else{
			return false;
		}
	}

	protected function generateSafeOptions($collection){
		if(count($collection)>0){
			$html = '<option value="new">Novo cartão</option>';
			foreach($collection	as $item){
				$html .= '<option value="'.$item->token.'" data-flag="'.$item->operator.'">**** **** **** '.$item->digits.'</option>';
			}
			return $html;
		}
		return false;
	}

	public function getParcelamento() {
		$orderTotal = $this->getQuote()->getGrandTotal();
		$minParcela = (float)$this->_helper->getConfig('parcela_min');
		$maxParcelas = (float)$this->_helper->getConfig('numero_max_parcelas');
		$semJuros = (float)$this->_helper->getConfig('parcelas_s_juros');

		$parcelas = array();

		if(Mage::helper('moip')->getConfig('parcelado')){
			if ($semJuros > 0 && ($orderTotal/$minParcela) > 1) {

				if (($orderTotal/$minParcela) > $maxParcelas) {
					for ($i = 0; $i < $semJuros; $i++) {
						$parcelas[] = array('label' => ($i+1).' parcelas de '.Mage::helper('core')->currency(($orderTotal/($i+1)), true, false).' - Total: '.Mage::helper('core')->currency($orderTotal, true, false), 'value' => (int)$i+1);
					}

					for ($i = $semJuros; $i < $maxParcelas; $i++) {
						$parcelas[] = array('label' => ($i+1).' parcelas de '.$this->getValorParcelaLabel($orderTotal, ($i+1)).'*', 'value' => (int)$i+1);
					}
				}else {
					for ($i = 0; $i < $semJuros; $i++) {
						$parcelas[] = array('label' => ($i+1).' parcelas de '.Mage::helper('core')->currency(($orderTotal/($i+1)), true, false).' - Total: '.Mage::helper('core')->currency($orderTotal, true, false), 'value' => (int)$i+1);
					}

					for ($i = $semJuros; $i < floor($this->getJurosComposto($orderTotal,$i)/$minParcela); $i++) {
						$parcelas[] = array('label' => ($i+1).' parcelas de '.$this->getValorParcelaLabel($orderTotal, ($i+1)).'*', 'value' => (int)$i+1);
					}
				}
			}
		}else{
			$parcelas[] = array('label' => 'À vista', 'value' => 1);
		}

		$html = '';
		foreach ($parcelas as $parcela) {
			$html .= '<option value="'.$parcela['value'].'">'.$parcela['label'].'</option>';
		}

		return $html;
	}

	public function getParcelasTexto() {
		return 'Parcelas com "*" tem juros de'.str_replace('.', ',', $this->_helper->getConfig('juros_parcela')).'% ao mês';
	}

	public function getTextoDescontoBoleto() {
		$desconto = (float)$this->_helper->getConfig('desconto_boleto');
		if ($desconto > 0) {
			return "<p class='desconto'>Desconto de $desconto% utilizando o Boleto Bancário como forma de pagamento</p>";
		}
		return;
	}

	public function getTextoDescontoTransferencia() {
		$desconto = (float)$this->_helper->getConfig('desconto_transf');
		if ($desconto > 0) {
			return "<p class='desconto'>Desconto de $desconto% utilizando a Transferência Bancária como forma de pagamento</p>";
		}
		return;
	}

	protected function getValorParcela($valor, $parcelas) {
		return Mage::helper('core')->currency($this->getJurosComposto($valor, $parcelas)/$parcelas, true, false);
	}

	protected function getValorParcelaLabel($valor, $parcelas) {
		return Mage::helper('core')->currency($this->getJurosComposto($valor, $parcelas)/$parcelas, true, false).' - Total: '.Mage::helper('core')->currency($this->getJurosComposto($valor, $parcelas), true, false);
	}

	protected function getJurosComposto($valor, $parcelas) {
		$juros = (float)$this->_helper->getConfig('juros_parcela')/100;

		return round(($juros/(1-(1/pow((1+$juros),(int)$parcelas))))*$valor*(int)$parcelas,2);
	}

	/**
	 * Get checkout session namespace
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	protected function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}

	/**
	 * Get current quote
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	protected function getQuote() {
		return $this->getCheckout()->getQuote();
	}

	/**
	 * Get one page checkout model
	 *
	 * @return Mage_Checkout_Model_Type_Onepage
	 */
	protected function getOnepage() {
		return Mage::getSingleton('checkout/type_onepage');
	}

}
