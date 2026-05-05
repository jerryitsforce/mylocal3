<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Sales\Model\ResourceModel\Order\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\Order\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
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

    private bool $isJoinCalled = false;

    public function __construct(
        RequestInterface $request,
        CollectionRestrictInterface $collectionRestrict
    ) {
        $this->request = $request;
        $this->collectionRestrict = $collectionRestrict;
    }

    /*
     *
     */
    public function beforeLoad(OrderCollection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME || $this->isJoinCalled) {
            return;
        }

        $this->collectionRestrict->execute($subject);
    }
}
