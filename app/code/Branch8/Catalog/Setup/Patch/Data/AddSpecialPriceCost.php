<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Webkul\Marketplace\Model\SaleperpartnerFactory;

class AddSpecialPriceCost implements DataPatchInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    private $productCollectionFactory;

    private $marketplaceHelper;

    private $mpSalesPartner;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Webkul\Marketplace\Helper\Data $marketplaceHelper,
        SaleperpartnerFactory $mpSalesPartner
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->mpSalesPartner = $mpSalesPartner;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        //null special price
        $productSpecialPriceNullCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('price')
            ->addAttributeToFilter('type_id', ['in' => ['simple', 'virtual', 'downloadable']])
            ->addAttributeToFilter('special_price', ['null' => true]);
        foreach($productSpecialPriceNullCollection as $_nullSpecialPrice){
            $_nullSpecialPrice->setSpecialPrice($_nullSpecialPrice->getPrice());
            $_nullSpecialPrice->getResource()->saveAttribute($_nullSpecialPrice, 'special_price');
        }
        //empty special price
        $productSpecialPriceEmptyCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('price')
            ->addAttributeToFilter('type_id', ['in' => ['simple', 'virtual', 'downloadable']])
            ->addAttributeToFilter('special_price', '');
        foreach($productSpecialPriceEmptyCollection as $_emptySpecialPrice){
            $_emptySpecialPrice->setSpecialPrice($_emptySpecialPrice->getPrice());
            $_emptySpecialPrice->getResource()->saveAttribute($_emptySpecialPrice, 'special_price');
        }

        //calculator cost for empty
        $productCostNullCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('special_price')
            ->addAttributeToFilter('type_id', ['in' => ['simple', 'virtual', 'downloadable']])
            ->addAttributeToFilter('cost', ['null' => true]);
        $this->processCost($productCostNullCollection);

        $productCostEmptyCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('special_price')
            ->addAttributeToFilter('type_id', ['in' => ['simple', 'virtual', 'downloadable']])
            ->addAttributeToFilter('cost', '');
        $this->processCost($productCostEmptyCollection);

        $this->moduleDataSetup->getConnection()->endSetup();
    }


    private function processCost($collection){
        $sellerCommissionRate = [];
        foreach($collection as $_nullCost){
            $pid = $_nullCost->getId();
            $sellerId = $this->marketplaceHelper->getSellerIdByProductId($pid);
            //get default commission
            if(!isset($sellerCommissionRate[$sellerId])){
                $partner = $this->mpSalesPartner->create()
                    ->getCollection()
                    ->addFieldToSelect('entity_id')
                    ->addFieldToSelect('commission_rate')
                    ->addFieldToFilter('seller_id', $sellerId)
                    ->getFirstItem();
                if($partner->getData('commission_rate') == null){
                    $this->marketplaceHelper->getConfigCommissionRate();
                }
                $sellerCommissionRate[$sellerId] = $partner->getData('commission_rate');
                unset($partner);
            }
            $commissionRate = $sellerCommissionRate[$sellerId];

            $_nullCost->setCostSetting(\Branch8\Catalog\Model\Source\CostSetting::FIXED);
            $_nullCost->setCommissionPercent($commissionRate);
            $costValue = $_nullCost->getSpecialPrice() * (100 - $commissionRate)/100;
            $_nullCost->setCost($costValue);

//            if($_nullCost->getId() == 24){
//                var_dump($commissionRate, $costValue);die;
//            }

            $_nullCost->getResource()->saveAttribute($_nullCost, 'cost');
            $_nullCost->getResource()->saveAttribute($_nullCost, 'commission_percent');
            $_nullCost->getResource()->saveAttribute($_nullCost, 'cost_setting');

        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
        
        ];
    }
}

