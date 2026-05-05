<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Api;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface ParentOrderRepositoryInterface
{
    /**
     * @param int $id
     * @return ParentOrderInterface
     * @throw \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $id): ParentOrderInterface;

    /**
     * @param ParentOrderInterface $parentOrder
     * @return ParentOrderInterface
     * @throw \Exception
     */
    public function save(ParentOrderInterface $parentOrder);
}
