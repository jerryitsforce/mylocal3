<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Branch8\ProductCertification\Model\ResourceModel\ProductCertification as ResourceModel;
use Branch8\ProductCertification\Model\ProductCertificationFactory;
use Branch8\ProductCertification\Model\ResourceModel\ProductCertification\CollectionFactory;
use Magento\Framework\App\RequestInterface;

/**
 * Observer: catalog_product_save_after
 * Persist the certification selections (submitted via form) to branch8_product_certification table
 */
class SaveProductCertifications implements ObserverInterface
{
    /**
     * @var ResourceModel
     */
    private ResourceModel $resourceModel;

    /**
     * @var ProductCertificationFactory
     */
    private ProductCertificationFactory $certFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @param ResourceModel $resourceModel
     * @param ProductCertificationFactory $certFactory
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     */
    public function __construct(
        ResourceModel $resourceModel,
        ProductCertificationFactory $certFactory,
        CollectionFactory $collectionFactory,
        RequestInterface $request
    ) {
        $this->resourceModel     = $resourceModel;
        $this->certFactory       = $certFactory;
        $this->collectionFactory = $collectionFactory;
        $this->request           = $request;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product   = $observer->getEvent()->getProduct();
        // Use row_id for Enterprise Staging compatibility, fallback to entity_id
        $productId = (int)($product->getData('row_id') ?: $product->getId());

        if (!$productId) {
            return;
        }

        // Get certification data from the request (nested under "product", "staging", root) or from the product object itself
        $params   = $this->request->getParam('product') ?? [];
        $stagingParams = $this->request->getParam('staging') ?? [];
        $rawJson  = $params['branch8_certifications_post'] 
            ?? $stagingParams['branch8_certifications_post'] 
            ?? $this->request->getParam('branch8_certifications_post') 
            ?? $product->getData('branch8_certifications_post') 
            ?? '';

        // Decode the JSON string from the KnockoutJS panel
        $certData = $rawJson ? json_decode((string)$rawJson, true) : [];

        if (!is_array($certData)) {
            return;
        }

        // 1. Delete all existing records for this product first (atomic update)
        $connection = $this->resourceModel->getConnection();
        $connection->delete(
            $this->resourceModel->getMainTable(),
            ['product_id = ?' => $productId]
        );

        // 2. Re-insert selected certifications
        $insertData = [];
        foreach ($certData as $typeId => $value) {
            $typeId = (int)$typeId;
            if (!$typeId) {
                continue;
            }

            $insertData[] = [
                'product_id'            => $productId,
                'certification_type_id' => $typeId,
                'certification_value'   => (string)$value,
            ];
        }

        if (!empty($insertData)) {
            try {
                $connection->insertMultiple(
                    $this->resourceModel->getMainTable(),
                    $insertData
                );
            } catch (\Exception $e) {
                // Ignore DB constraint violations (like invalid certification_type_id)
                // without breaking the outer product save transaction nesting level.
            }
        }
    }
}
