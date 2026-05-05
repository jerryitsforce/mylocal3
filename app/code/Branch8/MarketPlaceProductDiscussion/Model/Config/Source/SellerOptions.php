<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       18/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\Config\Source;

use Branch8\MarketPlaceProductDiscussion\Model\Actions\GetSellerOptions;
use Branch8\MarketPlaceProductDiscussion\Model\Actions\GetProductOptions;
use Magento\Framework\Data\OptionSourceInterface;

class SellerOptions implements OptionSourceInterface
{
    private GetSellerOptions $getSellerOptions;

    /**
     * @param GetSellerOptions $getCustomerOptions
     */
    public function __construct(
        GetSellerOptions $getCustomerOptions
    )
    {
        $this->getSellerOptions = $getCustomerOptions;
    }

    /**
     * ToOptionArray
     * @return array[]
     */
    public function toOptionArray()
    {
        return $this->getSellerOptions->get()['options'];
    }
}
