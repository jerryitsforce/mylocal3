<?php
declare (strict_types = 1);

namespace Branch8\Edenred\Block\ParentOrder;

use Branch8\Edenred\Helper\Common as CommonHelper;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Branch8\HotaiCore\Helper\Barcode as BarcodeHelper;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;

class BarcodeLinks extends Template
{
    /** @var string */
    protected $_template = 'Branch8_Edenred::parent_order/barcodelinks.phtml';

    /** @var \Magento\Framework\Registry */
    protected $coreRegistry = null;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var EdenredTicketRecordRepository */
    protected $edenredTicketRecordRepository;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var BarcodeHelper */
    protected $barcodeHelper;

    public function __construct(
        TemplateContext $context,
        Registry $registry,
        OrderRepository $orderRepository,
        CommonHelper $commonHelper,
        EdenredTicketRecordRepository $edenredTicketRecordRepository,
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository,
        BarcodeHelper $barcodeHelper,
        array $data = []
    ) {
        $this->coreRegistry                  = $registry;
        $this->orderRepository               = $orderRepository;
        $this->commonHelper                  = $commonHelper;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
        $this->scopeConfig                   = $scopeConfig;
        $this->productRepository             = $productRepository;
        $this->barcodeHelper                 = $barcodeHelper;

        parent::__construct($context, $data);
    }

    /**
     * 獲取當前頁面的Parent order資料
     *
     * @return ParentOrder
     */
    public function getOrder(): ParentOrder
    {
        return $this->coreRegistry->registry('current_parent_order');
    }

    /**
     * 根據當前頁面的Parent order取得相關的宜睿票券資料(如果有的話)
     *
     * @return array
     */
    public function getEdenredTicketData(): array
    {
        $salesOrderItemIdsArray = [];
        $edenredTicketDataArray = [];
        $orderIds               = \explode(",", $this->getOrder()->getOrderIds());
        $edenredGuidUrl         = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_DYNAMIC_VOUCHER_GUID_PAGE_URL);

        foreach ($orderIds as $orderId) {
            $order = $this->orderRepository->get((int) $orderId);

            /** @var \Magento\Sales\Model\Order $order */
            foreach ($order->getAllVisibleItems() as $item) {
                if (!$this->commonHelper->IsEdenredTicketProduct((int) $item->getProductId())) {
                    continue;
                }

                $salesOrderItemIdsArray[] = $item;
            }
        }

        foreach ($salesOrderItemIdsArray as $salesOrderItem) {
            $collection   = $this->edenredTicketRecordRepository->getRecordsByOrderItemId((int) $salesOrderItem->getId());
            $recordsArray = $collection->getItems();

            if (count($recordsArray) == 0) {
                continue;
            }

            /** @var \Branch8\Edenred\Model\EdenredTicketRecord $record */
            foreach ($recordsArray as $record) {
                $edenredTicketDataArray[] = [
                    "barcode_type"          => $this->barcodeHelper->getBarcodeTypeByProductId((int) $salesOrderItem->getProductId()),
                    "edenred_voucher_no"    => $record->getEdenredVoucherNo(),
                    "edenred_voucher_guid"  => $record->getEdenredVoucherGuid(),
                    "edenred_guid_page_url" => $edenredGuidUrl . $record->getEdenredVoucherGuid(),
                ];
            }
        }

        return $edenredTicketDataArray;
    }

    /**
     * 根據宜睿票券資料組出顯示條碼的網址
     *
     * @param string $barcodeContent
     * @param integer $barcodeType
     * @return string
     */
    public function getBarcodeDisplayUrl(string $barcodeContent, int $barcodeType): string
    {
        return $this->_urlBuilder->getUrl('hotai_core/barcode/display', [
            "barcode_content" => $barcodeContent,
            "barcode_type"    => $barcodeType,
        ]);
    }
}
