<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Setup\Patch\Data;

use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as MpProductCollection;

class MigrateTempData implements DataPatchInterface
{

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var ProductTempDataRepositoryInterface
     */
    protected $productTempDataRepository;

    /**
     * @var CollectionFactory
     */
    protected $productTempCollectionFactory;

    /**
     * @var MpProductCollection
     */
    protected $mpProductCollectionFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param ProductTempDataRepositoryInterface $productTempDataRepository
     * @param CollectionFactory $productTempCollectionFactory
     * @param MpProductCollection $mpProductCollectionFactory
     * @param Json|null $serializer
     */
    public function __construct(
        ProductTempDataRepositoryInterface $productTempDataRepository,
        CollectionFactory $productTempCollectionFactory,
        MpProductCollection $mpProductCollectionFactory,
        Json $serializer = null
    ) {
        $this->productTempDataRepository = $productTempDataRepository;
        $this->productTempCollectionFactory = $productTempCollectionFactory;
        $this->mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(Json::class);
    }
    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->updateTempData();
    }

    private function updateTempData()
    {
        $collection = $this->productTempCollectionFactory->create();
        $collection->addFieldToSelect('*');
        $collection->addFieldToFilter('sku', '');
        if ($collection->getSize() > 0) {
            foreach ($collection as $productTempDuplicateData) {
                try {
                    $wholeData = $this->serializer->unserialize($productTempDuplicateData->getInformation());
                    $productId = (int)$wholeData['product_id'] ?? 0;
                    $sellerId = $this->getSellerIdByProductId($productId);
                    $status = (isset($wholeData['status']) && $wholeData['status']) ? (int)$wholeData['status'] : SellerProduct::STATUS_ENABLED;
                    $productTempDuplicateData->setSellerId((int)$sellerId ?? 0);
                    $productTempDuplicateData->setProductId($productId);
                    $productTempDuplicateData->setStatus($status);
                    $productTempDuplicateData->setThumbnail($wholeData['product']['thumbnail'] ?? '');
                    $productTempDuplicateData->setName($wholeData['product']['name'] ?? '');
                    $productTempDuplicateData->setType($wholeData['type'] ?? '');
                    $productTempDuplicateData->setSku($wholeData['product']['sku'] ?? '');
                    $productTempDuplicateData->setQuantity(isset($wholeData['product']['quantity_and_stock_status']['qty']) ? (float)$wholeData['product']['quantity_and_stock_status']['qty'] : (float)'0');
                    $productTempDuplicateData->setCost(isset($wholeData['product']['cost']) ? (float)$wholeData['product']['cost'] : (float)'0');
                    $productTempDuplicateData->setPrice(isset($wholeData['product']['price']) ? (float)$wholeData['product']['price'] : (float)'0');
                    $productTempDuplicateData->setSpecialPrice(isset($wholeData['product']['special_price']) ? (float)$wholeData['product']['special_price'] : (float)'0');
                    $this->productTempDataRepository->save($productTempDuplicateData);
                }catch (\Exception $e){
                    continue;
                }
            }
        }
    }

    /**
     * Return the seller Id by product id.
     *
     * @param int $productId
     * @return int||null
     */
    public function getSellerIdByProductId($productId = '')
    {
        $collection = $this->mpProductCollectionFactory->create();
        $collection->addFieldToFilter('mageproduct_id', $productId);
        return $collection->getFirstItem()->getSellerId();
    }

    /**
     * Get aliases
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Get dependencies
     */
    public static function getDependencies()
    {
        return [];
    }
}
