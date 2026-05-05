<?php

namespace Branch8\Rma\Helper;

use Branch8\Customer\Model\GetCustomerNickname;
use Branch8\Rma\Model\Rma\Status;
use Magento\Framework\App\Helper\Context;
use Magento\Backend\Model\UrlInterface;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Log option value for this helper.
     */
    private const LOG_OPTION = 'Email';

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    protected $_timezone;

    protected $_priceCurrency;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;


    private GetCustomerNickname $getCustomerNickname;

    private \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $collectionFactory;

    private \Magento\Customer\Model\CustomerFactory $customerFactory;


    private UrlInterface $backendUrl;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;


    public function __construct(
        Context                                            $context,
        \Magento\Framework\Translate\Inline\StateInterface               $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder                $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface                       $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface             $timezone, // Add this
        \Magento\Framework\Pricing\PriceCurrencyInterface                $priceCurrency,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface             $localeDate,
        \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $collectionFactory,
        GetCustomerNickname                                              $getCustomerNickname,
        \Magento\Customer\Model\CustomerFactory                          $customerFactory,
        UrlInterface                                                     $backendUrl,
        \Magento\Framework\UrlInterface                                  $urlBuilder
    )
    {
        parent::__construct($context);
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->_timezone = $timezone; // Initialize timezone
        $this->_priceCurrency = $priceCurrency;
        $this->_localeDate = $localeDate;
        $this->getCustomerNickname = $getCustomerNickname;
        $this->collectionFactory = $collectionFactory;
        $this->customerFactory = $customerFactory;
        $this->backendUrl = $backendUrl;
        $this->urlBuilder = $urlBuilder;
    }


    public function sendDeclinedEmail($items, $rmaId, $declineReason, $declineDetails, $order, $changedStatus)
    {
        try {
            // Disable inline translation while sending the email to avoid conflicts
            $this->_inlineTranslation->suspend();

            // Get the store information
            $storeId = $this->_storeManager->getStore()->getId();

            // Fetch the email template from system configuration
            if($changedStatus == Status::RETURN_APPLY_DECLINE){
                $templateId = $this->scopeConfig->getValue(
                    'mprmasystem/email/reject_by_seller',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            }else{
                $templateId = $this->scopeConfig->getValue(
                    'mprmasystem/email/reject_exchange_by_seller',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            }


            // Prepare email template variables
            $emailTemplateVariables = [
                'customer_name' => $this->getCustomerNickname->getCustomerNicknameByCustomerId($order->getCustomerId()),
                'decline_reason' => $declineReason,
                'decline_details' => $declineDetails,
                'rma_id' => $rmaId,
                'order_id' => $order->getIncrementId(),
                'order_date' => $this->formatDate($order->getCreatedAt()), // Format order date
                'order_total' => $this->formatPrice($order->getGrandTotal()), // Format price
                'order_items' => $this->getOrderItemsList($items), // Get list of order items
                'order_link' => $this->getOrderUrlDetail($order->getHotaiChildOrderNumber()),
            ];

            // Get the sender information (for example, from the config)
            $sender = [
                'name' => $this->scopeConfig->getValue('trans_email/ident_support/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'email' => $this->scopeConfig->getValue('trans_email/ident_support/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            ];

            // Build the transport for email
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($templateId) // Set the email template identifier from the system config
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $storeId
                    ]
                )
                ->setTemplateVars($emailTemplateVariables) // Set the template variables
                ->setFrom($sender) // Sender information
                ->addTo($order->getCustomerEmail(), $order->getCustomerName()) // Send email to the customer
                ->getTransport();

            // Send the email
            $transport->sendMessage();

            // Re-enable inline translation
            $this->_inlineTranslation->resume();

            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->info('Declined RMA email sent successfully for RMA ID: ' . $rmaId, self::LOG_OPTION);

        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaId]);

            // Ensure that inline translation is resumed even if there was an error
            $this->_inlineTranslation->resume();
        }
    }


    /**
     * @param $id
     * @return string|null
     */
    public function getOrderUrlDetail($hotaiParentOrderNumber){
        if($hotaiParentOrderNumber){
            return $this->_urlBuilder->getUrl('sales/parentOrder/history', ['_query' => ['search' => $hotaiParentOrderNumber]]);
        }
        return $this->_urlBuilder->getUrl('sales/parentOrder/history');
    }

    /**
     * Get formatted list of order items for email template
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    protected function getOrderItemsList($items)
    {
        $itemsHtml = '<ul>'; // Start an unordered list

        // Loop through each item in the array
        foreach ($items as $item) {
            $itemsHtml .= '<li>'  . $item->getName(). ' x '.(int)$item->getQtyOrdered() . '</li>'; // Use <li> for each item
        }

        $itemsHtml .= '</ul>'; // Close the unordered list

        return $itemsHtml; // Return the formatted HTML
    }

    /**
     * @param $createdAt
     * @return string
     */
    public function formatDate($createdAt)
    {
        try {
            return $this->_localeDate->date($createdAt)->format('Y/m/d H:i');
        }catch (\Exception $exception){
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
            return '';
        }
    }


    /**
     * Format the price for display
     *
     * @param float $price
     * @return string
     */
    protected function formatPrice($price)
    {
        return $this->_priceCurrency->format($price, false, 2);
    }


    /**
     * Send email notification to seller when admin creates RMA
     *
     * @param array $requestItemInformation
     * @param array $postData
     * @return void
     */
    public function sendNewRmaNotifyEmailToSeller($requestItemInformation, $postData, $id = false)
    {
        try {
            // Get seller information
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('order_id', $postData['order_id']);
            $seller = $this->customerFactory->create()->load($collection->getFirstItem()->getData('seller_id'));
            if(empty($seller) || !$seller->getId() || !$seller->getEmail()){
                return ;
            }
            // Disable inline translation
            $this->_inlineTranslation->suspend();

            $storeId = $this->_storeManager->getStore()->getId();

            // Get email template ID from config
            $templateId = $this->scopeConfig->getValue(
                'mprmasystem/email/rma_request_from_be_for_seller', //used for case : customer create rma on FE too
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );


            $number = count($postData['list_rma_items']);
            $message = (int)$postData['resolution_type'] == 1
                ? __('您在HOTAI購有%1個退貨申請，請前往後台處理退貨申請', $number)
                : __('您在HOTAI購有%1個換貨申請，請前往後台處理換貨申請', $number);

            $isCreateByCustomer = $postData['create_by_customer'] ?? false;

            $emailSubject = $isCreateByCustomer
                ? (int)$postData['resolution_type'] == 1
                    ? __("You have a new return request notification")
                    : __("You have a new exchange request notification")
                : __("後台主動取消訂單/退貨信");

            // Prepare items data for email
            $items = [];
            foreach ($postData['list_rma_items'] as $item) {
                $items[] = [
                    'name' => $item['name'],
                    'specification_name' => $item['name'] ?? '',
                    'sku' => $item['sku'],
                    'qty' => $requestItemInformation[$item['item_id']][0]['qty']
                ];
            }
            $rmaUrl = $id ? $this->urlBuilder->getBaseUrl(). 'mprmasystem/seller/rma/'. $id : $this->urlBuilder->getBaseUrl(). 'mprmasystem/seller/rma';
            // Prepare email variables exactly as template requires
            $emailTemplateVariables = [
                'email_subject' => $emailSubject->render(),
                'seller_name' => $seller->getName(),
                'message' => $message->render(),
                'number' => count($postData['list_rma_items']),
                'seller_backend_url' => $rmaUrl,
                'order_date' => $this->_localeDate->date($postData['rma_order_choose_grid']['created_at'])->format('Y/m/d H:i'),
                'sub_order_number' => $postData['order_increment_id'],
                'items' => $items
            ];

            // Get sender information
            $sender = [
                'name' => $this->scopeConfig->getValue('trans_email/ident_support/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'email' => $this->scopeConfig->getValue('trans_email/ident_support/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ];

            // Build and send email
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId
                ])
                ->setTemplateVars($emailTemplateVariables)
                ->setFrom($sender)
                ->addTo($seller->getEmail(), $seller->getName())
                ->getTransport();

            $transport->sendMessage();

            // Re-enable inline translation
            $this->_inlineTranslation->resume();

        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__);
            $this->_inlineTranslation->resume();
        }
    }


}
