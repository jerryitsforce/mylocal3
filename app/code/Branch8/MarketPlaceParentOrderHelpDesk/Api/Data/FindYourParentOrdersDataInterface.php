<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Find Your Order Interface
 */
interface FindYourParentOrdersDataInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $value
     * @return FindYourParentOrdersDataInterface
     */
    public function setId(string $value);

    /**
     * @return string
     */
    public function getIncrementId();

    /**
     * @param string $value
     * @return FindYourParentOrdersDataInterface
     */
    public function setIncrementId(string $value);

}
