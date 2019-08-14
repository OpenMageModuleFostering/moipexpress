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
class Monstroestudio_Moip_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{

	private $_token = 'VUVRIGGW9A7UM6DPAHOFKAOKSZLREDFB';
	private $_key = 'FIAFGINNFLYFMUDLNUJVEMIIVVIHADJAW3ARLXT8';
	private $_tokenHomolog = 'CC0IIN6QXYPXTYNRS5VKFNVHKD2F76W1';
	private $_keyHomolog = 'SUI72I2JBVJUCCZHCJRUSEFJBBBCE9UVTCBWCTM2';
	private $_moip;

	protected $_code = 'moip';
	protected $_isGateway               = true;
	protected $_canAuthorize            = true;
	protected $_canCapture              = true;
	protected $_canCapturePartial       = true;
	protected $_canRefund               = true;
	protected $_canVoid                 = true;
	protected $_canUseInternal          = true;
	protected $_canUseCheckout          = true;
	protected $_canUseForMultishipping  = true;
	protected $_canSaveCc = false;
	protected $_formBlockType = 'Monstroestudio_Moip_Block_Form';

	/**
	 * Send authorize request to gateway
	 *
	 * @param  Mage_Payment_Model_Info $payment
	 * @param  decimal $amount
	 * @return Monstroestudio_Moip_Model_PaymentMethod
	 */
	public function authorize(Varien_Object $payment, $amount) {
		if ($amount <= 0) {
			Mage::throwException(Mage::helper('paygate')->__('Invalid amount for authorization.'));
		}
		if($this->getOnepage()->getQuote()->getPayment()->getMoipMethod() == 'cc'){
			if(Mage::helper('moip')->getConfig('parcelado') && (float)Mage::helper('moip')->getConfig('parcelas_s_juros') < (int)$this->getOnepage()->getQuote()->getPayment()->getParcelas()){
				$amount = round($this->removeJurosComposto($amount, (int)$this->getOnepage()->getQuote()->getPayment()->getParcelas()),2);
			}
		}

		$this->_moip = new Moip_Moip();

		$this->_moip->setEnvironment((bool)Mage::helper('moip')->getConfig('test'));

		if((bool)Mage::helper('moip')->getConfig('test')){
			$this->_moip->setCredential(array(
				'key' => $this->_keyHomolog,
				'token' => $this->_tokenHomolog
			));
		}else{
			$this->_moip->setCredential(array(
				'key' => $this->_key,
				'token' => $this->_token
			));
		}

		$billing = $this->getQuote()->getBillingAddress();
		$street = $billing->getStreet();
		$this->_moip->setUniqueID(Mage::app()->getStore()->getName().$payment->getOrder()->getIncrementId());
		$this->_moip->setValue("$amount");
		$this->_moip->setReason('Compra na loja '.Mage::app()->getStore()->getName());
		$this->_moip->setPayer(array(
				'name'            => $payment->getOrder()->getCustomerFirstname().' '.$payment->getOrder()->getCustomerLastname(),
				'email'           => $payment->getOrder()->getCustomerEmail(),
				'payerId'         => $payment->getOrder()->getCustomerId(),
				'billingAddress'  => array(
					'address'         => $street[0],
					'number'          => $street[1],
					'complement'      => $street[2],
					'neighborhood'    => $street[3],
					'city'            => $billing->getCity(),
					'state'           => $billing->getRegionCode(),
					'country'         => $billing->getCountryId(),
					'zipCode'         => $billing->getPostcode(),
					'phone'           => $billing->getTelephone()
				)));

		if($this->getOnepage()->getQuote()->getPayment()->getMoipMethod() == 'boleto'){
			$this->_moip->addPaymentWay('billet');
			if(Mage::getStoreConfig('design/theme/default') !== ''){
				$theme = '/'.Mage::getStoreConfig('design/theme/default').'/';
			}else{
				$theme = '/default/';
			}
			$this->_moip->setBilletConf(Mage::helper('moip')->getConfig('vencimento_boleto'), Mage::helper('moip')->getConfig('tipo_vencimento_boleto'), explode("\n", utf8_decode(Mage::helper('moip')->getConfig('instrucao_boleto'))), Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'frontend/'.Mage::getStoreConfig('design/package/name').$theme.Mage::getStoreConfig('design/header/logo_src'));
		}

		if($this->getOnepage()->getQuote()->getPayment()->getMoipMethod() == 'cc' && Mage::helper('moip')->getConfig('parcelado')){
			if((int)$this->getOnepage()->getQuote()->getPayment()->getParcelas() > (int)Mage::helper('moip')->getConfig('parcelas_s_juros')){
				$this->_moip->addParcel(((int)Mage::helper('moip')->getConfig('parcelas_s_juros')+1), (int)Mage::helper('moip')->getConfig('numero_max_parcelas'), (float)Mage::helper('moip')->getConfig('juros_parcela'), false, ((bool)Mage::helper('moip')->getConfig('parcelado')?'AVista':'Parcelado'));
			}else{
				$this->_moip->addParcel('1', Mage::helper('moip')->getConfig('parcelas_s_juros'),0,false,((bool)Mage::helper('moip')->getConfig('parcelado')?'AVista':'Parcelado'));
			}
		}

		$this->_moip->setReceiver(Mage::helper('moip')->getConfig('login'), Mage::app()->getStore()->getName());
		$this->_moip->setNotificationURL(Mage::getUrl('moip/checkout/update'));
		$this->_moip->validate('Identification');
		$this->_moip->send();

		$this->getCheckout()->setMoipData(array(
			'method'=> $this->getOnepage()->getQuote()->getPayment()->getMoipMethod(),
			'cc' => Mage::helper('core')->encrypt($this->getOnepage()->getQuote()->getPayment()->getCcNumber()),
			'bandeira' => $this->getOnepage()->getQuote()->getPayment()->getBandeira(),
			'validade' => $this->getOnepage()->getQuote()->getPayment()->getValidade(),
			'parcelas' => $this->getOnepage()->getQuote()->getPayment()->getParcelas(),
			'cvv' => $this->getOnepage()->getQuote()->getPayment()->getCvv(),
			'safe_cvv' => $this->getOnepage()->getQuote()->getPayment()->getSafeCvv(),
			'safe_parcelas' => $this->getOnepage()->getQuote()->getPayment()->getSafeParcelas(),
			'moip_safe' => $this->getOnepage()->getQuote()->getPayment()->getMoipSafe(),
			'cc_holder_name' => $this->getOnepage()->getQuote()->getPayment()->getCreditcardHolderName(),
			'cc_holder_cpf' => $this->getOnepage()->getQuote()->getPayment()->getCcHolderCpf(),
			'cc_holder_dob' => $this->getOnepage()->getQuote()->getPayment()->getCcHolderDob(),
			'cc_holder_phone' => $this->getOnepage()->getQuote()->getPayment()->getCcHolderPhone(),
			'save_cc' => $this->getOnepage()->getQuote()->getPayment()->getCcSave(),
		));


		$idAlreadyExists = false;
		if($this->_moip->getAnswer()->error){
			$idAlreadyExists = $this->_moip->getAnswer()->error == 'Id Próprio já foi utilizado em outra Instrução';
		}


		if (($this->_moip->getAnswer()->response || $idAlreadyExists) && !is_string($this->_moip->getAnswer())) {
			if(!$idAlreadyExists){
				$arrayData = array(
					'order_id'  => (int)$payment->getOrder()->getIncrementId(),
					'token'     => $this->_moip->getAnswer()->token,
					'accept_safe'   => $this->getOnepage()->getQuote()->getPayment()->getCcSave(),
					'data'      => serialize($this->_moip->getAnswer())
				);
				$modelResponse = Mage::getModel('moip/transactions')->setData($arrayData)->save();
			}
		}else {
			if(is_string($this->_moip->getAnswer())){
				Mage::throwException($this->_moip->getAnswer());
			}
			Mage::throwException('Erro: '.$this->_moip->getAnswer()->error);
		}

		return $this;
	}

	protected function removeJurosComposto($valor, $parcelas) {
		$juros = (float)Mage::helper('moip')->getConfig('juros_parcela')/100;

		return $valor/$parcelas/($juros/(1-(1/pow((1+$juros),(int)$parcelas))));
	}

	/**
	 * Void the payment through gateway
	 *
	 * @param  Mage_Payment_Model_Info $payment
	 * @return Monstroestudio_Moip_Model_PaymentMethod
	 */
	public function void(Varien_Object $payment) {
		$cardsStorage = $this->getCardsStorage($payment);

		$messages = array();
		$isSuccessful = false;
		$isFiled = false;

		return $this;
	}

	/**
	 * Change redirect Url
	 *
	 * @return string
	 */
	public function getOrderPlaceRedirectUrl() {
		//when you click on place order you will be redirected on this url, if you don't want this action remove this method
		return Mage::getUrl('moip/checkout/authorize', array('_secure' => true));
	}

	/**
     * Checkout redirect URL getter
     *
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {

        $method = $this->getMethodInstance();
        if ($method) {
            return $method->getCheckoutRedirectUrl();
        }
        return '';
    }

	public function assignData($data) {
		if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}

		$info = $this->getInfoInstance();
		$info->setMoipMethod($data->getMoipMethod())
		->setCcNumber($data->getCcNumber())
		->setBandeira($data->getBandeira())
		->setValidade($data->getValidade())
		->setCreditcardHolderName($data->getCreditcardHolderName())
		->setCvv($data->getCvv())
		->setSafeCvv($data->getSafeCvv())
		->setSafeParcelas($this->getOnepage()->getQuote()->getPayment()->getSafeParcelas())
		->setParcelas($data->getParcelas())
		->setCcHolderCpf($data->getCcHolderCpf())
		->setMoipSafe($data->getMoipSafe())
		->setCcHolderDob($data->getCcHolderDob())
		->setCcHolderPhone($data->getCcHolderPhone())
		->setCcSave($data->getCcSave());

		Mage::dispatchEvent('moip_sales_quote_assign_data_after', array('quote'=>$this->getOnepage()->getQuote()));

		return $this;
	}


	public function validate() {
		parent::validate();

		$info = $this->getInfoInstance();

		/*$no = $info->getCheckNo();
		$date = $info->getCheckDate();
		if (empty($no) || empty($date)) {
			$errorCode = 'invalid_data';
			$errorMsg = $this->_getHelper()->__('Check No and Date are required fields');
		}

		if ($errorMsg) {
			Mage::throwException($errorMsg);
		}*/
		return $this;
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