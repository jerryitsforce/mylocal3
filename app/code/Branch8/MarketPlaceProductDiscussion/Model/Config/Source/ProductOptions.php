<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       18/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\Config\Source;

use Branch8\MarketPlaceProductDiscussion\Model\Actions\GetCustomerOptions;
use Branch8\MarketPlaceProductDiscussion\Model\Actions\GetProductOptions;
use Magento\Framework\Data\OptionSourceInterface;

class ProductOptions implements OptionSourceInterface
{
    private GetProductOptions $getProductOptions;

    /**
     * @param GetProductOptions $getCustomerOptions
     */
    public function __construct(
        GetProductOptions $getCustomerOptions
    )
    {
        $this->getProductOptions = $getCustomerOptions;
    }

    /**
     * ToOptionArray
     * @return array[]
     */
    public function toOptionArray()
    {
        return $this->getProductOptions->get()['options'];
    }
}
