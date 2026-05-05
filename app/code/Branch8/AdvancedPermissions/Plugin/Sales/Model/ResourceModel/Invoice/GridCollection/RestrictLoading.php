<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Sales\Model\ResourceModel\Invoice\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\RelatedOrder\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Framework\App\RequestInterface;

class RestrictLoading
{
    const SALES_ORDER_INVOICE_GRID_DATA_SOURCE = 'sales_order_invoice_grid_data_source';

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

    public function aroundGetReport(CollectionFactory $subject, \Closure $proceed, $requestName)
    {
        $result = $proceed($requestName);

        if (self::SALES_ORDER_INVOICE_GRID_DATA_SOURCE == $requestName) {
            if ($this->request->getModuleName() === self::API_MODULE_NAME) {
                return $result;
            }

            $this->collectionRestrict->execute($result);
        }

        return $result;
    }
}
