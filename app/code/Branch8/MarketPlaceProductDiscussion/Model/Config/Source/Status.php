<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       16/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\Config\Source;

use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Status  implements OptionSourceInterface
{
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * ToOptionArray
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Please select status'), 'value' => ''],
            ['label' => __('Pending'), 'value' => Thread::STATUS_PENDING],
            ['label' => __('Approved'), 'value' => Thread::STATUS_APPROVED],
            ['label' => __('Reject'), 'value' => Thread::STATUS_REJECTED],
        ];
    }
}
