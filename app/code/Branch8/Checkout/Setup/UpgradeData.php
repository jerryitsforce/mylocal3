<?php

namespace Branch8\Checkout\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface{

    protected $quoteSetupFactory;

    protected $salesSetupFactory;

    protected $_urlRewriteFactory;

    public function __construct(
        QuoteSetupFactory $quoteSetupFactory,
        SalesSetupFactory $salesSetupFactory,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
    ){
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->_urlRewriteFactory = $urlRewriteFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context){
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $attributeOptions = [
                'type' => Table::TYPE_TEXT,
                'visible' => true,
                'required' => false
            ];
            $quoteSetup = $this->quoteSetupFactory->create(['setup' => $setup]);
            $quoteSetup->addAttribute('quote_item', 'source_tracking', $attributeOptions);
            $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
            $salesSetup->addAttribute('order_item', 'source_tracking', $attributeOptions);
        }
        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $attributeOptions = [
                'type' => Table::TYPE_TEXT,
                'visible' => true,
                'required' => false
            ];
            $quoteSetup = $this->quoteSetupFactory->create(['setup' => $setup]);
            $quoteSetup->addAttribute('quote_item', 'category_name', $attributeOptions);
            $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
            $salesSetup->addAttribute('order_item', 'category_name', $attributeOptions);

            $attributeIdOptions = [
                'type' => Table::TYPE_INTEGER,
                'visible' => true,
                'required' => false
            ];
            $quoteSetup->addAttribute('quote_item', 'category_id', $attributeIdOptions);
            $salesSetup->addAttribute('order_item', 'category_id', $attributeIdOptions);
            $quoteSetup->getConnection()->dropColumn('quote_item', 'source_tracking');
            $salesSetup->getConnection()->dropColumn('sales_order_item', 'source_tracking');
        }
        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $urlRewriteModel = $this->_urlRewriteFactory->create();
            $urlRewriteModel->setStoreId(1);
            $urlRewriteModel->setEntityId(0);
            $urlRewriteModel->setEntityType('custom');
            $urlRewriteModel->setRedirectType(0);
            $urlRewriteModel->setTargetPath("checkout/index/virtualFullpointCheckoutConfirmation");
            $urlRewriteModel->setRequestPath('fullpoint-checkout');
            $urlRewriteModel->save();
        }

        $setup->endSetup();
    }

}