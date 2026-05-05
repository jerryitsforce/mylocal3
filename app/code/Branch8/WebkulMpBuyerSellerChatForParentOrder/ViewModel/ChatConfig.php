<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatForParentOrder\ViewModel;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\BuildBuyerSellerConversationData;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileEntity;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChat\Model\GeneralConfig;
use Branch8\WebkulMpBuyerSellerChatForParentOrder\Model\ChatStatus;
use Branch8\WebkulMpBuyerSellerChatForParentOrder\Model\Services\GetChatObjectByCustomerId;
use Branch8\WebkulMpBuyerSellerChatForParentOrder\Model\Services\GetSellerDataByProductId;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Chat Config Model
 */
class ChatConfig implements ArgumentInterface
{
    private GeneralConfig $configData;

    private $parentOrderRepository;

    private $json;
    private ParentOrderManagementInterface $parentOrderManagement;
    private \Magento\Catalog\Helper\Image $imageHelper;
    private GetChatObjectByCustomerId $getChatObjectByCustomerId;
    private GetSellerDataByProductId $getSellerDataByProductId;
    private $customerRepository;
    private \Magento\Framework\View\Asset\Repository $assetRepo;
    private StoreManagerInterface $storeManager;
    private \Magento\Customer\Model\Session $customerSession;
    private ChatConversationRepository $chatConversationRepository;
    private GetOrCreateChatProfile $getOrCreateChatProfile;
    private $buildSellerConverstationData;

    /**
     * @param GeneralConfig $configData
     * @param ParentOrderRepository $parentOrderRepository
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param Json $json
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param CustomerRepository $customerRepository
     * @param GetChatObjectByCustomerId $getChatObjectByCustomerId
     * @param GetSellerDataByProductId $getSellerDataByProductId
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param StoreManagerInterface $storeManager
     * @param ChatConversationRepository $chatConversationRepository
     * @param GetOrCreateChatProfile $getOrCreateChatProfile
     * @param BuildBuyerSellerConversationData $buildBuyerSellerConversationData
     */
    public function __construct(
        GeneralConfig                            $configData,
        ParentOrderRepository                    $parentOrderRepository,
        ParentOrderManagementInterface           $parentOrderManagement,
        Json                                     $json,
        \Magento\Catalog\Helper\Image            $imageHelper,
        CustomerRepository                       $customerRepository,
        GetChatObjectByCustomerId                $getChatObjectByCustomerId,
        GetSellerDataByProductId                 $getSellerDataByProductId,
        \Magento\Customer\Model\Session          $customerSession,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        StoreManagerInterface                    $storeManager,
        ChatConversationRepository               $chatConversationRepository,
        GetOrCreateChatProfile                   $getOrCreateChatProfile,
        BuildBuyerSellerConversationData         $buildBuyerSellerConversationData
    )
    {
        $this->parentOrderRepository = $parentOrderRepository;
        $this->configData = $configData;
        $this->json = $json;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->imageHelper = $imageHelper;
        $this->getChatObjectByCustomerId = $getChatObjectByCustomerId;
        $this->getSellerDataByProductId = $getSellerDataByProductId;
        $this->customerRepository = $customerRepository;
        $this->assetRepo = $assetRepo;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->chatConversationRepository = $chatConversationRepository;
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;
        $this->buildSellerConverstationData = $buildBuyerSellerConversationData;
    }

    /**
     * @param $id
     * @return ParentOrderInterface|\Branch8\MarketPlaceParentOrder\Model\ParentOrder|mixed|string
     */
    public function getOrder($id)
    {
        try {
            return $this->parentOrderRepository->get((int)$id);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canShow(ParentOrderInterface $parentOrder)
    {
        $status = $parentOrder->getDetail()->getStatus();
        $hiddenStatus = $this->configData->getHiddenOrderStatus();
        $enable = $this->configData->enableChat();
        $customer = $this->getCustomer((int)$this->customerSession->getCustomerId());
        if (!$customer || !$enable || in_array($status, $hiddenStatus)) {
            return false;
        }
        return true;
    }

    /**
     * @param Order $subOrder
     * @param Order\Item $item
     * @return bool|string
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getChatDataForSubOrder(Order $subOrder, \Magento\Sales\Model\Order\Item $item)
    {
        $basicData = $this->basicChatData($item);
        if (!$basicData) {
            return '';
        }
        $basicData['order'] = [
            'id' => $subOrder->getIncrementId(),
            'status' => $subOrder->getFrontendStatusLabel(),
            'total' => (float)$item->getQtyOrdered(),
        ];
        return $this->json->serialize($basicData);
    }

    /**
     * @param Order $subOrder
     * @return float|int|null
     */
    private function getTotalQtyOrdered(Order $subOrder)
    {
        $sum = 0;
        foreach ($subOrder->getItems() as $item) {
            if ($item->getQtyOrdered() > 0) {
                $sum += $item->getQtyOrdered();
            }
        }
        return $sum;
    }
    /**
     * @param Order\Item $item
     * @return array|string|null
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function basicChatData(\Magento\Sales\Model\Order\Item $item)
    {
        /**
         * @var \Webkul\MpBuyerSellerChat\Model\CustomerData $chatSellerData
         */
        $product = $item->getProduct();
        if (!$product) {
            return '';
        }
        $sellerId = $this->getSellerDataByProductId->execute($product);
        if (empty($sellerId)) {
            return '';
        }
        $customer = $this->getCustomer((int)$this->customerSession->getCustomerId());
        $customerChatProfile = $this->getOrCreateChatProfile->execute(
            (int)$customer->getId(),
            ChatProfileEntity::CUSTOMER,
            ChatRole::CUSTOMER,
            ChatStatus::ONLINE
        );
        $sellerProfile = $this->getOrCreateChatProfile->execute(
            (int)$sellerId,
            ChatProfileEntity::CUSTOMER,
            ChatRole::SELLER
        );
        if (!$sellerId
            || !($customerChatProfile->getId())
            || !($sellerProfile)
            || !($sellerProfile->getId())

        ) {
            return null;
        }
        $conversation = $this->chatConversationRepository->findConverstation(
            $customerChatProfile,
            $sellerProfile
        );
        $data = [
            'item' => [
                'name' => $item->getName(),
                'imageUrl' => $this->getProductImage($item->getProduct())
            ],
            'conversation' => $this->buildSellerConverstationData->build(
                $conversation,
                $customerChatProfile,
                $sellerProfile
            )
        ];
        return $data;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @param Order\Item $item
     * @return bool|string
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getChatData(
        ParentOrderInterface            $parentOrder,
        \Magento\Sales\Model\Order\Item $item
    )
    {
        $basicData = $this->basicChatData($item);
        if (!$basicData) {
            return '';
        }
        $basicData['order'] = [
            'id' => $parentOrder->getDetail()->getIncrementId(),
            'status' => $parentOrder->getDetail()->getFrontendStatusLabel(),
            'total' => $this->getGrandTotal($parentOrder)
        ];
        return $this->json->serialize($basicData);
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return float|int
     */
    private function getGrandTotal(ParentOrderInterface $parentOrder)
    {
        try {
            $totals = $this->parentOrderManagement->getTotals($parentOrder);
            foreach ($totals as $total) {
                if ($total->getCode() === 'grand_total') {
                    return (float)$total->getValue();
                }
            }
            return 0;
        } catch (\Exception $exception) {
            return 0;
        }
    }

    /**
     * @param Product $product
     * @return string
     */
    private function getProductImage(Product $product)
    {
        return $this->imageHelper->init($product,
            'product_image_for_chat')
            ->setImageFile($product->getSmallImage())
            ->getUrl();
    }

    /**
     * @return bool|string
     */
    public function getChatLinkWidgetConfig()
    {
        return $this->json->serialize([
            'pattern' => '[data-role=\'chat-with-seller\']'
        ]);
    }

    /**
     * @param \Webkul\MpBuyerSellerChat\Model\CustomerData $chatSellerData
     * @param $sellerId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getSellerImage(\Webkul\MpBuyerSellerChat\Model\CustomerData $chatSellerData, $sellerId)
    {
        $defaultImageUrl = $this->assetRepo->getUrlWithParams('Webkul_MpBuyerSellerChat::images/sellerimage.png', []);
        if (!$chatSellerData->getImage() == null) {
            $defaultImageUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .
                'mpchatsystem/profile/' . $sellerId . '/' . $chatSellerData->getImage();
        }
        return $defaultImageUrl;
    }

    /**
     * @param int $customerId
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    private function getCustomer(int $customerId)
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
