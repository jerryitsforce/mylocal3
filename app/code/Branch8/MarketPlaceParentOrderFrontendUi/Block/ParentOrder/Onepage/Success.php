<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\Onepage;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Customer\Model\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    private $registry;

    /**
     * @var RuleCollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param Registry $registry
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session                  $checkoutSession,
        \Magento\Sales\Model\Order\Config                $orderConfig,
        \Magento\Framework\App\Http\Context              $httpContext,
        ParentOrderRepositoryInterface                   $parentOrderRepository,
        Registry                                         $registry,
        RuleCollectionFactory                            $ruleCollectionFactory,
        array                                            $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->parentOrderrRepository = $parentOrderRepository;
        $this->registry = $registry;
        $this->httpContext = $httpContext;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    /**
     * Render additional order information lines and return result html
     *
     * @return string
     */
    public function getAdditionalInfoHtml()
    {
        return $this->_layout->renderElement('order.success.additional.info');
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        if (!$this->getParentOrder()) {
            return '';
        }
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|null
     */
    public function getParentOrder()
    {
        try {
            $id = (int)$this->_checkoutSession->getData('parentOrderId');
            return $this->parentOrderrRepository->get($id);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $order = $this->getParentOrder();
        $lastOrder = $this->_checkoutSession->getLastRealOrder();
        if (!$this->registry->registry('current_order')) {
            $this->registry->register('current_order', $lastOrder);
        }
        $this->addData(
            [
                'is_order_visible' => $this->isVisible($order),
                'view_order_url' => $this->getUrl(
                    'sales/parentOrder/view/',
                    ['id' => $order->getEntityId()]
                ),
                'print_url' => $this->getUrl(
                    'sales/parentOrder/print',
                    ['id' => $order->getEntityId()]
                ),
                'can_print_order' => $this->isVisible($order),
                'can_view_order' => $this->canViewOrder($order),
                'order_id' => $order->getDetail()->getIncrementId()
            ]
        );
    }

    /**
     * Is order visible
     *
     * @param ParentOrder $order
     * @return bool
     */
    protected function isVisible(ParentOrder $order)
    {
        return !in_array(
            $order->getDetail()->getStatus(),
            $this->_orderConfig->getInvisibleOnFrontStatuses()
        );
    }

    /**
     * Can view order
     *
     * @param ParentOrder $order
     * @return bool
     */
    protected function canViewOrder(ParentOrder $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
            && $this->isVisible($order);
    }

    /**
     * @return string
     * @since 100.2.0
     */
    public function getContinueUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Get feedback contents from applied sales rules
     *
     * @return array
     */
    public function getFeedbackContents(): array
    {
        try {
            $parentOrder = $this->getParentOrder();
            if (!$parentOrder) {
                return [];
            }

            $allRuleIds = [];
            foreach ($parentOrder->getSubOrders() as $childOrder) {
                $ruleIds = $childOrder->getAppliedRuleIds();
                if ($ruleIds) {
                    $allRuleIds = array_merge($allRuleIds, explode(',', $ruleIds));
                }
            }
            $allRuleIds = array_unique(array_filter($allRuleIds));
            if (empty($allRuleIds)) {
                return [];
            }

            $collection = $this->ruleCollectionFactory->create();
            $collection->addFieldToFilter('rule_id', ['in' => $allRuleIds]);
            $collection->addFieldToFilter('feedback_content', ['notnull' => true]);
            $collection->addFieldToFilter('feedback_content', ['neq' => '']);

            $contents = [];
            foreach ($collection as $rule) {
                $content = trim($rule->getData('feedback_content'));
                if ($content !== '' && !in_array($content, $contents)) {
                    $contents[] = $content;
                }
            }
            return $contents;
        } catch (\Exception $e) {
            return [];
        }
    }
}
