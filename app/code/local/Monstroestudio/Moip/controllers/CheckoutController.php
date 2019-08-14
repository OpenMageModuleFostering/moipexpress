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
class Monstroestudio_Moip_CheckoutController extends Mage_Core_Controller_Front_Action
{
	/**
     *  Página de sucesso do módulo
     */
	public function AuthorizeAction(){
        $this->loadLayout();
        $this->renderLayout();
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

	/**
     *  Retorno do MoIP
     */
    public function updateAction(){
    	if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
			$transaction_id = preg_replace('/[^0-9,.\-_]/', '', $data['id_transacao']);
			$order = Mage::getModel('sales/order')->loadByIncrementId($transaction_id);
			$customer = $order->getCustomerId();
			$safeModel = Mage::getModel('moip/safe');

			if(isset($data['cofre']) && strlen($data['cofre']) > 4){
				if(!$safeModel->loadBySafe($data['cofre']) && (bool)Mage::getModel('moip/transactions')->loadByOrder($transaction_id)->getAcceptSafe()){
					$safeData = array(
						'customer_id' => $customer,
						'token'       => $data['cofre'],
						'digits'      => $data['cartao_final'],
						'operator'    => $data['cartao_bandeira']
					);

					$safeModel->setData($safeData)->save();
				}
			}

			$isOrderAlreadyAuthorized = ($order->getStatus() == 'authorized' || $order->getStatus() == 'closed' || $order->getStatus() == 'complete');

			switch ((int)$data['status_pagamento']){
				case 1:
					if($isOrderAlreadyAuthorized){
						return false;
					}
					$order_status = 'authorized';
					$order_state = Mage_Sales_Model_Order::STATE_PROCESSING;
				break;
				case 2:
					if($isOrderAlreadyAuthorized){
						return false;
					}
					$order_status = 'iniciado';
					$order_state = Mage_Sales_Model_Order::STATE_HOLDED;
				break;
				case 3:
					if($isOrderAlreadyAuthorized){
						return false;
					}
					$order_status = 'boleto_impresso';
					$order_state = Mage_Sales_Model_Order::STATE_HOLDED;
				break;
				case 4:
					$order_status = 'concluido';
					$order_state = Mage_Sales_Model_Order::STATE_PROCESSING;
				break;
				case 5:
					if($isOrderAlreadyAuthorized){
						return false;
					}
					$order_status = 'canceled';
					$order_state = Mage_Sales_Model_Order::STATE_CANCELED;
				break;
				case 6:
					if($isOrderAlreadyAuthorized){
						return false;
					}
					$order_status = 'payment_review';
					$order_state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
				break;
				case 7:
					//reembolso
					$order_status = 'closed';
					$order_state = Mage_Sales_Model_Order::CLOSED;
					return false;
				break;
				case 9:
					//reembolso
					$order_status = 'closed';
					$order_state = Mage_Sales_Model_Order::CLOSED;
					return false;
				break;

			}
			$comment = '';
			if($order_status !== 'closed'){
				$order->setState($order_state, $order_status, $comment, $notified = true, $includeComment = false)->save();
				if($order_status == 'authorized'){
					$this->generateInvoice($order);
				}
			}

    	}
    }

    /**
     * generateInvoice function.
     *
     * @access protected
     * @param Mage_Sales_Model_Order $order
     * @return void
     */
    protected function generateInvoice(Mage_Sales_Model_Order $order){
		$status = $order->getStatus();
		$state = $order->getState();
		if (!$order->canInvoice()) {
			$order->addStatusHistoryComment('Pedido não pode ser faturado.', false);
			$order->save();
			return false;
		}

		$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
		$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
		$invoice->register();
		$invoice->getOrder()->setCustomerNoteNotify(false);
		$invoice->getOrder()->setIsInProcess(true);
		$order->addStatusHistoryComment('Pagamento realizado e autorizado pela solução de pagamentos MOIP.', false);
		$transactionSave = Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder());
		$transactionSave->save();

		$order->setState($state, $status, '', $notified = true, $includeComment = false);
		$order->save();
    }

}