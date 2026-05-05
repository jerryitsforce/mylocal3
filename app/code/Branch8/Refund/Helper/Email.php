<?php

namespace Branch8\Refund\Helper;

use Magento\Contact\Model\ConfigInterface;
use Magento\Email\Model\Template\SenderResolver;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Sends refund-related transactional emails (customer + abnormal admin).
 */
class Email extends AbstractHelper
{
    public const REFUND_EMAIL_ENABLED = 'sales_email/refund/enabled';
    public const REFUND_EMAIL_IDENTITY = 'sales_email/refund/identity';
    public const REFUND_ABNORMAL_EMAIL_RECEIVERS = 'sales_email/refund/abnormal_mail_receivers';

    public const REFUND_SUCCESS_EMAIL_TEMPLATE = 'sales_email_refund_success_template';
    public const REFUND_DECLINE_EMAIL_TEMPLATE = 'sales_email_refund_decline_template';
    public const REFUND_ABNORMAL_EMAIL_TEMPLATE = 'sales_email_refund_abnormal_template';
    public const REFUND_CANCEL_EMAIL_TEMPLATE = 'sales_email_refund_cancel_template';
    public const REFUND_TRIGGER_FAIL_EMAIL_TEMPLATE = 'sales_email_refund_trigger_fail_template';

    public const DEFAULT_IDENTITY = 'general';

    private const LOG_CLASS_KEY = 'Email';

    /** @var StateInterface */
    protected $inlineTranslation;

    /** @var Escaper */
    protected $escaper;

    /** @var TransportBuilder */
    protected $transportBuilder;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var ScopeConfigInterface - parent uses $scopeConfig from AbstractHelper; keep alias for clarity in older code */
    protected $scopeConfigInterface;

    /** @var SenderResolver */
    protected $senderResolver;

    /** @var string */
    public $emailTemplateId = '';

    /** @var array<string, mixed> */
    public $emailVars = [];

    /** @var array|string */
    public $emailReceivers = '';

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /**
     * @param Context $context Helper context (scopeConfig, logger, etc.)
     * @param StateInterface $inlineTranslation Inline translation suspend/resume
     * @param Escaper $escaper String escaper
     * @param TransportBuilder $transportBuilder Mail transport builder
     * @param StoreManagerInterface $storeManager Store resolver
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface Scope config (duplicate of parent; kept for BC)
     * @param SenderResolver $senderResolver From identity resolver
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        SenderResolver $senderResolver,
        ConfigurableRefundLogger $refundLogger
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->senderResolver = $senderResolver;
        $this->refundLogger = $refundLogger;
    }

    /**
     * Send the configured refund email if feature is enabled in sales_email/refund.
     *
     * @return void
     */
    public function send()
    {
        if (!$this->isEnableRefundMail()) {
            return;
        }

        try {
            $this->inlineTranslation->suspend();
            $this->transportBuilder();

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'send');
        }
    }

    /**
     * @return bool
     */
    private function isEnableRefundMail()
    {
        return (bool) $this->scopeConfig->getValue(self::REFUND_EMAIL_ENABLED, ScopeInterface::SCOPE_STORE) ?? self::DEFAULT_IDENTITY;
    }

    /**
     * Configure template, vars, sender and recipients on the transport builder.
     *
     * @return void
     */
    protected function transportBuilder()
    {
        $this->setEmailTemplate();
        $this->setEmailOptions();
        $this->setTemplateVars();
        $this->setSender();
        $this->setReceivers();
    }

    /**
     * @return void
     */
    public function setEmailTemplate()
    {
        $this->transportBuilder->setTemplateIdentifier($this->emailTemplateId);
    }

    /**
     * @return void
     */
    public function setEmailOptions()
    {
        $this->transportBuilder
            ->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId(),
                ]
            );
    }

    /**
     * @return void
     */
    private function setReceivers()
    {
        $this->transportBuilder->addTo($this->emailReceivers);
    }

    /**
     * @return void
     */
    public function setTemplateVars()
    {
        $varsObj = new DataObject($this->emailVars);
        $this->transportBuilder->setTemplateVars($varsObj->getData());
    }

    /**
     * @return void
     */
    private function setSender()
    {
        $this->transportBuilder->setFrom($this->senderIdentity());
    }

    /**
     * @return array<string, mixed>
     */
    private function senderIdentity()
    {
        $identity = $this->scopeConfig->getValue(self::REFUND_EMAIL_IDENTITY, ScopeInterface::SCOPE_STORE) ?? self::DEFAULT_IDENTITY;

        return $this->senderResolver->resolve($identity, $this->storeManager->getStore()->getId());
    }

    /**
     * @param string $emailTemplateId Template id string
     * @return void
     */
    public function setEmailTemplateId(string $emailTemplateId)
    {
        $this->emailTemplateId = $emailTemplateId;
    }

    /**
     * @param array<string, mixed> $emailVars Template variables
     * @return void
     */
    public function setEmailVars(array $emailVars)
    {
        $this->emailVars = $emailVars;
    }

    /**
     * @param array|string $emailReceivers Recipient(s)
     * @return void
     */
    public function setEmailReceivers($emailReceivers)
    {
        $this->emailReceivers = $emailReceivers;
    }

    /**
     * Comma-separated abnormal receivers from config, fallback to contact form recipient.
     *
     * @return string
     */
    public function getAbnormalAdminReceviers()
    {
        return $this->scopeConfig->getValue(self::REFUND_ABNORMAL_EMAIL_RECEIVERS, ScopeInterface::SCOPE_STORE)
            ?? $this->scopeConfig->getValue(ConfigInterface::XML_PATH_EMAIL_RECIPIENT, ScopeInterface::SCOPE_STORE);
    }
}
