<?php
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Model\Variations;

use Branch8\OptionsWithStockAndImages\Model\Actions\GetProductVariants;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;

/**
 * Class ReadHandler
 */
class ReadHandler implements ExtensionInterface
{
    private GetProductVariants $getProductVariants;

    /**
     * @param GetProductVariants $getProductVariants
     */
    public function __construct(
        GetProductVariants $getProductVariants
    ) {
        $this->getProductVariants = $getProductVariants;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $entity
     * @param array $arguments
     * @return \Magento\Catalog\Api\Data\ProductInterface|object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        $this->getProductVariants->execute($entity);
        return $entity;
    }
}
