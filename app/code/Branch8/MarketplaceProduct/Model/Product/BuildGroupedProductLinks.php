<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductLinkExtensionFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\GroupedProduct\Model\Product\Type\Grouped as TypeGrouped;

class BuildGroupedProductLinks
{
    /**#@+
     * Constants for keys of product link.
     */
    public const KEY_GROUPED_TYPE = 'associated';

    /**
     * @var ProductLinkInterfaceFactory
     */
    protected $productLinkFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProductLinkExtensionFactory
     */
    protected $productLinkExtensionFactory;

    /**
     * Init
     *
     * @param ProductLinkInterfaceFactory $productLinkFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductLinkExtensionFactory $productLinkExtensionFactory
     */
    public function __construct(
        ProductLinkInterfaceFactory $productLinkFactory,
        ProductRepositoryInterface $productRepository,
        ProductLinkExtensionFactory $productLinkExtensionFactory
    ) {
        $this->productLinkFactory = $productLinkFactory;
        $this->productRepository = $productRepository;
        $this->productLinkExtensionFactory = $productLinkExtensionFactory;
    }

    /**
     * Build product links to grouped product.
     *
     * @param ProductInterface $product
     * @param array $links
     *
     * @return void
     */
    public function execute(ProductInterface $product, array $links): void
    {
        if ($product->getTypeId() === TypeGrouped::TYPE_CODE && !$product->getGroupedReadonly()) {
            $links = $links[self::KEY_GROUPED_TYPE] ?? $product->getGroupedLinkData();
            if (!is_array($links)) {
                $links = [];
            }
            if ($product->getGroupedLinkData()) {
                $links = array_merge($links, $product->getGroupedLinkData());
            }
            $newLinks = [];
            $existingLinks = $product->getProductLinks();
            foreach ($links as $linkRaw) {
                /** @var \Magento\Catalog\Api\Data\ProductLinkInterface $productLink */
                $productLink = $this->productLinkFactory->create();
                if (!isset($linkRaw['id'])) {
                    continue;
                }
                $productId = $linkRaw['id'];
                if (!isset($linkRaw['qty'])) {
                    $linkRaw['qty'] = 0;
                }
                $linkedProduct = $this->productRepository->getById($productId);

                $productLink->setSku($product->getSku())
                    ->setLinkType(self::KEY_GROUPED_TYPE)
                    ->setLinkedProductSku($linkedProduct->getSku())
                    ->setLinkedProductType($linkedProduct->getTypeId())
                    ->setPosition($linkRaw['position'])
                    ->getExtensionAttributes()
                    ->setQty($linkRaw['qty']);
                if (isset($linkRaw['custom_attributes'])) {
                    $productLinkExtension = $productLink->getExtensionAttributes();
                    if ($productLinkExtension === null) {
                        $productLinkExtension = $this->productLinkExtensionFactory->create();
                    }
                    foreach ($linkRaw['custom_attributes'] as $option) {
                        $name = $option['attribute_code'];
                        $value = $option['value'];
                        $setterName = 'set' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($name);
                        // Check if setter exists
                        if (method_exists($productLinkExtension, $setterName)) {
                            call_user_func([$productLinkExtension, $setterName], $value);
                        }
                    }
                    $productLink->setExtensionAttributes($productLinkExtension);
                }
                $newLinks[] = $productLink;
            }

            $existingLinks = $this->removeUnExistingLinks($existingLinks, $newLinks);
            $product->setProductLinks(array_merge($existingLinks, $newLinks));
        }
    }

    /**
     * Removes unexisting links
     *
     * @param array $existingLinks
     * @param array $newLinks
     * @return array
     */
    private function removeUnExistingLinks($existingLinks, $newLinks)
    {
        $result = [];
        foreach ($existingLinks as $key => $link) {
            $result[$key] = $link;
            if ($link->getLinkType() == self::KEY_GROUPED_TYPE) {
                $exists = false;
                foreach ($newLinks as $newLink) {
                    if ($link->getLinkedProductSku() == $newLink->getLinkedProductSku()) {
                        $exists = true;
                    }
                }
                if (!$exists) {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }
}
