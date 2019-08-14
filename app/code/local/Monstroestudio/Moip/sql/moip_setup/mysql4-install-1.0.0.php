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
$installer = $this;
$installer->startSetup();


// insert the product attribute to ignore boleto for some products
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$config = array(
	'position' => 1,
	'required'=> 0,
	'label' => 'Proibir Compra Por Boleto:',
	'type' => 'int',
	'input'=>'boolean',
	'apply_to'=>'simple,bundle,grouped,configurable',
	'note'=>'Proibe a compra do carrinho com boleto caso o produto esteja no carrinho'
);

$setup->addAttribute('catalog_product', 'proibir_boleto' , $config);

//insert new order status
$statusTable        = $installer->getTable('sales/order_status');
$statusStateTable   = $installer->getTable('sales/order_status_state');
$statusLabelTable   = $installer->getTable('sales/order_status_label');

$statuses = array();
$states = array();
$existingStatus = array();

foreach (Mage::getModel('sales/order_status')->getResourceCollection() as $status) {
	$existingStatus[] = $status->getStatus();
}
if (!in_array('authorized', $existingStatus)) $statuses[] = array('status' => 'authorized', 'label' => 'Autorizado');
if (!in_array('iniciado', $existingStatus)) $statuses[] = array('status' => 'iniciado', 'label' => 'Iniciado');
if (!in_array('boleto_impresso', $existingStatus)) $statuses[] = array('status' => 'boleto_impresso', 'label' => 'Boleto Impresso');
if (!in_array('concluido', $existingStatus)) $statuses[] = array('status' => 'concluido', 'label' => 'Concluido');

if (!in_array('authorized', $existingStatus))  $states[] = array('status' => 'authorized', 'state' => 'processing', 'is_default' => 1);
if (!in_array('iniciado', $existingStatus)) $states[] = array('status' => 'boleto_impresso', 'state' => 'holded', 'is_default' => 1);
if (!in_array('boleto_impresso', $existingStatus)) $states[] = array('status' => 'iniciado', 'state' => 'processing', 'is_default' => 1);
if (!in_array('concluido', $existingStatus)) $states[] = array('status' => 'concluido', 'state' => 'processing', 'is_default' => 1);

if(count($statuses)>0){
	$installer->getConnection()->insertArray($statusTable, array('status', 'label'), $statuses);
	$installer->getConnection()->insertArray($statusStateTable, array('status', 'state', 'is_default'), $states);
}



// create moip tables
$installer->run("DROP TABLE IF EXISTS {$this->getTable('moip/transactions')};");
$installer->run("DROP TABLE IF EXISTS {$this->getTable('moip/safe')};");

//Moip Transactions
$table = $installer->getConnection()
->newTable($installer->getTable('moip/transactions'))

->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('identity'  => true,'unsigned'  => true,'nullable'  => false,'primary'   => true), 'Id')
->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Order Id')
->addColumn('token', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Token')
->addColumn('accept_safe', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Accept Safe')
->addColumn('data', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(), 'Data')

->addIndex($installer->getIdxName('moip/transactions', array('order_id')), array('order_id'))

->setComment('Moip Transactions');
$installer->getConnection()->createTable($table);
// Moip Safe
$table = $installer->getConnection()
->newTable($installer->getTable('moip/safe'))

->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('identity'  => true,'unsigned'  => true,'nullable'  => false,'primary'   => true), 'Id')
->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Customer Id')
->addColumn('digits', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Digits')
->addColumn('operator', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Operator')
->addColumn('token', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Token')

->addIndex($installer->getIdxName('moip/safe', array('customer_id', 'token')), array('customer_id', 'token'))

->setComment('Moip Safe');
$installer->getConnection()->createTable($table);

$installer->endSetup();

$directory_country_region = Mage::getSingleton('core/resource')->getTableName('directory_country_region');
$directory_country_region_name = Mage::getSingleton('core/resource')->getTableName('directory_country_region_name');


$installer->run("
DELETE from `".$directory_country_region."` WHERE `country_id`='BR';
DELETE from `".$directory_country_region_name."` WHERE `locale`='BR';

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'AC', 'Acre');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Acre'), ('pt_BR', LAST_INSERT_ID(), 'Acre');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'AL', 'Alagoas');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Alagoas'), ('pt_BR', LAST_INSERT_ID(), 'Alagoas');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'AP', 'Amapá');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Amapá'), ('pt_BR', LAST_INSERT_ID(), 'Amapá');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'AM', 'Amazonas');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Amazonas'), ('pt_BR', LAST_INSERT_ID(), 'Amazonas');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'BA', 'Bahia');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Bahia'), ('pt_BR', LAST_INSERT_ID(), 'Bahia');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'CE', 'Ceará');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Ceará'), ('pt_BR', LAST_INSERT_ID(), 'Ceará');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'DF', 'Distrito Federal');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Distrito Federal'), ('pt_BR', LAST_INSERT_ID(), 'Distrito Federal');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'ES', 'Espírito Santo');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Espírito Santo'), ('pt_BR', LAST_INSERT_ID(), 'Espírito Santo');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'GO', 'Goiás');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Goiás'), ('pt_BR', LAST_INSERT_ID(), 'Goiás');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'MA', 'Maranhão');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Maranhão'), ('pt_BR', LAST_INSERT_ID(), 'Maranhão');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'MT', 'Mato Grosso');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Mato Grosso'), ('pt_BR', LAST_INSERT_ID(), 'Mato Grosso');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'MS', 'Mato Grosso do Sul');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Mato Grosso do Sul'), ('pt_BR', LAST_INSERT_ID(), 'Mato Grosso do Sul');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'MG', 'Minas Gerais');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Minas Gerais'), ('pt_BR', LAST_INSERT_ID(), 'Minas Gerais');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PA', 'Pará');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Pará'), ('pt_BR', LAST_INSERT_ID(), 'Pará');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PB', 'Paraíba');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Paraíba'), ('pt_BR', LAST_INSERT_ID(), 'Paraíba');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PR', 'Paraná');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Paraná'), ('pt_BR', LAST_INSERT_ID(), 'Paraná');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PE', 'Pernambuco');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Pernambuco'), ('pt_BR', LAST_INSERT_ID(), 'Pernambuco');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PI', 'Piauí');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Piauí'), ('pt_BR', LAST_INSERT_ID(), 'Piauí');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RJ', 'Rio de Janeiro');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Rio de Janeiro'), ('pt_BR', LAST_INSERT_ID(), 'Rio de Janeiro');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RN', 'Rio Grande do Norte');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Rio Grande do Norte'), ('pt_BR', LAST_INSERT_ID(), 'Rio Grande do Norte');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RS', 'Rio Grande do Sul');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Rio Grande do Sul'), ('pt_BR', LAST_INSERT_ID(), 'Rio Grande do Sul');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RO', 'Rondônia');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Rondônia'), ('pt_BR', LAST_INSERT_ID(), 'Rondônia');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RR', 'Roraima');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Roraima'), ('pt_BR', LAST_INSERT_ID(), 'Roraima');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'SC', 'Santa Catarina');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Santa Catarina'), ('pt_BR', LAST_INSERT_ID(), 'Santa Catarina');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'SP', 'São Paulo');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'São Paulo'), ('pt_BR', LAST_INSERT_ID(), 'São Paulo');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'SE', 'Sergipe');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Sergipe'), ('pt_BR', LAST_INSERT_ID(), 'Sergipe');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'TO', 'Tocantins');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Tocantins'), ('pt_BR', LAST_INSERT_ID(), 'Tocantins');

    ");
