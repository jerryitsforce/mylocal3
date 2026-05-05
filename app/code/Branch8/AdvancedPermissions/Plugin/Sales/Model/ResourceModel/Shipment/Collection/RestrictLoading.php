<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Sales\Model\ResourceModel\Shipment\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\RelatedOrder\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use Magento\Framework\App\RequestInterface;

class RestrictLoading
{
    private const API_MODULE_NAME = 'api';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CollectionRestrictInterface
     */
    private $collectionRestrict;

    public function __construct(
        RequestInterface $request,
        CollectionRestrictInterface $collectionRestrict
    ) {
        $this->request = $request;
        $this->collectionRestrict = $collectionRestrict;
    }

    public function beforeLoad(ShipmentCollection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME) {
            return;
        }

        $this->collectionRestrict->execute($subject);
    }
}
