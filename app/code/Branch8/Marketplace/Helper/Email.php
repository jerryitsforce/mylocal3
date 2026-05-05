<?php
namespace Branch8\Marketplace\Helper;

use Webkul\Marketplace\Helper\Email as BaseEmailHelper;

/**
 * YourVendor YourModule Helper Email.
 */
class Email extends BaseEmailHelper
{
    public const XML_PATH_ENABLE_SELLER_APPROVE_TEMPLATE = 'marketplace/email/enable_seller_approve_template';
    public const XML_PATH_ENABLE_SELLER_BECOME_TEMPLATE = 'marketplace/email/enable_becomeseller_request_template';
    public const XML_PATH_ENABLE_SELLER_DISAPPROVE_TEMPLATE = 'marketplace/email/enable_seller_disapprove_template';
    public const XML_PATH_ENABLE_SELLER_PROCESS_TEMPLATE = 'marketplace/email/enable_seller_process_template';
    public const XML_PATH_ENABLE_SELLER_DENY_TEMPLATE = 'marketplace/email/enable_seller_deny_template';
    public const XML_PATH_ENABLE_PRODUCT_DENY_TEMPLATE = 'marketplace/email/enable_product_deny_template';
    public const XML_PATH_ENABLE_NEW_PRODUCT_TEMPLATE = 'marketplace/email/enable_new_product_template';
    public const XML_PATH_ENABLE_EDIT_PRODUCT_TEMPLATE = 'marketplace/email/enable_edit_product_template';
    public const XML_PATH_ENABLE_ASK_PRODUCT_QUERY_TEMPLATE = 'marketplace/email/enable_ask_productquery_seller_template';
    public const XML_PATH_ENABLE_ASK_SELLER_QUERY_TEMPLATE = 'marketplace/email/enable_askquery_seller_template';
    public const XML_PATH_ENABLE_ASK_ADMIN_QUERY_TEMPLATE = 'marketplace/email/enable_askquery_admin_template';
    public const XML_PATH_ENABLE_PRODUCT_APPROVE_TEMPLATE = 'marketplace/email/enable_product_approve_template';
    public const XML_PATH_ENABLE_PRODUCT_DISAPPROVE_TEMPLATE = 'marketplace/email/enable_product_disapprove_template';
    public const XML_PATH_ENABLE_ORDER_PLACED_TEMPLATE = 'marketplace/email/enable_order_placed_template';
    public const XML_PATH_ENABLE_ORDER_INVOICED_TEMPLATE = 'marketplace/email/enable_order_invoiced_template';
    public const XML_PATH_ENABLE_SELLER_TRANSACTION_TEMPLATE = 'marketplace/email/enable_seller_transaction_template';
    public const XML_PATH_ENABLE_LOW_STOCK_TEMPLATE = 'marketplace/email/enable_low_stock_template';
    public const XML_PATH_ENABLE_WITHDRAWAL_REQUEST_TEMPLATE = 'marketplace/email/enable_withdrawal_request_template';
    public const XML_PATH_ENABLE_PRODUCT_FLAG_TEMPLATE = 'marketplace/email/enable_product_flag_template';
    public const XML_PATH_ENABLE_SELLER_FLAG_TEMPLATE = 'marketplace/email/enable_seller_flag_template';

    public const XML_PATH_ENABLE_AUTO_APPROVE_EMAIL_TEMPLATE = 'marketplace/email/enable_becomeseller_request_auto_approve_template';
    public const XML_PATH_EMAIL_AUTO_APPROVE_TEMPLATE = 'marketplace/email/becomeseller_request_auto_approve_notification_template';

    /**
     * Override sendSellerApproveMail to check Yes/No config before sending email.
     */
    public function sendSellerApproveMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_SELLER_APPROVE_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendSellerApproveMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    public function sendNewSellerRequest($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_SELLER_BECOME_TEMPLATE, $this->getStore()->getId());
        if($isEnabled) {
            parent::sendNewSellerRequest($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendSellerDisapproveMail to check Yes/No config before sending email.
     */
    public function sendSellerDisapproveMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_SELLER_DISAPPROVE_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendSellerDisapproveMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendSellerProcessingMail to check Yes/No config before sending email.
     */
    public function sendSellerProcessingMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_SELLER_PROCESS_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendSellerProcessingMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendSellerDenyMail to check Yes/No config before sending email.
     */
    public function sendSellerDenyMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_SELLER_DENY_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendSellerDenyMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendProductDenyMail to check Yes/No config before sending email.
     */
    public function sendProductDenyMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_PRODUCT_DENY_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendProductDenyMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendNewProductMail to check Yes/No config before sending email.
     */
    public function sendNewProductMail($emailTemplateVariables, $senderInfo, $receiverInfo, $editFlag)
    {
        $isEnabled = $this->getConfigValue($editFlag ? self::XML_PATH_ENABLE_EDIT_PRODUCT_TEMPLATE : self::XML_PATH_ENABLE_NEW_PRODUCT_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendNewProductMail($emailTemplateVariables, $senderInfo, $receiverInfo, $editFlag);
        }
    }

    /**
     * Override sendQuerypartnerEmail to check Yes/No config before sending email.
     */
    public function sendQuerypartnerEmail($data, $emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(isset($data['product-id']) ? self::XML_PATH_ENABLE_ASK_PRODUCT_QUERY_TEMPLATE : self::XML_PATH_ENABLE_ASK_SELLER_QUERY_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendQuerypartnerEmail($data, $emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override askQueryAdminEmail to check Yes/No config before sending email.
     */
    public function askQueryAdminEmail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_ASK_ADMIN_QUERY_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::askQueryAdminEmail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendProductStatusMail to check Yes/No config before sending email.
     */
    public function sendProductStatusMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_PRODUCT_APPROVE_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendProductStatusMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendProductUnapproveMail to check Yes/No config before sending email.
     */
    public function sendProductUnapproveMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_PRODUCT_DISAPPROVE_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendProductUnapproveMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendPlacedOrderEmail to check Yes/No config before sending email.
     */
    public function sendPlacedOrderEmail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_ORDER_PLACED_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendPlacedOrderEmail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendInvoicedOrderEmail to check Yes/No config before sending email.
     */
    public function sendInvoicedOrderEmail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_ORDER_INVOICED_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendInvoicedOrderEmail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendSellerPaymentEmail to check Yes/No config before sending email.
     */
    public function sendSellerPaymentEmail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_SELLER_TRANSACTION_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendSellerPaymentEmail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendLowStockNotificationMail to check Yes/No config before sending email.
     */
    public function sendLowStockNotificationMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_LOW_STOCK_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendLowStockNotificationMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendWithdrawalRequestMail to check Yes/No config before sending email.
     */
    public function sendWithdrawalRequestMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_WITHDRAWAL_REQUEST_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendWithdrawalRequestMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendProductFlagMail to check Yes/No config before sending email.
     */
    public function sendProductFlagMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_PRODUCT_FLAG_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendProductFlagMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    /**
     * Override sendSellerFlagMail to check Yes/No config before sending email.
     */
    public function sendSellerFlagMail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_SELLER_FLAG_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            parent::sendSellerFlagMail($emailTemplateVariables, $senderInfo, $receiverInfo);
        }
    }

    public function sendSellerAutoApprovalEmail($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_ENABLE_AUTO_APPROVE_EMAIL_TEMPLATE, $this->getStore()->getId());
        if ($isEnabled) {
            $this->_template = $this->getTemplateId(self::XML_PATH_EMAIL_AUTO_APPROVE_TEMPLATE);
            $this->_inlineTranslation->suspend();
            $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);
            try {
                $transport = $this->_transportBuilder->getTransport();
                $transport->sendMessage();
            } catch (\Exception $e) {
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Branch8\Marketplace\Service\MarketplaceLogger::class)
                    ->logException('Email', $e);
                $this->_messageManager->addError($e->getMessage());
            }
            $this->_inlineTranslation->resume();
        }
    }
}
