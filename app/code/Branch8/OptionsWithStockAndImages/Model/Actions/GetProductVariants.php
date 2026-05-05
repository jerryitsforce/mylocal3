<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       06/03/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model\Actions;

use Branch8\OptionsWithStockAndImages\Api\Data\B8VariationsInterface;
use Branch8\OptionsWithStockAndImages\Api\Data\B8VariationsInterfaceFactory;
use Branch8\OptionsWithStockAndImages\Model\Actions\NormalizeCombo;
use Magento\Framework\App\ResourceConnection;

class GetProductVariants
{
    private $cached = [];
    private ResourceConnection $resourceConnection;
    private \Magento\Framework\Api\DataObjectHelper $dataObjectHelper;

    private B8VariationsInterfaceFactory $b8VariationsInterfaceFactory;

    /**
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param ResourceConnection $resourceConnection
     * @param B8VariationsInterfaceFactory $b8VariationsInterfaceFactory
     */
    public function __construct(
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        ResourceConnection                      $resourceConnection,
        B8VariationsInterfaceFactory            $b8VariationsInterfaceFactory
    )
    {
        $this->b8VariationsInterfaceFactory = $b8VariationsInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array[]|mixed
     */
    public function get(\Magento\Catalog\Model\Product $product)
    {
        if (isset($this->cached[$product->getId()])) {
            return $this->cached[$product->getId()];
        }
        $variants = [];
        $normalizeMap = [];
        foreach ($this->loadDb($product) as $variantData) {
            if (empty($variantData['comb'])) {
                continue;
            }
            /** @var B8VariationsInterface $categoryLink */
            $variantObject = $this->b8VariationsInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $variantObject,
                $variantData,
                B8VariationsInterface::class
            );
            $comb = trim($variantData['comb']);
            $variants[$comb] = $variantObject;

            // Generate normalize map
            $normalizedKey = NormalizeCombo::getNormalizedKey($comb);
            $normalizeMap[$normalizedKey] = $comb;

            // Also allow direct lookup via normalized key
            if (!isset($variants[$normalizedKey])) {
                $variants[$normalizedKey] = $variantObject;
            }
        }
        $this->cached[$product->getId()] = [
            'variations' => $variants,
            'normalize_variation_map' => $normalizeMap
        ];
        return $this->cached[$product->getId()];
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    public function execute(\Magento\Catalog\Model\Product $product): void
    {
        $extensionAttributes = $product->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }

        $variantsData = $this->get($product);
        $variants = $variantsData['variations'] ?? [];
        $normalizeMap = $variantsData['normalize_variation_map'] ?? [];

        $extensionAttributes->setVariations($variants);
        $extensionAttributes->setNormalizeVariationMap($normalizeMap);
        $product->setExtensionAttributes($extensionAttributes);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    private function loadDb(\Magento\Catalog\Model\Product $product)
    {
        $table = $this->resourceConnection->getTableName('wk_osi_variations');
        $select = $this->resourceConnection->getConnection()->select()->from($table)
            ->where('product_id = ?', $product->getRowId());
        return $this->resourceConnection->getConnection()->fetchAll($select);
    }
}
