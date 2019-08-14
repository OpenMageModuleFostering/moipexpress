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
class Monstroestudio_Moip_Model_Safe extends Monstroestudio_Moip_Model_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->_init('moip/safe');
        $this->setIdFieldName('id');

	}

	public function loadByCustomer($id){
		if($id == null){
		    return false;
	    }

		$collection = $this->getCollection()->addFilter('customer_id', array('eq' => $id))->load();

	    if(count($collection) > 0){
		    return $collection;
	    }else{
		    return false;
	    }
	}

	public function loadBySafe($token){
		if($token == null){
		    return false;
	    }

		$collection = $this->getCollection()->addFilter('token', array('eq' => $token))->load();

	    if(count($collection) > 0){
		    return $collection;
	    }else{
		    return false;
	    }
	}


}
