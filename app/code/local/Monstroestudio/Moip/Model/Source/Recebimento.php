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
class Monstroestudio_Moip_Model_Source_Recebimento {
	public function toOptionArray() {
		return array(
			array('value' => 'Parcelado', 'label' => 'Sim'),
			array('value' => 'AVista', 'label'    => 'Não'),
		);
	}
}