<?php
namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\SubOrderItem;

use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\SubOrderItems;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\Template\Context;
use Magento\Rma\Helper\Data as RmaHelper;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Customer\Model\Session\Proxy as CustomerSession;

class SellerChatLink extends SubOrderItems
{
    protected $chatConfig;
    public function __construct(
        Context $context,
        Registry $registry,
        OrderRepository $_orderRepository,
        \Webkul\Marketplace\ViewModel\Profile $profile,
        ProductRepositoryInterface $productRepository,
        ImageFactory $imageFactory,
        \Webkul\Marketplace\Helper\Data $marketPlaceDataHelper,
        \Branch8\SplitCart\Helper\Data $splitCartHelper,
        \Magento\Store\Model\StoreManagerInterface $storemanager,
        OptionFactory $productOptionFactory,
        StringUtils $string,
        Image $imageHelper,
        \Branch8\Sales\Block\ParentOrder\Items $itemBlock,
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        RmaHelper $rmaHelper,
        ScopeConfigInterface $scopeConfig,
        \Ecpay\General\Controller\Api\Invoice $invoiceAPi,
        \Branch8\Sales\Helper\Config $config,
        \Branch8\WebkulMpBuyerSellerChatForParentOrder\ViewModel\ChatConfig $chatConfig,
        \Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\CollectionFactory $preorderItemCollectionFactory,
        \Webkul\MpRmaSystem\Model\DetailsFactory $rmaDetail,
        VirtualProductHelper          $virtualProductHelper,
        EdenredTicketRecordRepository $edenredTicketRecordRepository,
        CustomerSession $customerSession,
        CollectionFactory $itemCollectionFactory = null
    )
    {
        parent::__construct(
            $context,
            $registry,
            $_orderRepository,
            $profile,
            $productRepository,
            $imageFactory,
            $marketPlaceDataHelper,
            $splitCartHelper,
            $storemanager,
            $productOptionFactory,
            $string,
            $imageHelper,
            $itemBlock,
            $preorderHelper,
            $rmaHelper,
            $scopeConfig,
            $invoiceAPi,
            $config,
            $preorderItemCollectionFactory,
            $rmaDetail,
            $virtualProductHelper,
            $edenredTicketRecordRepository,
            $customerSession,
            $itemCollectionFactory
        );
        $this->chatConfig = $chatConfig;
    }

    public function getChatConfig()
    {
        return $this->chatConfig;
    }
    protected $_template = 'Branch8_MarketPlaceParentOrderFrontendUi::parent_order/chat_seller.phtml';

    public function _prepareLayout()
    {
        $this->setData('view_model', $this->getChatConfig());
        parent::_prepareLayout();
    }
}
