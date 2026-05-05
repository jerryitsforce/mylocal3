<?php
namespace Ecpay\General\Helper\Services\Common;

use Ecpay\General\Helper\Foundation\GeneralHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Escaper;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Magento\Framework\Mail\Template\TransportBuilder;

class MailService extends AbstractHelper
{
    /**
     * 寄件人
     */
    public const DEFAULT_SEND_NAME = '綠界科技ECPay';

    /**
     * 寄件人 Email
     */
    public const DEFAULT_SEND_EMAIL = 'sys@ns1.ecpay.com.tw';

    protected $_inlineTranslation;
    protected $_escaper;
    protected $_transportBuilder;
    protected $_loggerInterface;

    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder,
        GeneralHelper $loggerInterface
    ) {
        parent::__construct($context);
        $this->_inlineTranslation = $inlineTranslation;
        $this->_escaper = $escaper;
        $this->_transportBuilder = $transportBuilder;
        $this->_loggerInterface = $loggerInterface;
    }

    /**
     * 發送 Email
     * @param array $mailData
     */
    public function send(array $mailData)
    {
        try {
            $this->_loggerInterface->writeLog('Mail Service mailData:'. print_r($mailData, true));
            $this->_inlineTranslation->suspend();

            $sender = [
                'name'  => $this->_escaper->escapeHtml($mailData['sender_name']),
                'email' => $this->_escaper->escapeHtml($mailData['sender_email']),
            ];

            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($mailData['template_id']) // ecpay_payment_info_template
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => Store::DEFAULT_STORE_ID
                    ]
                )
                ->setTemplateVars($mailData['template_values'])
                ->setFrom($sender)
                ->addTo($mailData['receiver'])
                ->getTransport();

            $transport->sendMessage();
            $this->_inlineTranslation->resume();

        } catch (\Exception $e) {
            $this->_loggerInterface->writeLog('Mail Service Exception Message: ' . $e->getMessage());
        }
    }
}
