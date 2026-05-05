<?php
declare(strict_types=1);

namespace Branch8\SalesRule\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class for adding Customer Segment conditions section
 */
class AddFirstPurchaseOrderSalesRuleCombineObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface          $objectManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Module\Manager                  $moduleManager
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->_objectManager = $objectManager;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $additional = $observer->getEvent()->getAdditional();
        $conditions = (array)$additional->getConditions();
        if (!is_array($conditions)) {
            $conditions = [];
        }
        $typeCode = 'Orders';
        $code = 'first_purchase_history';
        if ($this->moduleManager->isEnabled('Amasty_RulesPro')) {
            /**
             * @var $condition
             */
            foreach ($conditions as &$condition) {
                if (isset($condition['label']) && $condition['label'] instanceof \Magento\Framework\Phrase
                    && $condition['label']->getText() == 'Purchases history'
                ) {
                    if (!is_array($condition['value'])) {
                        $condition['value'] = [];
                    }
                    $condition['value'][] = [
                        'label' => __('First Purchase Order'),
                        'value' => 'Branch8\SalesRule\Model\Rule\Condition\\' . $typeCode . '|' . $code,
                    ];
                }
            }
        } else {
            $conditions = array_merge_recursive(
                $conditions,
                [
                    [
                        'label' => __('First Purchase Order'),
                        'value' => 'Branch8\SalesRule\Model\Rule\Condition\\' . $typeCode . '|' . $code,
                    ],
                ]
            );
        }
        $additional->setConditions($conditions);
    }
}
