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
class Monstroestudio_Moip_Block_Redirect extends Mage_Core_Block_Template {

	private $_helper;
	private $_salesData;
	private $_order;

	protected function _construct() {
		parent::_construct();
		$this->_helper = Mage::helper('moip');
		$this->_salesData = $this->getCheckout()->getMoipData();
		$this->_order = Mage::getModel('sales/order')->load($this->getCheckout()->getLastOrderId());
		Mage::register('current_order',$this->_order);

		$this->setTemplate('monstroestudio/moip/redirect.phtml');
	}

	public function getPaymentMethod(){
		return $this->_salesData['method'];
	}

	public function getShippingDescription(){
		return $this->_order->getShippingDescription();
	}

	public function getMoipJson(){

		$json = array();
		if($this->_salesData['method'] == 'cc' && $this->_salesData['moip_safe'] == 'new'){
			$json['Forma'] = 'CartaoCredito';
			$json['Instituicao'] = $this->getInstituicao($this->_salesData['bandeira']);
			$json['Parcelas'] = $this->_salesData['parcelas'];
			$json['CartaoCredito'] = array();
			$json['CartaoCredito']['Numero'] = Mage::helper('core')->decrypt($this->_salesData['cc']);
			$json['CartaoCredito']['Expiracao'] = $this->_salesData['validade'];
			$json['CartaoCredito']['CodigoSeguranca'] = $this->_salesData['cvv'];
			$json['CartaoCredito']['Portador'] = array();
			$json['CartaoCredito']['Portador']['Nome'] = $this->_salesData['cc_holder_name'];
			$json['CartaoCredito']['Portador']['DataNascimento'] = $this->_salesData['cc_holder_dob'];
			$json['CartaoCredito']['Portador']['Telefone'] = $this->_salesData['cc_holder_phone'];
			$json['CartaoCredito']['Portador']['Identidade'] = $this->_salesData['cc_holder_cpf'];
		}elseif($this->_salesData['method'] == 'cc' && $this->_salesData['moip_safe'] != 'new'){
			$json['Forma'] = 'CartaoCredito';
			$json['Instituicao'] = $this->getInstituicao($this->_salesData['bandeira']);
			$json['Parcelas'] = $this->_salesData['parcelas'];
			$json['CartaoCredito'] = array();
			$json['CartaoCredito']['Cofre'] = $this->_salesData['moip_safe'];
			$json['CartaoCredito']['CodigoSeguranca'] = $this->_salesData['safe_cvv'];
		}



		if($this->_salesData['method'] == 'boleto'){
			$json['Forma'] = 'BoletoBancario';
		}

		if($this->_salesData['method'] == 'transferencia'){
			$json['Forma'] = 'DebitoBancario';
			$json['Instituicao'] = $this->_salesData['bandeira'];
		}

		//erase credit card data
		$this->getCheckout()->setMoipData('');

		return  json_encode($json);

	}

	public function getMoipUrl(){
		if ((bool)$this->_helper->getConfig('test')) {
			return "https://desenvolvedor.moip.com.br/sandbox";
		}
		return "https://www.moip.com.br/";
	}

	public function getToken(){
		return Mage::getModel('moip/transactions')->loadByOrder($this->getOrderId())->token;
	}

	public function getOrderId(){
		return $this->getCheckout()->getLastRealOrderId();
	}

	/**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    protected function getInstituicao($string){
	    switch($string){
			case 'amex':
				return 'AmericanExpress';
			break;
			case 'diners':
				return 'Diners';
			break;
			case 'master':
				return 'Mastercard';
			break;
			case 'hiper':
				return 'Hipercard';
			break;
			case 'visa':
				return 'Visa';
			break;
		}
    }

}
