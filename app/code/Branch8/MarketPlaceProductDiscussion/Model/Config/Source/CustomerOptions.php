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
use Magento\Framework\Data\OptionSourceInterface;

class CustomerOptions implements OptionSourceInterface
{
    private GetCustomerOptions $getCustomerOptions;

    /**
     * @param GetCustomerOptions $getCustomerOptions
     */
    public function __construct(
        GetCustomerOptions $getCustomerOptions
    )
    {
        $this->getCustomerOptions = $getCustomerOptions;
    }

    /**
     * ToOptionArray
     * @return array[]
     */
    public function toOptionArray()
    {
        return $this->getCustomerOptions->get()['options'];
    }
}
