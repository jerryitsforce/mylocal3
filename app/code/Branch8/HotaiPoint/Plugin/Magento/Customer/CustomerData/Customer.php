<?php
/**
 * CLEARgo
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the CLEARgo.com license that is
 * available through the world-wide-web at this URL:
 * http://cleargo.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category   CLEARgo
 * @package    Cleargo_RewardPoints
 * @copyright  Copyright (c) 2016 CLEARgo (http://www.cleargo.com/)
 * @license    http://www.cleargo.com/LICENSE-1.0.html
 */

namespace Branch8\HotaiPoint\Plugin\Magento\Customer\CustomerData;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Data as HotaiPointHelper;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

class Customer
{
    /**
     * @var ApiHelper
     */
    protected ApiHelper $apiHelper;

    /**
     * @var HotaiPointHelper
     */
    protected HotaiPointHelper $hotaiPointHelper;

    /**
     * @var HttpContext
     */
    protected HttpContext $httpContext;

    /**
     * @var OrderCollectionFactory
     */
    protected OrderCollectionFactory $orderCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected TimezoneInterface $timezone;

    /**
     * @var mixed
     */
    protected mixed $pointUser = null;
    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    /**
     * @param ApiHelper $apiHelper
     * @param HotaiPointHelper $hotaiPointHelper
     * @param HttpContext $httpContext
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param TimezoneInterface $timezone
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     * @param HelperData $subAccountHelper
     */
	public function __construct(
        ApiHelper $apiHelper,
        HotaiPointHelper $hotaiPointHelper,
        HttpContext $httpContext,
        OrderCollectionFactory $orderCollectionFactory,
        TimezoneInterface $timezone,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        HelperData $subAccountHelper
	) {
        $this->apiHelper = $apiHelper;
        $this->hotaiPointHelper = $hotaiPointHelper;
        $this->httpContext = $httpContext;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->timezone = $timezone;
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->subAccountHelper = $subAccountHelper;
	}

    /**
     * @param \Magento\Customer\CustomerData\Customer $subject
     * @param $result
     * @return mixed
     */
    public function afterGetSectionData(\Magento\Customer\CustomerData\Customer $subject, $result)
    {
        $hour = $this->timezone->date()->format('G');
        if (
            $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)
            && !$this->b8CustomerHelper->isSeller()
            && !$this->b8CustomerHelper->isWaitForSeller()
            && !$this->subAccountHelper->isSubAccount()
        ) {
            $greeting = __("Good night").", ";
            if ($hour < 12) {
                $greeting = __("Good morning").", ";
            } else if ($hour < 18) {
                $greeting = __("Good afternoon").", ";
            } else if ($hour < 21) {
                $greeting = __("Good evening").", ";
            }
        } else {
            $greeting = __("Good night").", ".__("visitor")."<br>".__("Log in to view your Hotai Points!");
            if ($hour < 12) {
                $greeting = __("Good morning").", ".__("visitor")."<br>".__("Log in to view your Hotai Points!");
            } else if ($hour < 18) {
                $greeting = __("Good afternoon").", ".__("visitor")."<br>".__("Log in to view your Hotai Points!");
            } else if ($hour < 21) {
                $greeting = __("Good evening").", ".__("visitor")."<br>".__("Log in to view your Hotai Points!");
            }
        }
        $result['greeting'] = $greeting;
        return $result;
    }
}
