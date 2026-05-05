<?php

namespace Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicketOverDue;

use Magento\Framework\ObjectManagerInterface;

class CollectionFactory
{
    /** @var ObjectManagerInterface */
    private ObjectManagerInterface $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create CustomerTicketOverDue collection instance.
     *
     * @param array $data
     * @return Collection
     */
    public function create(array $data = []): Collection
    {
        return $this->objectManager->create(Collection::class, $data);
    }
}

