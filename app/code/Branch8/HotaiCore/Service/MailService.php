<?php

namespace Branch8\HotaiCore\Service;

use AllowDynamicProperties;
use Branch8\HotaiCore\Helper\DebugLog;
use Branch8\HotaiCore\Model\Config\Source\LogOption;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Log_Exception;

#[AllowDynamicProperties] class MailService
{
    /**
     * 寄件人
     */
    public const DEFAULT_SEND_NAME = 'Hotai購 Notification';

    /**
     * 寄件人 Email
     */
    public const DEFAULT_SEND_EMAIL = 'notification@hotaigo.com.tw';

    private const DEBUG_LOG_OPTION = LogOption::LOG_MAIL_SERVICE;

    /**
     * Sender Name
     * @var string
     */
    public string $senderName = self::DEFAULT_SEND_NAME;

    /**
     * Sender Email
     * @var string
     */
    public string $senderEmail = self::DEFAULT_SEND_EMAIL;

    /**
     * Receiver Name
     * @var string
     */
    public string $receiverName = '';

    /**
     * Receiver Email
     * @var string|array
     */
    public string|array $receiverEmail;

    /**
     * @param StateInterface $inlineTranslation
     * @param Escaper $escaper
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param GeneralHelper $loggerInterface
     */
    public function __construct(
        StateInterface $inlineTranslation,
        Escaper $escaper,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        GeneralHelper $loggerInterface,
    ) {
        $this->_inlineTranslation = $inlineTranslation;
        $this->_escaper           = $escaper;
        $this->_storeManager      = $storeManager;
        $this->_transportBuilder  = $transportBuilder;
        $this->_loggerInterface   = $loggerInterface;
    }

    /**
     * @param $templateId
     * @return $this
     */
    public function setTemplateId($templateId): static
    {
        $this->templateId = $templateId;
        return $this;
    }

    /**
     * @param $senderName
     * @return $this
     */
    public function setSenderName($senderName): static
    {
        $this->senderName = $senderName;
        return $this;
    }

    /**
     * @param $senderEmail
     * @return $this
     */
    public function setSenderEmail($senderEmail): static
    {
        $this->senderEmail = $senderEmail;
        return $this;
    }

    /**
     * @param string $receiverName
     * @return $this
     */
    public function setReceiverName(string $receiverName): static
    {
        $this->receiverName = $receiverName;
        return $this;
    }

    /**
     * @param $receiverEmail
     * @return $this
     */
    public function setReceiverEmail($receiverEmail): static
    {
        $this->receiverEmail = $receiverEmail;
        return $this;
    }

    /**
     * Pass-in 2D array of email template variables.
     * exmaple: $orders = [['increment_id' => '1'],['increment_id' => '2']];
     *
     * @param array $emailTemplateVariables
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function send(array $emailTemplateVariables): void
    {
        try {
            $this->_inlineTranslation->suspend();

            $sender = [
                'name'  => $this->_escaper->escapeHtml($this->senderName),
                'email' => $this->_escaper->escapeHtml($this->senderEmail),
            ];

            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($this->templateId)
                ->setTemplateOptions(
                    [
                        'area'  => Area::AREA_FRONTEND,
                        'store' => Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars($emailTemplateVariables)
                ->setFromByScope($sender)
                ->addTo($this->receiverEmail, $this->receiverName)
                ->getTransport();

            $transport->sendMessage();
            $this->_inlineTranslation->resume();
        } catch (Exception $e) {
            if (!DebugLog::isEnable('Branch8_HotaiCore', self::DEBUG_LOG_OPTION)) {
                return;
            }

            $this->writeLog('Mail Service Exception Message: ' . $e->getMessage());
        }
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function writeLog($message): void
    {
        $this->_loggerInterface->writeLog($message, 'hotai_core_email_service');
    }
}
