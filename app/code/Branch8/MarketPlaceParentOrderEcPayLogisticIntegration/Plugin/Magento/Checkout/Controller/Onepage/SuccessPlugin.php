<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Plugin\Magento\Checkout\Controller\Onepage;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Manager;

/**
 * Onepage checkout success plugin class
 */
class SuccessPlugin
{
    const XML_PATH_ENABLE_SPLIT_ORDER = 'marketplace/mpsplitorder/mpsplitorder_enable';

    private \Magento\Framework\Controller\Result\RedirectFactory $rediectFactory;
    private ScopeConfigInterface $scopeConfig;
    private \Magento\Framework\View\Result\PageFactory $pageFactory;
    private Manager $eventManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param Manager $eventManager
     */
    public function __construct(
        ScopeConfigInterface                                 $scopeConfig,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\View\Result\PageFactory           $pageFactory,
        Manager                                              $eventManager
    )
    {
        $this->eventManager = $eventManager;
        $this->scopeConfig = $scopeConfig;
        $this->rediectFactory = $redirectFactory;
        $this->pageFactory = $pageFactory;
    }

    /**
     * Order success action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function aroundExecute(\Magento\Checkout\Controller\Onepage\Success $subject, callable $process, ...$args)
    {
        $isEnable = (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLE_SPLIT_ORDER);
        if (!$isEnable) {
            return $process($args);
        }
        $session = $subject->getOnepage()->getCheckout();
        if (!ObjectManager::getInstance()->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
            return $this->rediectFactory->create()->setPath('checkout/cart');
        }
        $session->clearQuote();
        //@todo: Refactor it to match CQRS
        $resultPage = $this->pageFactory->create();
        // get parentorder id from checkout session
        /**
         * get parentorder id from checkout session
         * see Branch8\WebkulMpsplitorder\Model\QuoteManagement::cacheSessionForSuccessPage
         */
        $this->eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            [
                'order_ids' => [$session->getLastOrderId()],
                'order' => $session->getLastRealOrder(),
                'parent_order_id' => $session->getData('parentOrderId')
            ]
        );
        return $resultPage;
    }
}
