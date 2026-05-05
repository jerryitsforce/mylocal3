<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       15/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussionSeller\Model\Email;

use Branch8\MarketPlaceProductDiscussion\Model\Message;
use Branch8\MarketPlaceProductDiscussionSeller\Model\ConfigData;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Sender
{
    const XML_PATH_TEMPLATE = 'vendor_module/email/seller_reply_template';
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    private ConfigData $configData;
    private StateInterface $inlineTranslation;

    private LoggerInterface $logger;
    private ProductRepository $productRepository;

    /**
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ConfigData $configData
     * @param StateInterface $inlineTranslation
     * @param ProductRepository $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        TransportBuilder      $transportBuilder,
        ScopeConfigInterface  $scopeConfig,
        StoreManagerInterface $storeManager,
        ConfigData            $configData,
        StateInterface        $inlineTranslation,
        ProductRepository     $productRepository,
        LoggerInterface       $logger
    )
    {
        $this->productRepository = $productRepository;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->configData = $configData;
        $this->logger = $logger;
    }

    /**
     * @param Thread $thread
     * @param Message $message
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return bool
     */
    public function send(
        Thread                                       $thread,
        Message                                      $message,
        \Magento\Customer\Api\Data\CustomerInterface $customer
    )
    {
       /* $this->inlineTranslation->suspend();*/
        try {
            $emailSender = $this->configData->getValue('seller_reply_identity');
            $template = $this->configData->getValue('reply_template');
            $email = $customer->getEmail();
            $product = $this->productRepository->getById($thread->getProductId());
            $buyerEmail = $customer->getCustomAttribute('buyer_email') ? $customer->getCustomAttribute('buyer_email')->getValue() : '';
            if ($buyerEmail) {
                $email = $buyerEmail;
            }
            $object = new DataObject([
                'customer_first_name' => $customer->getFirstname(),
                'customer_last_name' => $customer->getLastname(),
                'product_name' => $product->getName(),
                'name' => sprintf("%s %s", $customer->getFirstname(), $customer->getLastname()),
                'product_url' => $product->getProductUrl() . "#discussions",
                'question' => $thread->getContent(),
                'reply' => $message->getMessage()
            ]);
            $variables = $object->getData();
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($template)
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $thread->getStoreId()
                    ]
                )
                ->setTemplateVars($variables)
                ->setFrom($emailSender)
                ->addTo($email)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            return true;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        } finally {
            $this->inlineTranslation->resume();
        }
        return false;
    }
}
