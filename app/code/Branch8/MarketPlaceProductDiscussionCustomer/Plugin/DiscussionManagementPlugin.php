<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionCustomer\Plugin;

use Branch8\MarketPlaceProductDiscussion\Api\DiscussionManagementInterface;
use Branch8\MarketPlaceProductDiscussionCustomer\Model\ConfigData;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Psr\Log\LoggerInterface;

class DiscussionManagementPlugin
{
    private ConfigData $configData;
    private TransportBuilder $transportBuilder;
    private StoreManagerInterface $storeManager;
    private StateInterface $inlineTranslation;
    private LoggerInterface $logger;

    public function __construct(
        ConfigData $configData,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface $inlineTranslation,
        LoggerInterface $logger
    ) {
        $this->configData = $configData;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
    }

    /**
     * @param DiscussionManagementInterface $subject
     * @param mixed $result
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Customer\Api\Data\CustomerInterface $seller
     * @param int $customerId
     * @param string $message
     * @param string $threadType
     * @return mixed
     */
    public function afterCreateThread(
        DiscussionManagementInterface $subject,
        $result,
        \Magento\Catalog\Model\Product $product,
        \Magento\Customer\Api\Data\CustomerInterface $seller,
        int $customerId,
        string $message,
        string $threadType = 'question'
    ) {
        if ($threadType !== 'question') {
            return $result;
        }

        try {
            $isEnabled = $this->configData->getValue('enable_email_notification', ConfigData::PREFIX_PATH_CUSTOMER);
            if (!$isEnabled) {
                return $result;
            }

            $templateId = $this->configData->getValue('email_template', ConfigData::PREFIX_PATH_CUSTOMER);
            if (!$templateId) {
                $templateId = 'product_discussion_customer_email_template';
            }

            $store = $this->storeManager->getStore();
            $sellerEmail = $seller->getEmail();
            $sellerName = $seller->getFirstname() . ' ' . $seller->getLastname();

            // Prepare template variables
            $templateVars = [
                'seller_name' => $sellerName,
                'product_name' => $product->getName(),
                'product_url' => $product->getProductUrl(),
                'customer_name' => $result ? $result->getAuthorName() : __('Customer'),
                'question_content' => $message,
                'store_name' => $store->getName(),
            ];

            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope('general') // Or any other sender configured
                ->addTo($sellerEmail, $sellerName)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();

        } catch (\Exception $e) {
            $this->logger->error('Error sending email to seller: ' . $e->getMessage());
        }

        return $result;
    }
}
