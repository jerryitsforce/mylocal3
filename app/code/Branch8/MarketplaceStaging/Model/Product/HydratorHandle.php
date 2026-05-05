<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Model\Product;

use Branch8\MarketplaceStaging\Model\Product\Hydrator as MarketplaceStagingHydrator;
use Magento\CatalogStaging\Model\Product\Hydrator as CatalogStagingHydrator;
use Magento\Staging\Model\Entity\HydratorInterface;

class HydratorHandle implements HydratorInterface
{
    /**
     * @var CatalogStagingHydrator
     */
    private CatalogStagingHydrator $catalogHydrator;

    /**
     * @var MarketplaceStagingHydrator
     */
    private MarketplaceStagingHydrator $marketplaceHydrator;

    /**
     * @param CatalogStagingHydrator $catalogHydrator
     * @param MarketplaceStagingHydrator $marketplaceHydrator
     */
    public function __construct(
        CatalogStagingHydrator $catalogHydrator,
        MarketplaceStagingHydrator $marketplaceHydrator
    ) {
        $this->catalogHydrator = $catalogHydrator;
        $this->marketplaceHydrator = $marketplaceHydrator;
    }

    public function hydrate(array $data)
    {
        if (isset($data['product_id'])) {
            return $this->marketplaceHydrator->hydrate($data);
        }
        return $this->catalogHydrator->hydrate($data);
    }
}
