<?php

namespace Branch8\Sales\Block\Adminhtml\Order\View\Items\Renderer;

use Magento\Backend\Block\Template\Context;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Framework\Registry;
use Magento\GiftMessage\Helper\Message as MessageHelper;
use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;

class DefaultRendererCustom extends DefaultRenderer
{
    /**
     * @var CatalogRuleRepositoryInterface
     */
    private CatalogRuleRepositoryInterface $catalogRuleRepository;

    protected $virtualProductHelper;
    protected $edenredTicketRecordRepository;

    /**
     * DefaultRendererCustom constructor.
     *
     * @param Context $context
     * @param StockRegistryInterface $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     * @param Registry $registry
     * @param MessageHelper $messageHelper
     * @param CheckoutHelper $checkoutHelper
     * @param CatalogRuleRepositoryInterface $catalogRuleRepository
     * @param VirtualProductHelper $virtualProductHelper
     * @param EdenredTicketRecordRepository $edenredTicketRecordRepository
     * @param array $data
     */
    public function __construct(
        Context                        $context,
        StockRegistryInterface         $stockRegistry,
        StockConfigurationInterface    $stockConfiguration,
        Registry                       $registry,
        MessageHelper                  $messageHelper,
        CheckoutHelper                 $checkoutHelper,
        CatalogRuleRepositoryInterface $catalogRuleRepository,
        VirtualProductHelper           $virtualProductHelper,
        EdenredTicketRecordRepository  $edenredTicketRecordRepository,
        array                          $data = []
    ) {
        parent::__construct(
            $context,
            $stockRegistry,
            $stockConfiguration,
            $registry,
            $messageHelper,
            $checkoutHelper,
            $data
        );
        $this->catalogRuleRepository = $catalogRuleRepository;
        $this->virtualProductHelper = $virtualProductHelper;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
    }

    /**
     * Retrieve the names of the rules applied to the order.
     *
     * @return string Rule names if available, otherwise 'N/A'
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRuleName()
    {
        $ruleName = 'N/A'; // Default rule name
        try {
            $order = $this->getOrder(); // Get the current order

            // If the order has any applied rule names, return them
            if ($order->getAppliedRuleNames()) {
                return $order->getAppliedRuleNames();
            }
        } catch (\Exception $e) {
            // If any exception occurs, return the default rule name
            return $ruleName;
        }

        return $ruleName;
    }

    /**
     * Returns catalog rule names apply into order item.
     *
     * @param OrderItem $item
     *
     * @return string
     */
    public function getCatalogRuleNames(OrderItem $item): string
    {
        $priceLog = $item->getData('price_log');
        if (empty($priceLog)) {
            return '';
        }

        $appliedRuleData = json_decode($priceLog, true);
        if (empty($appliedRuleData)) {
            return '';
        }

        $ruleNames = [];
        foreach ($appliedRuleData as $rule) {
            $ruleId = $rule['rule_id'] ?? false;
            if (!$ruleId) {
                continue;
            }

            try {
                $rule = $this->catalogRuleRepository->get($ruleId);
            } catch (\Exception $e) {
                continue;
            }
            $ruleNames[] = $rule->getName();
        }

        return implode(',', $ruleNames);
    }

    public function isEdenredTicket($item): bool
    {
        $virtualProductType = $this->virtualProductHelper->getProductTicketTypeByOrderItemId((int) $item->getId());

        return $virtualProductType == VirtualProductType::TYPE_EDENRED_TICKET;
    }

    public function getEdenredClientOrderNumber($item): string
    {
        $clientOrderNumber = "";

        $records = $this->edenredTicketRecordRepository->getRecordsByOrderItemId((int) $item->getId());

        if ($records->getSize()) {
            /** @var \Branch8\Edenred\Model\EdenredTicketRecord $record */
            $record = $records->getFirstItem();
            $clientOrderNumber = $record->getEdenredClientOrderNumber();
        }

        return $clientOrderNumber;
    }
}
