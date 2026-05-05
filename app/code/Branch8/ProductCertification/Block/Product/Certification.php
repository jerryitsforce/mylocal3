<?php

declare(strict_types=1);

namespace Branch8\ProductCertification\Block\Product;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Branch8\ProductCertification\Helper\Config;
use Branch8\ProductCertification\Helper\ImageUploader;
use Branch8\ProductCertification\Model\CertificationTypeRepository;
use Branch8\ProductCertification\Model\ResourceModel\ProductCertification\CollectionFactory as PcCollectionFactory;

/**
 * Frontend block for rendering product certifications on the product detail page
 */
class Certification extends Template
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var PcCollectionFactory
     */
    private PcCollectionFactory $pcCollectionFactory;

    /**
     * @var CertificationTypeRepository
     */
    private CertificationTypeRepository $repository;

    /**
     * @var ImageUploader
     */
    private ImageUploader $imageUploader;

    /**
     * @var array|null cached certification rows
     */
    private ?array $certificationRows = null;

    /**
     * @param Context $context
     * @param Config $config
     * @param Registry $registry
     * @param PcCollectionFactory $pcCollectionFactory
     * @param CertificationTypeRepository $repository
     * @param ImageUploader $imageUploader
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Registry $registry,
        PcCollectionFactory $pcCollectionFactory,
        CertificationTypeRepository $repository,
        ImageUploader $imageUploader,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config             = $config;
        $this->registry           = $registry;
        $this->pcCollectionFactory = $pcCollectionFactory;
        $this->repository         = $repository;
        $this->imageUploader      = $imageUploader;
    }

    /**
     * Whether the block should be rendered at all
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Get the current product from registry
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get the label for an empty certification value
     * @return string
     */
    public function getCompliesLabel(): string
    {
        return (string)__('Complies with standards');
    }

    /**
     * Build and return certification rows to display.
     * Each row: ['name', 'icon_url', 'value']
     *
     * Only rows that are SELECTED (exist in branch8_product_certification table) are returned.
     *
     * @return array
     */
    public function getCertificationRows(): array
    {
        if ($this->certificationRows !== null) {
            return $this->certificationRows;
        }

        $product = $this->getProduct();
        $rowId = (int)($product->getData('row_id') ?: $product->getId());

        if (!$product || !$rowId) {
            return $this->certificationRows = [];
        }

        $savedCerts = $this->pcCollectionFactory->create()
            ->addProductFilter($rowId);

        $rows = [];
        foreach ($savedCerts as $pc) {
            $typeId = (int)$pc->getData('certification_type_id');
            try {
                $type = $this->repository->getById($typeId);
            } catch (\Exception $e) {
                continue;
            }

            if (!$type->isActive()) {
                continue;
            }

            $rows[] = [
                'name'     => $type->getData('certification_name'),
                'icon_url' => $type->getData('icon')
                    ? $this->imageUploader->getIconUrl($type->getData('icon'))
                    : '',
                'value'    => (string)$pc->getData('certification_value'),
            ];
        }

        return $this->certificationRows = $rows;
    }

    /**
     * Get pre-loaded certifications for a product for the edit form
     * @param int $productId
     * @return array
     */
    public function getPreloadedData(int $productId): array
    {
        // 1. Try to get data from current Request (highest priority, for form validation error recovery)
        $productParam = $this->getRequest()->getParam('product') ?: [];
        $submittedJson = $productParam['branch8_certifications_post'] ?? '';
        if ($submittedJson) {
            $decoded = json_decode((string)$submittedJson, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (!$productId) {
            return [];
        }

        $savedCerts = $this->pcCollectionFactory->create()
            ->addProductFilter((int)$productId);

        $data = [];
        foreach ($savedCerts as $pc) {
            $data[(string)$pc->getData('certification_type_id')] = (string)$pc->getData('certification_value');
        }
        return $data;
    }

    /**
     * @return bool
     */
    public function hasCertifications(): bool
    {
        return count($this->getCertificationRows()) > 0;
    }
}
