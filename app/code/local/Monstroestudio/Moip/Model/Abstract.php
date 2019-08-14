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
abstract class Monstroestudio_Moip_Model_Abstract extends Mage_Core_Model_Abstract
{
    /**
     * Store rule combine conditions model
     *
     * @var Mage_Rule_Model_Condition_Combine
     */
    protected $_conditions;

    /**
     * Store rule form instance
     *
     * @var Varien_Data_Form
     */
    protected $_form;

    /**
     * Is model can be deleted flag
     *
     * @var bool
     */
    protected $_isDeleteable = true;

    /**
     * Is model readonly
     *
     * @var bool
     */
    protected $_isReadonly = false;


    /**
     * Prepare data before saving
     *
     * @return Mage_Rule_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        return $this;
    }

    protected function _prepareWebsiteIds()
    {
        return $this;
    }
}
