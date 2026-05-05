<?php
/**
 * Email Marketing Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Branch8\Smtp\Framework\Mail\Template;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface; // @codingStandardsIgnoreLine
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Email\Model\TemplateFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\Context;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;
use Laminas\Mime\PartFactory as MimePartFactory;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\MessageFactory as MimeMessageFactory;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * Template Identifier
     *
     * @var string
     */
    protected $templateIdentifier;

    /**
     * Template Model
     *
     * @var string
     */
    protected $templateModel;

    /**
     * Template Variables
     *
     * @var array
     */
    protected $templateVars;

    /**
     * Template Options
     *
     * @var array
     */
    protected $templateOptions;

    /**
     * Mail Transport
     *
     * @var \Magento\Framework\Mail\TransportInterface
     */
    protected $transport;

    /**
     * Template Factory
     *
     * @var FactoryInterface
     */
    protected $templateFactory;

    /**
     * Object Manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Message
     *
     * @var \Magento\Framework\Mail\Message
     */
    protected $message;

    /**
     * Sender resolver
     *
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface
     */
    protected $_senderResolver;

    /**
     * @var \Magento\Framework\Mail\TransportInterfaceFactory
     */
    protected $mailTransportFactory;

    /**
     * @var TemplateFactory
     */
    protected $emailTemplateInterface;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var
     */
    protected $innerSenderName;
    /**
     * @var
     */
    protected $innerSenderEmail;
    /**
     * @var
     */
    protected $innerSendto;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $storeConfig;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /** @var MimePart[] */
    private $parts = [];

    /** @var MimeMessageFactory */
    private $mimeMessageFactory;

    /** @var MimePartFactory */
    private $mimePartFactory;

    const PURINA_EMAIL_IDENTIFIER  = 'purina_customer';
    const REGISTRY_PURINA_EMAIL_IDENTIFIER = 'purina_email_sender';

    /**
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param TemplateFactory $emailTemplateInteface
     * @param \Magento\Framework\Registry $coreRegistry
     * @param MimePartFactory $mimePartFactory
     * @param MimeMessageFactory $mimeMessageFactory
     * @param Context $context
     */
    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager, // @codingStandardsIgnoreLine
        TransportInterfaceFactory $mailTransportFactory,
        TemplateFactory $emailTemplateInteface,
        \Magento\Framework\Registry $coreRegistry,
        MimePartFactory $mimePartFactory,
        MimeMessageFactory $mimeMessageFactory,
        Context $context
    ) {
        parent::__construct
        (
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory
        );
        $this->emailTemplateInterface = $emailTemplateInteface;
        $this->logger = $context->getLogger();
        $this->storeConfig = $context->getScopeConfig();
        $this->_coreRegistry = $coreRegistry;
        $this->mimePartFactory    = $mimePartFactory;
        $this->mimeMessageFactory = $mimeMessageFactory;
    }


    protected function prepareMessage()
    {
        parent::prepareMessage();

        $mimeMessage = $this->getMimeMessage($this->message);

        foreach ($this->parts as $part) {
            $mimeMessage->addPart($part);
        }

        $this->message->setBody($mimeMessage);

        return $this;
    }


    public function addAttachment(
        $body,
        $filename,
        $mimeType = Mime::TYPE_OCTETSTREAM,
        $disposition = Mime::DISPOSITION_ATTACHMENT,
        $encoding = Mime::ENCODING_BASE64
    ) {
        $this->parts[] = $this->createMimePart($body, $mimeType, $disposition, $encoding, $filename);
        return $this;
    }

    /**
     * @return void
     */
    public function resetAttachment()
    {
        $this->parts = [];
    }

    private function createMimePart(
        $content,
        $type = Mime::TYPE_OCTETSTREAM,
        $disposition = Mime::DISPOSITION_ATTACHMENT,
        $encoding = Mime::ENCODING_BASE64,
        $filename = null
    ) {
        /** @var MimePart $mimePart */
        $mimePart = $this->mimePartFactory->create(['content' => $content]);
        $mimePart->setType($type);
        $mimePart->setDisposition($disposition);
        $mimePart->setEncoding($encoding);

        if ($filename) {
            $mimePart->setFileName($filename);
        }

        return $mimePart;
    }

    private function getMimeMessage(MessageInterface $message)
    {
        $body = $message->getBody();
        if ($body instanceof MimeMessage) {
            return $body;
        }

        /** @var MimeMessage $mimeMessage */
        $mimeMessage = $this->mimeMessageFactory->create();

        if ($body) {
            $mimePart = $this->createMimePart((string)$body, Mime::TYPE_TEXT, Mime::DISPOSITION_INLINE);
            $mimeMessage->setParts([$mimePart]);
        }

        return $mimeMessage;
    }

}
