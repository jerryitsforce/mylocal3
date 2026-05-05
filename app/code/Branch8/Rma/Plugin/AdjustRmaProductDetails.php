<?php

declare(strict_types=1);

namespace Branch8\Rma\Plugin;

use Magento\Framework\Data\Collection;
use Webkul\MpRmaSystem\Model\ResourceModel\Details;

class AdjustRmaProductDetails
{
    /**
     * Plugin GetRmaProductDetails funcrion.
     *
     * @param Details $subject
     * @param Collection $collection
     * @param $rmaId
     *
     * @return Collection
     */
    public function afterGetRmaProductDetails(Details $subject, Collection $collection, $rmaId): Collection
    {
        $collection->getSelect()->group('main_table.item_id');
        return $collection;
    }
}
