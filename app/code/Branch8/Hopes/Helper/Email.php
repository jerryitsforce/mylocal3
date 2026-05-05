<?php

namespace Branch8\Hopes\Helper;
use Branch8\Customer\Model\GetCustomerNickname;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Area;

class Email extends AbstractHelper
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'Email';

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

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceCurrency;
    private GetCustomerNickname $customerNickname;
    private Log $hopesLog;

    /**
     * @param Context $context
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param GetCustomerNickname $customerNickname
     * @param Log $hopesLog
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        GetCustomerNickname $customerNickname,
        Log $hopesLog
    ) {
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->_priceCurrency = $priceCurrency;
        $this->customerNickname = $customerNickname;
        $this->hopesLog = $hopesLog;
        parent::__construct($context);
    }

    /**
     * Send store pickup notification email
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param string $trackingNumber
     * @param array $data
     * @return void
     */
    public function sendStorePickupEmail($shipment, $trackingNumber, $data)
    {
        try {
            $order = $shipment->getOrder();
            $storeId = $order->getStoreId();

            $this->_inlineTranslation->suspend();

            // Get template ID from system config
            $templateId = $this->scopeConfig->getValue(
                'carriers/hotai_711/email_get_product',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );

            // Prepare email template variables
            $templateVars = [
                'customer_name' => $this->customerNickname->getCustomerNicknameByCustomerId($order->getCustomerId()),
                'tracking_number' => $trackingNumber,
                'store_name' => $data['StoreName'] ?? '',
                'store_address' => $data['StoreAddress'] ?? '',
                'recipient_name' => $order->getShippingAddress()->getName(),
                'recipient_phone' => $order->getShippingAddress()->getTelephone(),
                'order_number' => $order->getIncrementId(),
                'order_date' => $this->formatDate($order->getCreatedAt()),
                'order_total' => $this->formatPrice($order->getGrandTotal()),
                'order_items' => $this->getOrderItemsList($order->getAllVisibleItems()),
                'order_data' => [
                    'url_history' => $this->getOrderUrlDetail($order->getIncrementId())
                ]
            ];

            // Get sender info from config
            $sender = [
                'name' => $this->scopeConfig->getValue('trans_email/ident_support/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'email' => $this->scopeConfig->getValue('trans_email/ident_support/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ];

            // Build and send email
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId
                ])
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo(
                    $order->getCustomerEmail(),
                    $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()
                )
                ->getTransport();

            $transport->sendMessage();

            $this->_inlineTranslation->resume();

            $this->hopesLog->log(
                self::LOG_TYPE,
                'Store pickup notification email sent for order: ' . $order->getIncrementId()
            );

        } catch (\Exception $e) {
            $this->hopesLog->logException(
                self::LOG_TYPE,
                $e,
                ['order_increment_id' => $shipment->getOrder()->getIncrementId() ?? null]
            );
            $this->_inlineTranslation->resume();
        }
    }

    /**
     * Format date
     *
     * @param string $date
     * @return string
     */
    public function formatDate($date)
    {
        try {
            return $this->_localeDate->date($date)->format('Y/m/d H:i');
        } catch (\Exception $exception) {
            $this->hopesLog->logException(self::LOG_TYPE, $exception, ['date' => $date]);
            return '';
        }
    }

    /**
     * Format price
     *
     * @param float $price
     * @return string
     */
    protected function formatPrice($price)
    {
        return $this->_priceCurrency->format($price, false, 2);
    }

    /**
     * Get order history URL
     *
     * @param string $orderNumber
     * @return string
     */
    public function getOrderUrlDetail($orderNumber)
    {
        return $this->_urlBuilder->getUrl(
            'sales/parentOrder/history',
            ['_query' => ['search' => $orderNumber]]
        );
    }

    /**
     * Get formatted list of order items
     *
     * @param array $items
     * @return string
     */
    protected function getOrderItemsList($items)
    {
        $itemsHtml = '<ul>';
        $i = 1;
        foreach ($items as $item) {
            $itemsHtml .= '<li>' . $i . '. ' . $item->getName() . ' X ' . (int)$item->getQtyOrdered() . '</li>';
            $i++;
        }
        $itemsHtml .= '</ul>';
        return $itemsHtml;
    }
}
