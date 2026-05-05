<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Ui\Component\Listing\Column\Product;

use Magento\Staging\Ui\Component\Listing\Column\Entity\UrlProviderInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\Url;

class UrlProvider implements UrlProviderInterface
{
    /**
     * @var Url
     */
    private $frontendUrlBuilder;

    /**
     * @param Url $frontendUrlBuilder
     */
    public function __construct(
        Url $frontendUrlBuilder
    ) {
        $this->frontendUrlBuilder = $frontendUrlBuilder;
    }

    /**
     * Get URL for data provider item
     *
     * @param array $item
     * @return string
     */
    public function getUrl(array $item)
    {
        return $this->frontendUrlBuilder->getUrl(
            'catalog/product/view',
            [
                'id' => $item['entity_id'],
                '_nosid' => true
            ]
        );
    }

    /**
     * Get URL for data provider item
     *
     * @param array $item
     * @return string
     */
    public function getRemoveUrl(array $item)
    {
        return $this->frontendUrlBuilder->getUrl(
            'catalog/product/view',
            [
                'id' => $item['entity_id'],
                '_nosid' => true
            ]
        );
    }
}
