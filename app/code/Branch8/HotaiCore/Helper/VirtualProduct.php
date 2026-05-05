<?php
namespace Branch8\HotaiCore\Helper;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory as CustomerTicketCollectionFactory;
use Branch8\Edenred\Helper\Flow as EdenredFlow;
use Branch8\FamilyBonusPin\Helper\Flow as FamilyBonusPinFlow;
use Branch8\GeneralNonNotifyTicket\Helper\Flow as GeneralNonNotifyFlow;
use Branch8\GeneralNotifyTicket\Helper\Flow as GeneralNotifyFlow;
use Branch8\HotaiCore\Helper\Common as CommonHelper;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\Yoxi\Helper\Flow as YoxiFlow;
use HotaiConnected\OpenHub\Helper\Flow as OpenHubFlow;
use HotaiConnected\Qware\Helper\Flow as QwareFlow;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory as OptionFactory;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory as ValueFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Framework\Exception\LocalizedException;
use Branch8\HotaiCore\Model\Ticket\Reason as TicketReason;

class VirtualProduct
{
    const TICKET_BATCH_SETTING_OPTION_TITLE = '貨號選項';
    const TICKET_BATCH_SETTING_OPTION_TYPE  = 'drop_down';

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var CustomerTicketCollectionFactory */
    protected $customerTicketCollectionFactory;

    /** @var YoxiFlow */
    protected $yoxiFlow;

    /** @var EdenredFlow */
    protected $edenredFlow;

    /** @var FamilyBonusPinFlow */
    protected $familyBonusPinFlow;

    /** @var GeneralNotifyFlow */
    protected $generalNotifyFlow;

    /** @var GeneralNonNotifyFlow */
    protected $generalNonNotifyFlow;

    /** @var OpenHubFlow */
    protected $openHubFlow;

    /** @var QwareFlow */
    protected $qwareFlow;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var OptionFactory */
    protected $optionFactory;

    /** @var ValueFactory */
    protected $valueFactory;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    protected $timezone;

    /** @var CommonHelper */
    protected $commonHelper;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        ProductRepositoryInterface $productRepository,
        OptionFactory $optionFactory,
        ValueFactory $valueFactory,
        OrderItemRepository $orderItemRepository,
        CustomerTicketCollectionFactory $customerTicketCollectionFactory,
        YoxiFlow $yoxiFlow,
        EdenredFlow $edenredFlow,
        FamilyBonusPinFlow $familyBonusPinFlow,
        GeneralNotifyFlow $generalNotifyFlow,
        GeneralNonNotifyFlow $generalNonNotifyFlow,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        CommonHelper $commonHelper,
        OpenHubFlow $openHubFlow,
        QwareFlow $qwareFlow
    ) {
        $this->hotaiCoreCommonHelper           = $hotaiCoreCommonHelper;
        $this->productRepository               = $productRepository;
        $this->optionFactory                   = $optionFactory;
        $this->valueFactory                    = $valueFactory;
        $this->orderItemRepository             = $orderItemRepository;
        $this->customerTicketCollectionFactory = $customerTicketCollectionFactory;
        $this->yoxiFlow                        = $yoxiFlow;
        $this->edenredFlow                     = $edenredFlow;
        $this->familyBonusPinFlow              = $familyBonusPinFlow;
        $this->generalNotifyFlow               = $generalNotifyFlow;
        $this->generalNonNotifyFlow            = $generalNonNotifyFlow;
        $this->timezone                        = $timezone;
        $this->commonHelper                    = $commonHelper;
        $this->openHubFlow                     = $openHubFlow;
        $this->qwareFlow                       = $qwareFlow;
    }

    /**
     * 根據傳入的order item ID回傳電子票券種類
     * @param int $orderItemId
     * @return int|null
     */
    public function getProductTicketTypeByOrderItemId(int $orderItemId): ?int
    {
        try {
            $orderItem = $this->orderItemRepository->get($orderItemId);
        } catch (\Exception $e) {
            return null;
        }

        $type = $orderItem->getProductOptionByCode(VirtualProductType::ATTRIBUTE_CODE);

        if (empty($type)) {
            return null;
        }

        return (int) $type;
    }

    /**
     * 根據傳入的order item ID判斷所購買的產品是否屬於電子票券
     * @param integer $orderItemId
     * @return boolean
     */
    public function checkIsProductTicketTypeByOrderItemId(int $orderItemId): bool
    {
        try {
            $orderItem = $this->orderItemRepository->get($orderItemId);
        } catch (\Exception $e) {
            return false;
        }
        $product            = $orderItem->getProduct();
        $virtualProductType = $product?->getData("virtual_product_type");

        if (empty($virtualProductType)) {
            return false;
        }

        return in_array((int) $virtualProductType, VirtualProductType::TYPES_TICKET);
    }

    /**
     * 根據傳入的order item ID回傳所屬票券各序號的使用狀況
     * @param int $orderItemId
     * @throws \Exception
     * @return array
     */
    public function getTicketStatusByOrderItemId(int $orderItemId): array
    {
        if (!$this->checkIsProductTicketTypeByOrderItemId($orderItemId)) {
            throw new \Exception("This is not a ticket type product.");
        }

        $orderItem                   = $this->orderItemRepository->get($orderItemId);
        $product                     = $orderItem->getProduct();
        $virtualProductTypeAttribute = $product?->getCustomAttribute('virtual_product_type');
        $virtualProductType          = $virtualProductTypeAttribute->getValue();

        if (!in_array($virtualProductType, VirtualProductType::TYPES_TICKET)) {
            throw new \Exception("Unexpected virtual product type: {$virtualProductType}");
        }

        $statusArray = [
            "type"       => $virtualProductType,
            "total"      => 0,
            "unused"     => [],
            "used"       => [],
            "expiration" => [],
            "canceled"   => [],
            "exception"  => [],
            "ticketId"   => [],
            "remainDay"  => [],
        ];

        $collection = $this->customerTicketCollectionFactory->create();
        $collection->addFieldToFilter(CustomerTicket::TYPE, $virtualProductType);
        $collection->addFieldToFilter(CustomerTicket::SALES_ORDER_ITEM_ID, $orderItemId);
        $collection->load();

        $statusArray["total"] = count($collection->getItems());

        /** @var CustomerTicket $customerTicket */
        foreach ($collection->getItems() as $customerTicket) {

            $ticketStatus = $customerTicket->getStatus();
            array_push($statusArray["ticketId"], $customerTicket->getId());
            array_push($statusArray["remainDay"], $this->getRemainDay($customerTicket->getUseEndTime()));

            switch ($ticketStatus) {
                case TicketStatus::STATUS_UNUSED:
                    array_push($statusArray["unused"], $customerTicket->getTicketUniqueContent());
                    break;

                case TicketStatus::STATUS_USED:
                    array_push($statusArray["used"], $customerTicket->getTicketUniqueContent());
                    break;

                case TicketStatus::STATUS_OVER_DUE:
                    array_push($statusArray["expiration"], $customerTicket->getTicketUniqueContent());
                    break;

                case TicketStatus::STATUS_RETURNED:
                    array_push($statusArray["canceled"], $customerTicket->getTicketUniqueContent());
                    break;

                default:
                    array_push($statusArray["exception"], $customerTicket->getTicketUniqueContent());
                    break;
            }
        }

        return $statusArray;
    }

    public function getRemainDay($dateString)
    {
        if (empty($dateString)) {
            return 0;
        }
        $date = $this->timezone->date($dateString);
        $now  = $this->timezone->date();
        $days = $now->diff($date)->format('%r%a');
        return $days;
    }

    /**
     * 根據傳入的customer ID獲取擁有的票券數量
     * @param int $customerId
     * @return int
     */
    public function getTicketCountByCustomerId(int $customerId): int
    {
        $collection = $this->customerTicketCollectionFactory->create();
        $collection
            ->addFieldToSelect('record_id')
            ->addFieldToFilter(CustomerTicket::CUSTOMER_ID, $customerId)
            ->addFieldToFilter('status', \Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED);

        return $collection->getSize();
    }

    /**
     * 呼叫取消票券API
     * 更新票券表與customerTicket表的狀態
     * @param int $orderItemId
     * @return void
     * @throws \Exception
     */
    public function cancelTickets(int $orderItemId): void
    {
        $orderItem                   = $this->orderItemRepository->get($orderItemId);
        $product                     = $orderItem->getProduct();
        $virtualProductTypeAttribute = $product?->getCustomAttribute('virtual_product_type');

        // if $virtualProductTypeAttribute is null then maybe this product isn't ticket product,
        // shouldn't pass in this function at start, just return.
        if (is_null($virtualProductTypeAttribute)) {
            return;
        }

        $virtualProductType = $virtualProductTypeAttribute->getValue();

        if (!in_array($virtualProductType, VirtualProductType::TYPES_TICKET)) {
            throw new \Exception("Unexpected virtual product type: {$virtualProductType}");
        }

        switch ($virtualProductType) {
            case VirtualProductType::TYPE_YOXI_TICKET:
                $this->yoxiFlow->cancelTickets($orderItemId);
                break;

            case VirtualProductType::TYPE_EDENRED_TICKET:
                $this->edenredFlow->cancelTickets($orderItemId);
                break;

            case VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET:
                $this->familyBonusPinFlow->cancelTickets($orderItemId);
                break;

            case VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET:
                $this->generalNotifyFlow->cancelTickets($orderItemId);
                break;

            case VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET:
                $this->generalNonNotifyFlow->cancelTickets($orderItemId);
                break;

            case VirtualProductType::TYPE_OPENHUB_TICKET:
                $this->openHubFlow->cancelTickets($orderItemId);
                break;

            case VirtualProductType::TYPE_QWARE_TICKET:
                $this->qwareFlow->cancelTickets($orderItemId);
                break;

            default:
                throw new \Exception("Unexpected virtual product type: {$virtualProductType}");
        }
    }

    public function getVirtualProductType(int $productId): mixed
    {
        return $this->commonHelper->getVirtualProductType($productId);
    }

    public function isBatchImportTicketProduct(int|string $productId): bool
    {
        $virtualProductType = $this->getVirtualProductType($productId);

        if (!$virtualProductType) {
            return false;
        }

        return in_array($virtualProductType, VirtualProductType::TYPES_BATCH_IMPORT_TICKET);
    }

    public function getTicketAvailableBatchData(int|string $productId): array
    {
        if (!$this->isBatchImportTicketProduct($productId)) {
            return [];
        }

        $virtualProductType = $this->getVirtualProductType($productId);

        switch ($virtualProductType) {
            case VirtualProductType::TYPE_YOXI_TICKET:
                return $this->yoxiFlow->getAvailableBatchData($productId);

            case VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET:
                return $this->familyBonusPinFlow->getAvailableBatchData($productId);

            case VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET:
                return $this->generalNotifyFlow->getAvailableBatchData($productId);

            case VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET:
                return $this->generalNonNotifyFlow->getAvailableBatchData($productId);

            default:
                return [];
        }
    }

    public function checkIfQuantityEnoughByCustomOptionAndRequestQuantity(
        int|string $productId,
        string $customOptionValue,
        int|string $requestQuantity
    ): array {
        if (!$this->isBatchImportTicketProduct($productId)) {
            return [
                "result" => false,
                "reason" => TicketReason::REASON_FOR_OOS_CHECK_PRODUCT_NOT_A_BATCH_IMPORT_TICKET,
            ];
        }

        $virtualProductType = $this->getVirtualProductType($productId);
        switch ($virtualProductType) {
            case VirtualProductType::TYPE_YOXI_TICKET:
                return $this->yoxiFlow->checkIfQuantityEnoughByCustomOptionAndRequestQuantity($productId, $customOptionValue, $requestQuantity);

            case VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET:
                return $this->familyBonusPinFlow->checkIfQuantityEnoughByCustomOptionAndRequestQuantity($productId, $customOptionValue, $requestQuantity);

            case VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET:
                return $this->generalNotifyFlow->checkIfQuantityEnoughByCustomOptionAndRequestQuantity($productId, $customOptionValue, $requestQuantity);

            case VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET:
                return $this->generalNonNotifyFlow->checkIfQuantityEnoughByCustomOptionAndRequestQuantity($productId, $customOptionValue, $requestQuantity);

            default:
                return [
                    "result" => false,
                    "reason" => TicketReason::REASON_FOR_OOS_CHECK_QUANTITY_VIRTUAL_PRODUCT_TYPE_NOT_SUPPORTED,
                ];
        }
    }

    public function checkIfBatchSettingCustomOptionExist(int|string $productId): bool
    {
        $product = $this->productRepository->getById($productId);

        if ($product->getHasOptions() == 0) {
            return false;
        }

        $options = $product->getOptions();

        foreach ($options as $option) {
            if ($option->getTitle() === self::TICKET_BATCH_SETTING_OPTION_TITLE) {
                return true;
            }
        }

        return false;
    }

    public function checkIfBatchSettingCustomOptionValueExist(int|string $productId, int|string $batchSettingId): bool
    {
        $product = $this->productRepository->getById($productId);

        if ($product->getHasOptions() == 0) {
            return false;
        }

        $options      = $product->getOptions();
        $targetOption = null;

        foreach ($options as $option) {
            if ($option->getTitle() === self::TICKET_BATCH_SETTING_OPTION_TITLE) {
                $targetOption = $option;
                break;
            }
        }

        if ($targetOption === null) {
            return false;
        }

        $values = $targetOption->getValues();
        foreach ($values as $value) {
            if ($value->getSku() == $batchSettingId) {
                return true;
            }
        }

        return false;
    }

    /**
     * example of $inputValueArray:
     * $inputValueArray = [
     *  ['title' => 'Batch Code 1', 'sku' => '{batch_setting_id_1}'],
     *  ['title' => 'Batch Code 2', 'sku' => '{batch_setting_id_2}'],
     * ];
     * @param int|string $productId
     * @param array $inputValueArray
     * @throws \Exception
     * @return void
     */
    public function createBatchSettingCustomOptionValue(int|string $productId, array $inputValueArray): void
    {
        $product = $this->productRepository->getById($productId);

        // 如果產品沒有自訂選項，則先建立一個
        if (!$this->checkIfBatchSettingCustomOptionExist($productId)) {
            $newOption = $this->optionFactory->create([
                'data' => [
                    'title'      => self::TICKET_BATCH_SETTING_OPTION_TITLE,
                    'type'       => self::TICKET_BATCH_SETTING_OPTION_TYPE,
                    'is_require' => 1,
                    'sort_order' => 0,
                    // 'price'      => 0,
                    // 'price_type' => 'fixed',
                    // 'sku'        => ''
                ],
            ]);

            $newOption->setProductId($productId);
            $newOption->setStoreId(0);

            $newValues = [];

            foreach ($inputValueArray as $inputValue) {
                $newValues[] = $this->valueFactory->create([
                    'data' => [
                        'title'      => $inputValue["title"],
                        'price'      => 0,
                        'price_type' => 'fixed',
                        'sku'        => $inputValue["sku"],
                        // 'sort_order' => 1
                    ],
                ]);
            }

            $newOption->setValues($newValues);

            $product->addOption($newOption);
            $product->setHasOptions(true);
            $product->setStoreId(0);

            $this->productRepository->save($product);

            return;
        }

        // 如果已經有batch setting custom option, 則使用該custom option做新增
        $options      = $product->getOptions();
        $targetOption = null;

        foreach ($options as $option) {
            if ($option->getTitle() === self::TICKET_BATCH_SETTING_OPTION_TITLE) {
                $targetOption = $option;
                break;
            }
        }

        if ($targetOption === null) {
            throw new \Exception("Target option not found for product ID: {$productId}");
        }

        $currentValues = $targetOption->getValues();

        foreach ($inputValueArray as $inputValue) {
            $canSave = true;
            foreach ($currentValues as $currentValue) {
                if ($currentValue->getSku() == $inputValue['sku']) {
                    $canSave = false;
                    break;
                }
            }

            if (!$canSave) {
                continue;
            }

            $this->valueFactory->create()
                ->addValue([
                    'title'      => $inputValue['title'],
                    'price'      => 0,
                    'price_type' => 'fixed',
                    'sku'        => $inputValue['sku'],
                ])
                ->setOption($targetOption)
                ->saveValues();
        }
    }

    public function deleteBatchSettingCustomOption(int|string $productId): void
    {
        $product = $this->productRepository->getById($productId);

        if (!$this->checkIfBatchSettingCustomOptionExist($productId)) {
            return;
        }

        $options = $product->getOptions();

        $deleteOptions    = [];
        $remainingOptions = [];

        foreach ($options as $option) {
            if ($option->getTitle() === self::TICKET_BATCH_SETTING_OPTION_TITLE) {
                $deleteOptions[] = $option;
            } else {
                $remainingOptions[] = $option;
            }
        }

        $product->setOptions($remainingOptions);
        $product->setStoreId(0);
        $this->productRepository->save($product);
    }

    public function createBatchSettingCustomOptionValueBasedOnCurrentBatchSetting(int|string $productId): void
    {
        if (!$this->isBatchImportTicketProduct($productId)) {
            throw new \Exception("This product is not a batch import ticket product.");
        }

        $batchSettingData = [];

        switch ($this->getVirtualProductType($productId)) {
            case VirtualProductType::TYPE_YOXI_TICKET:
                $batchSettingData = $this->yoxiFlow->getCurrentBatchSettingDataForCustomOption($productId);
                break;

            case VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET:
                $batchSettingData = $this->familyBonusPinFlow->getCurrentBatchSettingDataForCustomOption($productId);
                break;

            case VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET:
                $batchSettingData = $this->generalNotifyFlow->getCurrentBatchSettingDataForCustomOption($productId);
                break;

            case VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET:
                $batchSettingData = $this->generalNonNotifyFlow->getCurrentBatchSettingDataForCustomOption($productId);
                break;

            default:
                throw new \Exception("Unexpected virtual product type for this product ID: {$productId}");
        }

        if (count($batchSettingData) == 0) {
            return;
        }

        $this->deleteBatchSettingCustomOption($productId);

        $inputValueArray = [];
        foreach ($batchSettingData as $data) {
            $inputValueArray[] = [
                'title' => $data['batch_code'],
                'sku'   => $data['setting_id'],
            ];
        }

        $this->createBatchSettingCustomOptionValue($productId, $inputValueArray);
    }

    public function getBatchSettingCustomOptionValueForQuoteItemFlow(\Magento\Quote\Model\Quote\Item $quoteItem): ?\Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface
    {
        $product = $quoteItem->getProduct();

        if (!$product->getHasOptions()) {
            throw new \Exception("getBatchSettingCustomOptionValueForQuoteItemFlow product({$product->getId()}) has no option.");
        }

        $options         = $product->getOptions();
        $buyRequest      = $quoteItem->getBuyRequest();
        $selectedOptions = $buyRequest->getOptions();

        if (empty($selectedOptions)) {
            throw new \Exception("getBatchSettingCustomOptionValueForQuoteItemFlow can't get buy request.");
        }

        $batchSettingOptionId               = null;
        $batchSettingOptionValues           = null;
        $selectedBatchSettingOptionValueId  = null;
        $selectedBatchSettingOptionValue    = null;
        $selectedBatchSettingOptionValueSku = null;

        foreach ($options as $productOption) {
            $cond1 = $productOption->getTitle() == self::TICKET_BATCH_SETTING_OPTION_TITLE;
            $cond2 = $productOption->getType() == self::TICKET_BATCH_SETTING_OPTION_TYPE;

            if ($cond1 && $cond2) {
                $batchSettingOptionId     = $productOption->getId();
                $batchSettingOptionValues = $productOption->getValues();
                break;
            }
        }

        if ($batchSettingOptionId == null) {
            throw new \Exception("getBatchSettingCustomOptionValueForQuoteItemFlow can't locate batch setting custom option from product({$product->getId()}).");
        }

        if (!isset($selectedOptions[$batchSettingOptionId])) {
            throw new \Exception("getBatchSettingCustomOptionValueForQuoteItemFlow can't locate batch setting custom option from buy request.");
        }

        $selectedBatchSettingOptionValueId = $selectedOptions[$batchSettingOptionId];

        foreach ($batchSettingOptionValues as $optionValue) {
            if ($optionValue->getOptionTypeId() == $selectedBatchSettingOptionValueId) {
                $selectedBatchSettingOptionValue    = $optionValue;
                $selectedBatchSettingOptionValueSku = $optionValue->getSku();
                break;
            }
        }

        if ($selectedBatchSettingOptionValue == null) {
            throw new \Exception("getBatchSettingCustomOptionValueForQuoteItemFlow can't locate batch setting custom option value from buy request.");
        }

        return $selectedBatchSettingOptionValue;
    }

    public function checkBatchSettingSaleEndTimeForSetAllocationFlow(string $saleEndTime): bool
    {
        $currentTimeObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();
        $saleEndTimeObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject($saleEndTime);

        if ($currentTimeObj > $saleEndTimeObj) {
            return false;
        }

        return true;
    }
}
