<?php

namespace Branch8\Smtp\Controller\Adminhtml\Smtp\AbandonedCart;

use Branch8\Customer\Model\GetCustomerNickname;
use Magento\Backend\App\Action\Context;
use Magento\Email\Model\Template;
use Magento\Email\Model\Template\SenderResolver;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Quote\Model\QuoteRepository;
use Mageplaza\Smtp\Helper\EmailMarketing;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Helper\Image;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\App\State;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Branch8\Smtp\Block\AbandonedCart\AbandonedCart;
class Send extends \Mageplaza\Smtp\Controller\Adminhtml\Smtp\AbandonedCart\Send
{
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var AreaList
     */
    protected $areaList;

    /**
     * @var Template
     */
    protected $emailTemplate;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SenderResolver
     */
    protected $senderResolver;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var EmailMarketing
     */
    protected $helperEmailMarketing;

    /**
     * @var Emulation
     */
    protected $appEmulation;

    /**
     * @var State
     */
    protected $_appState;

    /**
     * @var LayoutInterface
     */
    protected $_layout;

    /**
     * @var GetCustomerNickname
     */
    protected $getCustomerNickname;

    /**
     * Send constructor.
     *
     * @param Context $context
     * @param QuoteRepository $quoteRepository
     * @param AreaList $areaList
     * @param Template $emailTemplate
     * @param LoggerInterface $logger
     * @param SenderResolver $senderResolver
     * @param TransportBuilder $transportBuilder
     * @param Registry $registry
     * @param EmailMarketing $helperEmailMarketing
     * @param Image $imageHelper
     */
    public function __construct(
        Context $context,
        QuoteRepository $quoteRepository,
        AreaList $areaList,
        Template $emailTemplate,
        LoggerInterface $logger,
        SenderResolver $senderResolver,
        TransportBuilder $transportBuilder,
        Registry $registry,
        EmailMarketing $helperEmailMarketing,
        Emulation $appEmulation,
        State $state,
        LayoutInterface $layout,
        GetCustomerNickname $getCustomerNickname
    ) {
        parent::__construct(
            $context,
            $quoteRepository,
            $areaList,
            $emailTemplate,
            $logger,
            $senderResolver,
            $transportBuilder,
            $registry,
            $helperEmailMarketing
        );
        $this->appEmulation = $appEmulation;
        $this->_appState = $state;
        $this->_layout = $layout;
        $this->getCustomerNickname = $getCustomerNickname;
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id', 0);

        try {
            $quote = $this->quoteRepository->get($id);
            $customerEmail = $quote->getCustomerEmail();
            $customerName = $this->helperEmailMarketing->getCustomerName($quote);
            if ($quote->getCustomerIsGuest()) {
                $customerNickname = '';
            } else {
                $customer = $quote->getCustomer();
                $customerNickname = $customer->getCustomAttribute("nickname")->getValue();
            }

            $from = $this->getRequest()->getParam('sender');
            $templateId = $this->getRequest()->getParam('email_template');
            $additionalMessage = $this->getRequest()->getParam('additional_message');
            $from = $this->senderResolver->resolve($from, $quote->getStoreId());
            $recoveryUrl = $this->helperEmailMarketing->getRecoveryUrl($quote);
            $block = $this->createBlock(AbandonedCart::class);
            $block->setQuote($quote);
            $template = $this->_appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$block, 'toHtml']
            );
            $vars = [
                'quote_id' => $quote->getId(),
                'customer_name' => $this->getCustomerNickname->getCustomerNicknameByCustomerId($quote->getCustomerId()),
                'additional_message' => trim(strip_tags($additionalMessage)),
                'cart_recovery_link' => $recoveryUrl,
                'template' => $template
            ];

            $areaObject = $this->areaList->getArea($this->emailTemplate->getDesignConfig()->getArea());
            $areaObject->load(Area::PART_TRANSLATE);

            $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $quote->getStoreId()])
                ->setFrom($from)
                ->addTo($customerEmail, $customerName)
                ->setTemplateVars($vars)
                ->getTransport();

            $this->registry->register('smtp_abandoned_cart', $quote);
            $transport->sendMessage();
            $this->messageManager->addSuccessMessage(__('Cart recovery email was sent to the customer successfully!'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Cart recovery email cannot sent to the customer.'));
            $this->logger->error($e->getMessage());
        }

        return $this->_redirect('adminhtml/smtp_abandonedcart/view', ['id' => $id]);
    }

    /**
     * Create block instance
     *
     * @param string|AbstractBlock $block
     * @return AbstractBlock
     * @throws LocalizedException
     */
    public function createBlock($block)
    {
        if (is_string($block)) {
            if (class_exists($block)) {
                $block = $this->_layout->createBlock($block);
            }
        }
        if (!$block instanceof AbstractBlock) {
            throw new LocalizedException(__('Invalid block type: %1', $block));
        }
        return $block;
    }
}
