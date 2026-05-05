<?php
declare(strict_types=1);

namespace Branch8\GiftToFriend\ViewModel;

use Branch8\HelpDesk\Model\FindYourOrderAction;
use Branch8\HelpDesk\Model\ResourceModel\Category\CollectionFactory;
use Branch8\HelpDesk\Model\Ticket\Priority;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\Customer\Helper\Info as HelperInfo;
use Branch8\GiftToFriend\Helper\Data as HelperGiftBox;

class ServiceFormConfig implements ArgumentInterface
{
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    private $categoryCollection = null;
    /**
     * @var
     */
    private $storeManager;
    /**
     * @var Priority
     */
    private $priority;
    /**
     * @var Session\Proxy
     */
    private Session\Proxy $customerSession;
    /**
     * @var FindYourOrderAction
     */
    private FindYourOrderAction $findYourOrderAction;
    private MaskRulesComposite $maskRulesComposite;

    /**
     * @var HelperInfo
     */
    protected $helperInfo;

    /**
     * @var HelperGiftBox
     */
    protected $giftBoxHelper;

    protected $request;

    protected $parentOrderFactory;

    /**
     * @param UrlInterface $url
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Session\Proxy $customerSession
     * @param Priority $priority
     * @param FindYourOrderAction $findYourOrderAction
     * @param MaskRulesComposite $maskRulesComposite
     * @param HelperInfo $helperInfo
     * @param HelperGiftBox $giftBoxHelper
     */
    public function __construct(
        UrlInterface          $url,
        CollectionFactory     $collectionFactory,
        StoreManagerInterface $storeManager,
        Session\Proxy         $customerSession,
        Priority              $priority,
        FindYourOrderAction   $findYourOrderAction,
        MaskRulesComposite    $maskRulesComposite,
        HelperInfo            $helperInfo,
        HelperGiftBox $giftBoxHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderFactory
    )
    {
        $this->findYourOrderAction = $findYourOrderAction;
        $this->customerSession = $customerSession;
        $this->priority = $priority;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $collectionFactory;
        $this->url = $url;
        $this->maskRulesComposite = $maskRulesComposite;
        $this->helperInfo = $helperInfo;
        $this->giftBoxHelper = $giftBoxHelper;
        $this->request = $request;
        $this->parentOrderFactory = $parentOrderFactory;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfigurations()
    {
        $orderId = $this->request->getParam('id', null);
        $parentOrder = $this->parentOrderFactory->create()->load($orderId);
        $detail = $parentOrder->getDetail();
        $orderData = [
            'increment_id' => '',
            'id' => '',
            'recipient_name' => '',
            'recipient_phone' => ''
        ];
        if($detail){
            $orderData = [
                'increment_id' => $detail->getData('increment_id'),
                'id' => $detail->getData('parent_id'),
                'recipient_name' => $detail->getData('recipient_name'),
                'recipient_phone' => $detail->getData('recipient_telephone')
            ];
        }
        return [
            'ajaxSubmitNewTicketUrl' => $this->url->getUrl('gift-order/giftbox/save'),
            'categoryOptions' => $this->getTicketCategories(),
            'priorityOptions' => $this->getPriorityOptions(),
            'maxFileUploadText' => __('Attachment (Up to 2.0 MB per image.Acceptable image formats: jpg, jpeg, png)'),
            'customer' => [],//$this->getDefaultCustomerData()
            'orderIncrementId' => $orderData['increment_id'],
            'orderId' => $orderData['id'],
            'recipientName' => $orderData['recipient_name'],
            'recipientPhone' => $orderData['recipient_phone']
        ];
    }

    /**
     * getTicketCategories for category options
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getTicketCategories()
    {   if(!$this->giftBoxHelper->isFeatureEnable()) {
            return [];
        }
        $giftTicketCategory = $this->giftBoxHelper->getConfigData(\Branch8\GiftToFriend\Helper\Data::XML_PATH_CUSTOMER_SERVICE_FORM_GIFT_CATEGORY);
        if (!$giftTicketCategory || $giftTicketCategory == '') {
            return [];
        }

        if ($this->categoryCollection == null) {
            $this->categoryCollection = [];
            /**
             * @var $collection \Branch8\HelpDesk\Model\ResourceModel\Category\Collection
             */
            $collection = $this->categoryCollectionFactory->create()
                ->addFieldToFilter('is_active', 1)
                ->addStoreFilter($this->storeManager->getStore()->getId(), true)
                ->load();

            foreach ($collection as $item) {
                if ($item->getCategoryId() != $giftTicketCategory) {
                    continue;
                }
                $this->categoryCollection[] = ['label' => $item->getTitle(), 'value' => $item->getId()];
            }
        }
        return $this->categoryCollection;
    }

    /**
     * GetPriorityOptions
     * @return array[]
     */
    private function getPriorityOptions()
    {
        return $this->priority->toOptionArray();
    }

    /**
     * GetDefaultCustomerData
     * @return array;
     */
    private function getDefaultCustomerData()
    {
        $default = [
            'name' => '',
            'phone' => '',
            'email' => ''
        ];
        if ($customer = $this->customerSession->getCustomer()) {
            $default['name'] = $customer->getName();
            $default['phone'] = $this->resolveDefaultCustomerPhone($customer);
            $default['email'] = $this->resolveEmail($customer);
            $default['name_masked'] = $this->helperInfo->getOAuthName('',$customer->getName());
            $default['email_masked'] = $this->helperInfo->getOAuthEmail($default['email']);
            $default['phone_masked'] = $this->helperInfo->getOAuthPhone($default['phone']);

        }
        return $default;
    }

    /**
     * @param $customer
     * @return void
     */
    private function resolveEmail($customer)
    {
        /**
         * @var $customer \Branch8\Customer\Model\Customer
         */
        if($customer->getBuyerEmail()){
            return $customer->getBuyerEmail();
        }
        return $customer->getEmail();
    }

    /**
     * Get  default phone prior
     * @param Customer $customer
     * @return string
     */
    private function resolveDefaultCustomerPhone(Customer $customer)
    {
        $defaultShippingAddress = $customer->getDefaultShippingAddress();
        $defaultBillingAddress = $customer->getDefaultBillingAddress();
        $defaultPhone = '';
        if ($phoneNumber = $customer->getData('phone_number')) {
            $defaultPhone = $phoneNumber;
        } elseif ($defaultShippingAddress) {
            $defaultPhone = $defaultShippingAddress->getTelephone();
        } elseif ($defaultBillingAddress) {
            $defaultPhone = $defaultBillingAddress->getTelephone();
        }
        return $defaultPhone;
    }

    /**
     * get Config for Find Your Order Component
     * @return array
     */
    public function getFindYourOrderConfig()
    {
        $recentlyOrders = [];
        foreach ($this->findYourOrderAction->execute(
            $this->customerSession->getCustomer()->getId(),
            3,
            1,
            '',
            'entity_id',
            'DESC'
        ) as $item) {
            $recentlyOrders[] = [
                'value' => $item->getId(),
                'label' => $item->getIncrementId()
            ];
        }
        return [
            'searchUrl' => $this->url->getUrl('helpdesk/ticket/LoadRecentOrders'),
            'recentlyOrders' => $recentlyOrders
        ];
    }

    public function getUploaderConfig()
    {
        return [
            'url' => $this->url->getUrl('helpdesk/message/postAttachmentGuest')
        ];
    }
}
