<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\SalesOrderGrid\Ui\Component\Listing\Column\Status;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

/**
 * Class Options for Listing Column Status
 */
class Options extends \Magento\Sales\Ui\Component\Listing\Column\Status\Options
{
    private \Branch8\Sales\Helper\Config $helper;

    /**
     * @param \Branch8\Sales\Helper\Config $helper
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        \Branch8\Sales\Helper\Config $helper,
        CollectionFactory            $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->helper = $helper;
        parent::__construct($collectionFactory);
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $statuses = $this->helper->getSpecificStatusesForFilter();
            $options = $this->collectionFactory->create()->addFieldToFilter('status',
                ['in' => $statuses])->toOptionArray();
            $this->options = $options;
        }
        return $this->options;
    }
}
