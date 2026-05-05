<?php
/**
 *
 */

namespace Branch8\WishlistStockAlert\Helper;

use Branch8\WishlistStockAlert\Model\ConfigData;
use Branch8\WishlistStockAlert\Model\QueueManager;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Pricing\Render;
use Magento\Framework\Pricing\Render\RendererPool;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\ProductAlert\Block\Email\AbstractEmail;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Branch8\WishlistStockAlert\Helper\Logger as CustomLogger;
use function Symfony\Component\String\s;

class Email extends AbstractHelper
{
    const XML_PATH_SENDER = 'wishlist_stock_alert/general/sender_email';
    const XML_PATH_TEMPLATE = 'wishlist_stock_alert/general/email_template';
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var StateInterface
     */
    protected $inlineTranslation;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    protected CustomLogger $logger;

    private ConfigData $configData;

    /**
     * @var Emulation
     */
    protected $_appEmulation;

    private LayoutInterface $_layout;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;
    private $block = null;

    /**
     * @param Context $context
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param ConfigData $configData
     * @param Emulation $appEmulation
     * @param LayoutInterface $layout
     * @param \Magento\Framework\App\State $appState
     * @param CustomLogger $logger
     */
    public function __construct(
        Context                      $context,
        TransportBuilder             $transportBuilder,
        StateInterface               $inlineTranslation,
        StoreManagerInterface        $storeManager,
        CustomerRepositoryInterface  $customerRepository,
        ConfigData                   $configData,
        Emulation                    $appEmulation,
        LayoutInterface              $layout,
        \Magento\Framework\App\State $appState,
        CustomLogger                 $logger
    )
    {
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->configData = $configData;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->_appState = $appState;
        $this->_appEmulation = $appEmulation;
        $this->_layout = $layout;
    }

    /**
     * @return
     */
    private function initLayout()
    {

    }

    /**
     * @param array $job
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendStockAlert(array $job)
    {
        try {
            /**
             * @var $customer \Magento\Customer\Model\Data\Customer
             */
            $enabled = (bool)$this->configData->getConfigValue('enabled', $job['store_id']);
            if (!$enabled || (int)$job['status'] !== QueueManager::STATUS_PENDING) {
                return false;
            }
            $storeId = $job['store_id'];
            $customer = $this->customerRepository->getById($job['customer_id']);
            $extra = $this->gatherData($job);
            $store = $this->storeManager->getStore($storeId);
            $this->_appEmulation->startEnvironmentEmulation($storeId);
            $block = $this->getBlock();
            $block->setStore($store)->reset();
            $block->setStoreId($storeId);
            $block->setItemIds($extra['itemIds']);
            $alertGrid = $this->_appState->emulateAreaCode(
                Area::AREA_FRONTEND,
                [$block, 'toHtml']
            );
            $this->_appEmulation->stopEnvironmentEmulation();
            $this->inlineTranslation->suspend();
            $sender = $this->configData->getConfigValue(
                'sender_email',
                $storeId
            ) ?: 'general';

            $templateId = $this->configData->getConfigValue(
                'email_template',
                $storeId
            ) ?: 'wishlist_stock_alert_general_email_template';
            $templateVars = [
                'customer_name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'service_contact_information' => (string)$this->configData->getConfigValue('service_contact_information', $storeId),
                'disclaimer_text' => (string)$this->configData->getConfigValue('disclaimer_text', $storeId),
                'product_ids' => $extra['productIds'],
                'combo' => $extra['combo'],
                'item_ids' => $extra['itemIds'],
                'store_id' => $store->getId(),
                'store' => $store,
                'alertGrid' => $alertGrid
            ];
            $email = $customer->getEmail();
            $buyerEmail = $customer->getCustomAttribute('buyer_email');
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId()
                ])
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($buyerEmail ? $buyerEmail->getValue() : $email, $customer->getFirstname())
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            return true;
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('Email Send Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    private function getBlock()
    {
        if ($this->block === null) {
            $this->_layout->createBlock(RendererPool::class, 'render.product.prices');
            $render = ObjectManager::getInstance()->create(Render::class);
            $render->setPriceRenderHandle('catalog_product_prices');
            $render->setLayout($this->_layout);
            $this->_layout->addBlock($render, 'product.price.render.default');
            $block = \Branch8\WishlistStockAlert\Block\Wishlist\Email\Items::class;
            $this->block = $this->_layout->createBlock($block);
        }
        return $this->block;
    }

    /**
     * @param $job
     * @return array
     */
    private function gatherData($job)
    {
        try {
            $alert = json_decode($job['data'], true);
            $productIds = [];
            $itemIds = [];
            $combo = [];
            foreach ($alert as $item) {
                $productIds[] = $item['product_id'];
                $itemIds[] = $item['item_id'];
                $combo[$item['product_id']][] = $item['combo'];
            }
            return ['itemIds' => $itemIds, 'productIds' => array_unique($productIds), 'combo' => $combo];
        } catch (\Exception $e) {
            return ['productIds' => $productIds, 'combo' => $combo];
        }
    }
}
