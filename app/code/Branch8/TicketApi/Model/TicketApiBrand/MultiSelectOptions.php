<?php

namespace Branch8\TicketApi\Model\TicketApiBrand;

use Branch8\TicketApi\Model\TicketApiBrand;
use Branch8\TicketApi\Model\TicketApiBrandRepository;

class MultiSelectOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /** @var TicketApiBrandRepository */
    private $repository;

    public function __construct(
        TicketApiBrandRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function toOptionArray()
    {
        $returnArray = [];
        $collection  = $this->repository->getActiveBrand();

        /** @var TicketApiBrand $brand */
        foreach ($collection->getItems() as $brand) {
            $returnArray[] = [
                "label" => $brand->getBrandName(),
                "value" => $brand->getBrandId()
            ];
        }

        return $returnArray;
    }
}
