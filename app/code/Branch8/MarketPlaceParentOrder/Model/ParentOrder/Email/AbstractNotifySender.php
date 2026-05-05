<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email;

use Branch8\Customer\Model\GetCustomerNickname;
use Branch8\MarketPlaceParentOrder\Helper\Log;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Manager;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\Sender;
use Magento\Sales\Model\Order\Email\SenderBuilder;
use Magento\Store\Model\App\Emulation;

class AbstractNotifySender
{
    /**
     * @var \Magento\Sales\Model\Order\Email\SenderBuilderFactory
     */
    protected $senderBuilderFactory;

    /**
     * @var Template
     */
    protected $templateContainer;

    /**
     * @var IdentityInterface
     */
    protected $identityContainer;

    /**
     * @var Log
     */
    protected Log $log;

    /**
     * @var Renderer
     */
    protected $addressRenderer;
    /**
     * @var FormatAddress
     */
    protected $formatAddress;
    /**
     * @var Emulation
     */
    protected Emulation $appEmulation;
    /**
     * @var Manager
     */
    protected Manager $eventManager;

    protected ScopeConfigInterface $scopeConfig;

    protected GetCustomerNickname $customerNickname;

    /**
     * @param Template $templateContainer
     * @param IdentityInterface $identityContainer
     * @param \Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\SenderBuilderFactory $senderBuilderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Log $log
     * @param FormatAddress $formatAddress
     * @param Renderer $addressRenderer
     * @param Emulation $emulation
     * @param Manager $eventManager
     */
    public function __construct(
        Template                                                                     $templateContainer,
        IdentityInterface                                                            $identityContainer,
        \Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\SenderBuilderFactory $senderBuilderFactory,
        ScopeConfigInterface                                                         $scopeConfig,
        Log                                                                          $log,
        FormatAddress                                                                $formatAddress,
        Renderer                                                                     $addressRenderer,
        Emulation                                                                    $emulation,
        Manager                                                                      $eventManager,
        GetCustomerNickname                                                          $customerNickname
    )
    {
        $this->templateContainer = $templateContainer;
        $this->identityContainer = $identityContainer;
        $this->senderBuilderFactory = $senderBuilderFactory;
        $this->log = $log;
        $this->addressRenderer = $addressRenderer;
        $this->formatAddress = $formatAddress;
        $this->appEmulation = $emulation;
        $this->eventManager = $eventManager;
        $this->scopeConfig = $scopeConfig;
        $this->customerNickname = $customerNickname;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return bool
     */
    protected function checkAndSend(ParentOrder $parentOrder)
    {
        $this->identityContainer->setStore($parentOrder->getStore());
        if (!$this->identityContainer->isEnabled()) {
            return false;
        }
        $this->prepareTemplate($parentOrder);

        /** @var SenderBuilder $sender */
        $sender = $this->getSender();

        try {
            $sender->send();
        } catch (\Throwable $e) {
            $this->log->logException('NotifySender', $e);
            return false;
        }
        if ($this->identityContainer->getCopyMethod() == 'copy') {
            try {
                $sender->sendCopyTo();
            } catch (\Throwable $e) {
                $this->log->logException('NotifySender', $e, ['send_copy_to' => true]);
            }
        }
        return true;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return void
     */
    protected function prepareTemplate(ParentOrder $parentOrder)
    {
        $this->templateContainer->setTemplateOptions($this->getTemplateOptions());

        if ($parentOrder->getDetail()->getCustomerIsGuest() && $parentOrder->getBillingAddress()) {
            $templateId = $this->identityContainer->getGuestTemplateId();
            $customerName = $parentOrder->getBillingAddress()->getName();
        } else {
            $templateId = $this->identityContainer->getTemplateId();
            $customerName = $parentOrder->getDetail()->getCustomerName();
        }

        $this->identityContainer->setCustomerName($customerName);
        $this->identityContainer->setCustomerEmail($parentOrder->getDetail()->getCustomerEmail());
        $this->templateContainer->setTemplateId($templateId);
    }

    /**
     * Create Sender object using appropriate template and identity.
     *
     * @return Sender
     */
    protected function getSender()
    {
        return $this->senderBuilderFactory->create(
            [
                'templateContainer' => $this->templateContainer,
                'identityContainer' => $this->identityContainer,
            ]
        );
    }

    /**
     * Get template options.
     *
     * @return array
     */
    protected function getTemplateOptions()
    {
        return [
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $this->identityContainer->getStore()->getStoreId()
        ];
    }

    /**
     * @param ParentOrder $parentOrder
     * @return null
     */
    protected function getFormattedShippingAddress(ParentOrder $parentOrder)
    {
        if ($parentOrder->isVirtual() || $parentOrder->getShippingAddress() === null) {
            return null;
        };
        $html = $this->formatAddress->getFormattedAddress($parentOrder->getShippingAddress());
        return $html;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return null
     */
    protected function getFormattedBillingAddress(ParentOrder $parentOrder)
    {
        return $this->formatAddress->getFormattedAddress($parentOrder->getBillingAddress());
    }
}
