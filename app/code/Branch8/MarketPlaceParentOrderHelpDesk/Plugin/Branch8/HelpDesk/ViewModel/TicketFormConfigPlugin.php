<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Plugin\Branch8\HelpDesk\ViewModel;

use Branch8\MarketPlaceParentOrderHelpDesk\Model\FindYourOrderAction;
use Branch8\MarketPlaceParentOrderHelpDesk\Model\FindYourParentOrderAction;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;

/**
 *
 */
class TicketFormConfigPlugin
{
    const XML_PATH_DISABLE_SUB_ORDERS_ACCESS = 'parent_order/sub_order_configuration/access';

    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var Session\Proxy
     */
    private Session\Proxy $customerSession;
    private FindYourParentOrderAction $findYourOrderAction;
    private \Branch8\MarketPlaceParentOrderHelpDesk\Model\Config $config;

    /**
     * @param \Branch8\MarketPlaceParentOrderHelpDesk\Model\Config $config
     * @param UrlInterface $url
     * @param Session\Proxy $customerSession
     * @param FindYourParentOrderAction $findYourOrderAction
     */
    public function __construct(
        \Branch8\MarketPlaceParentOrderHelpDesk\Model\Config $config,
        UrlInterface                                         $url,
        Session\Proxy                                        $customerSession,
        FindYourParentOrderAction                            $findYourOrderAction
    )
    {
        $this->findYourOrderAction = $findYourOrderAction;
        $this->customerSession = $customerSession;
        $this->url = $url;
        $this->config = $config;
    }

    /**
     * @param $subject
     * @param callable $process
     * @param ...$args
     * @return array
     */
    public function aroundGetFindYourOrderConfig($subject, callable $process, ...$args)
    {
        $isDisable = $this->config->isDisableNativeOrderUi();
        if (!$isDisable) {
            return $process($args);
        }
        $recentlyOrders = [];
        $result = $this->findYourOrderAction->execute(
            $this->customerSession->getCustomer()->getId(),
            3,
            1,
            '',
            'entity_id',
            'DESC'
        );
        foreach ($result as $item) {
            $recentlyOrders[] = [
                'value' => $item->getData('entity_id'),
                'label' => $item->getData('increment_id')
            ];
        }
        return [
            'searchUrl' => $this->url->getUrl('helpdesk/ticket/LoadRecentParentOrders'),
            'recentlyOrders' => $recentlyOrders,
            'postKey' => 'parent_order'
        ];
    }
}
