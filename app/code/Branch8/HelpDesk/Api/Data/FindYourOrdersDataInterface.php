<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Find Your Order Interface
 */
interface FindYourOrdersDataInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $value
     * @return FindYourOrdersDataInterface
     */
    public function setId(string $value);

    /**
     * @return string
     */
    public function getIncrementId();

    /**
     * @param string $value
     * @return FindYourOrdersDataInterface
     */
    public function setIncrementId(string $value);

}
