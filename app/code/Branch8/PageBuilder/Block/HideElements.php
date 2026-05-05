<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\PageBuilder\Block;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * Class HideElements
 */
class HideElements extends Template
{
    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * HideElements constructor.
     * @param Context $context
     * @param HttpContext $httpContext
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        HttpContext $httpContext,
        OrderCollectionFactory $orderCollectionFactory,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Returns show/hide elements
     *
     * @return bool
     * @api
     */
    public function hideElements(): bool
    {
        $result = false;
        if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection->addFieldToFilter('customer_id', $this->httpContext->getValue('customer_id'));
            if ($orderCollection->getSize() > 0) {
                $result = true;
            }
        }
        return $result;
    }
}
