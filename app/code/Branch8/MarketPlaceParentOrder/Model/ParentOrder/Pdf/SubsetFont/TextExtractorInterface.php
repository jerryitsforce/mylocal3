<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       05/03/2026
 */

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf\SubsetFont;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

interface TextExtractorInterface
{
    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function extract(ParentOrder $parentOrder);
}
