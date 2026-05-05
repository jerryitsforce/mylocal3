<?php

namespace Branch8\Edenred\Block\Detail;

use Branch8\Edenred\Model\EdenredTicketRecord as EdenredTicketRecordModel;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord\Collection as EdenredTicketRecordCollection;
use Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord\CollectionFactory as EdenredTicketRecordCollectionFactory;
use Branch8\HotaiCore\Helper\Barcode as BarcodeHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ProductImageHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Template;

class Index extends Template
{
    const DEFAULT_PAGE_INDEX = 1;
    const DEFAULT_PAGE_SIZE = 10;
    const PAGE_SIZE_POOL = [10, 20, 30];

    const TICKET_STATUS_POOL = ["Unused", "Used", "Canceled"];
    const DEFAULT_TICKET_STATUS = "Unused";

    /** @var BarcodeHelper */
    protected $barcodeHelper;

    /** @var EdenredTicketRecordRepository */
    protected $edenredTicketRecordRepository;

    /** @var EdenredTicketRecordCollectionFactory */
    protected $edenredTicketRecordCollectionFactory;

    /** @var Session */
    protected $customerSession;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ProductImageHelper */
    protected $productImageHelper;

    public $collection;
    public $barcodeTypeCache = [];
    public $ticketStatus;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        BarcodeHelper $barcodeHelper,
        EdenredTicketRecordRepository $edenredTicketRecordRepository,
        EdenredTicketRecordCollectionFactory $edenredTicketRecordCollectionFactory,
        Session $customerSession,
        ResourceConnection $resourceConnection,
        ProductRepositoryInterface $productRepository,
        ProductImageHelper $productImageHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->barcodeHelper = $barcodeHelper;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
        $this->edenredTicketRecordCollectionFactory = $edenredTicketRecordCollectionFactory;
        $this->customerSession = $customerSession;
        $this->resourceConnection = $resourceConnection;
        $this->productRepository = $productRepository;
        $this->productImageHelper = $productImageHelper;

        parent::__construct($context, $data);
    }

    /**
     * Initializes toolbar
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout(): \Magento\Framework\View\Element\AbstractBlock
    {
        parent::_prepareLayout();

        $this->setTicketStatus();
        $collection = $this->getEdenredTicketRecordCollection();

        $pager = $this->getLayout()
            ->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'edenred_ticket_record.index.pager'
            )->setAvailableLimit(
                $this->getPageSizePoolForPager()
            )->setShowPerPage(
                true
            )->setCollection($collection);

        $this->setChild('pager', $pager);

        $this->collection = $collection;

        return $this;
    }

    /**
     * 根據url參數確定當前要查詢的票券狀態
     *
     * @return void
     */
    protected function setTicketStatus(): void
    {
        switch ($this->getRequest()->getParam('status')) {
            case 'Used':
                $this->ticketStatus = $this->getRequest()->getParam('status');
                break;

            case 'Canceled':
                $this->ticketStatus = $this->getRequest()->getParam('status');
                break;

            case 'Unused':
            default:
                $this->ticketStatus = self::DEFAULT_TICKET_STATUS;
                break;
        }
    }

    /**
     * 配合當前要查詢的票券狀態取得宜睿票券collection
     *
     * @return EdenredTicketRecordCollection
     */
    protected function getEdenredTicketRecordCollection(): EdenredTicketRecordCollection
    {
        $pageIndex = ($this->getRequest()->getParam('p')) ? $this->getRequest()->getParam('p') : self::DEFAULT_PAGE_INDEX;
        $pageSize = ($this->getRequest()->getParam('limit')) ? $this->getRequest()->getParam('limit') : self::DEFAULT_PAGE_SIZE;
        $collection = null;

        switch ($this->ticketStatus) {
            case 'Used':
                $queryStatus = EdenredTicketRecordModel::STATUS_USED;
                break;

            case 'Canceled':
                $queryStatus = EdenredTicketRecordModel::STATUS_CANCELED;
                break;

            case 'Unused':
            default:
                $queryStatus = EdenredTicketRecordModel::STATUS_IMPORTED;
                break;
        }

        $collection = $this->edenredTicketRecordCollectionFactory->create();
        $collection
            ->join(
                ['sales_order_item' => 'sales_order_item'],
                'main_table.sales_order_item_id = sales_order_item.item_id',
                ['sales_order_item_id' => 'item_id', 'order_id' => 'order_id']
            )
            ->join(
                ['sales_order' => 'sales_order'],
                'sales_order_item.order_id = sales_order.entity_id',
                ['sales_order_id' => 'entity_id', 'customer_id' => 'customer_id']
            )->addFieldToFilter(
                'customer_id',
                $this->customerSession->getCustomerId()
            )->addFieldToFilter(
                'main_table.status',
                $queryStatus
            )->setPageSize(
                $pageSize
            )->setCurPage(
                $pageIndex
            )->load();

        return $collection;
    }

    /**
     * 分頁footer
     *
     * @return string
     */
    public function getPagerHtml(): string
    {
        return $this->getChildHtml('pager');
    }

    /**
     * 以productId取得產品名稱
     *
     * @param integer $productId
     * @return string|null
     */
    public function getProductNameByProductId(int $productId): ?string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($productId);

        return $product->getName();
    }

    /**
     * 以productId取得產品縮圖url
     *
     * @param integer $productId
     * @return string|null
     */
    public function getProductThumbnailByProductId(int $productId): ?string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($productId);

        $url = $this->productImageHelper->init($product, 'product_thumbnail_image')->getUrl();

        return $url;
    }

    /**
     * 以productId和條碼取得顯示條碼url
     *
     * @param integer $productId
     * @param string $barcodeContent
     * @return string
     */
    public function getBarcodeDisplayUrl(int $productId, string $barcodeContent): string
    {
        return $this->getUrl("hotai_core/barcode/display", [
            "barcode_type" => $this->barcodeHelper->getBarcodeTypeByProductId($productId),
            "barcode_content" => $barcodeContent,
        ]);
    }

    /**
     * 以productId取得兌換網址
     *
     * @param integer $productId
     * @return string
     */
    public function getExchangeUrlByProductId(int $productId): string
    {
        $exchangeUrl = $this->barcodeHelper->getExchangeUrlByProductId($productId);

        return empty($exchangeUrl) ? "" : $exchangeUrl;
    }

    /**
     * 取得一頁顯示多少紀錄的設定
     *
     * @return array
     */
    protected function getPageSizePoolForPager(): array
    {
        $pageSizePoolForPager = [];

        foreach (self::PAGE_SIZE_POOL as $size) {
            $pageSizePoolForPager[$size] = $size;
        }

        return $pageSizePoolForPager;
    }
}
