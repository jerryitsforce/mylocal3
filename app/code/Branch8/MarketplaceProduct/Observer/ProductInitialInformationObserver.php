<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Observer;

use Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface;
use Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterfaceFactory;
use Branch8\MarketplaceProduct\Model\ProductInitialInformationRepository;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped as TypeGrouped;
use \Psr\Log\LoggerInterface;
class ProductInitialInformationObserver implements ObserverInterface
{
    /**
     * @var ProductInitialInformationRepository
     */
    private ProductInitialInformationRepository $productInitialInfoRepository;

    /**
     * @var ProductInitialInformationInterfaceFactory
     */
    private ProductInitialInformationInterfaceFactory $productInitialInfoFactory;

    public \Psr\Log\LoggerInterface $logger;
    /**
     * ProductInitialInformationObserver constructor.
     *
     * @param ProductInitialInformationRepository $productInitialInfoRepository
     * @param ProductInitialInformationInterfaceFactory $productInitialInfoFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductInitialInformationRepository       $productInitialInfoRepository,
        ProductInitialInformationInterfaceFactory $productInitialInfoFactory,
        LoggerInterface $logger
    ) {
        $this->productInitialInfoRepository = $productInitialInfoRepository;
        $this->productInitialInfoFactory = $productInitialInfoFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        if ($product->getOrigData('entity_id') !== null) {
            return;
        }

        // Check if Product Initial Info already exists
        try {
            if ($this->productInitialInfoRepository->get((int)$product->getId())) {
                return;
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->info($e->getMessage(). ' Product ID: ' . $product->getId().' is new, saving initial information.');
        }

        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $this->saveChildProductsInitialInfo($product);
        } else {
            $this->saveProductInitialInfo($product);
        }
    }

    /**
     * Save initial information.
     *
     * @param Product $product
     *
     * @return void
     */
    private function saveProductInitialInfo(Product $product): void
    {
        $cost = (float)$product->getData(ProductInitialInformationInterface::COST);
        $specialPrice = (float)$product->getSpecialPrice();
        $productInitialInfo = $this->productInitialInfoFactory->create();
        $productInitialInfo->setProductId((int)$product->getId());
        $productInitialInfo->setPrice((float)$product->getPrice());
        $productInitialInfo->setSpecialPrice((float)$specialPrice);
        $productInitialInfo->setCost($cost);


//        $priceToCalc = (float)$product->getPrice();
//        if($specialPrice != 0){
        //now special price is required
            $priceToCalc = $specialPrice;
//        }

        if(in_array($product->getTypeId(), [
            TypeGrouped::TYPE_CODE,
            \Magento\Bundle\Model\Product\Type::TYPE_CODE,
            \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
        ]) || $priceToCalc == 0){
            $productInitialInfo->setInitGrossProfit(0);
        }else {
            $commissionRate = ($priceToCalc - $cost)/$priceToCalc*100;
            $productInitialInfo->setInitGrossProfit($commissionRate);
        }

        try {
            $this->productInitialInfoRepository->save($productInitialInfo);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * Save initial information for child products.
     *
     * @param Product $product
     *
     * @return void
     */
    private function saveChildProductsInitialInfo(Product $product): void
    {
        /** @var Configurable $productType */
        $productType = $product->getTypeInstance();
        $childProducts = $productType->getUsedProducts($product);

        // Check if there are child products
        if (empty($childProducts)) {
            return;
        }
        /** @var Product $childProduct */
        foreach ($childProducts as $childProduct) {
            try {
                $this->productInitialInfoRepository->get((int)$childProduct->getId());
            } catch (NoSuchEntityException $e) {
                $this->saveProductInitialInfo($childProduct);
            }
        }
    }
}
