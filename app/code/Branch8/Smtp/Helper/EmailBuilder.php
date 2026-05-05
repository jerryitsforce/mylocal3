<?php

namespace Branch8\Smtp\Helper;

use Branch8\MarketPlaceSeller\Logger\Logger;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MimeInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\XlsxFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;

class EmailBuilder
{

    protected Logger $logger;

    protected ScopeConfigInterface $scopeConfig;

    protected StateInterface $inlineTranslation;

    protected TransportBuilder $transportBuilder;

    protected StoreManagerInterface $storeManager;

    protected Spreadsheet $spreadsheet;

    protected XlsxFactory $xlsx;

    protected DirectoryList $directoryList;

    protected TimezoneInterface $timezone;

    public function __construct(
        Logger                      $logger,
        StateInterface              $inlineTranslation,
        TransportBuilder            $transportBuilder,
        ScopeConfigInterface        $scope,
        StoreManagerInterface       $storeManager,
        Spreadsheet                 $spreadsheet,
        XlsxFactory                 $xlsx,
        DirectoryList               $directoryList,
        TimezoneInterface           $timezone
    )
    {
        $this->logger = $logger;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scope;
        $this->storeManager = $storeManager;
        $this->spreadsheet = $spreadsheet;
        $this->xlsx = $xlsx;
        $this->directoryList = $directoryList;
        $this->timezone = $timezone;
    }


    /**
     * @param $templateId
     * @param $emailTemplateVariables
     * @param $sender
     * @param array $receiverInfo
     * @param $attachment
     * @param $copyTo
     * @param $copyMethod
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendEmail($templateId, $emailTemplateVariables, $sender, array $receiverInfo, $attachment = null, $copyTo = null, $copyMethod = null)
    {
        $emailAddress = $receiverInfo['email'] ?? implode(', ', $receiverInfo);
        $this->logger->info("Start: Sends email to " . $emailAddress);
        $template = $this->getTemplateId($templateId);
        $this->inlineTranslation->suspend();
        if ($copyTo && $copyMethod) {
            $copyTo = $this->getConfigValue($copyTo);
            $copyMethod = $this->getConfigValue($copyMethod);
        }

        try {
            $transportBuilder = $this->transportBuilder
                ->setTemplateIdentifier($template)
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->getStore()->getId() ?: \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )->setTemplateVars($emailTemplateVariables)
                ->setFrom($sender);
            if (empty($receiverInfo['email'])) {
                foreach ($receiverInfo as $email) {
                    $transportBuilder->addTo($email);
                }
            } else {
                $transportBuilder->addTo($receiverInfo['email'], $receiverInfo['name']);
            }
            if ($copyTo && $copyMethod) {
                if ($copyMethod == 'bcc') {
                    $transportBuilder->addBcc($copyTo, '');
                } else {
                    $transportBuilder->addCc($copyTo, '');
                }
            }

            if (is_array($attachment)) {
                $this->processAddAttachment($transportBuilder, $attachment);
            }

            $transport = $transportBuilder->getTransport();
            /**
             * @var $message MessageInterface
             */
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Could not send email to ". $emailAddress . " ERROR: ".$e->getMessage());
            $this->logger->critical($e->getMessage());
        }
        $this->logger->info("End: Sends email to ".$emailAddress);
        return false;
    }


    /**
     * @param TransportBuilder $transportBuilder
     * @param $attachment
     * @return void
     */
    protected function processAddAttachment(TransportBuilder $transportBuilder, $attachment)
    {
        try {
            if (is_file($attachment['path'])) {
                $transportBuilder->resetAttachment();
                $transportBuilder->addAttachment(
                    file_get_contents($attachment['path']),
                    $attachment['file_name'],
                    MimeInterface::TYPE_OCTET_STREAM
                );
            };
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $this->logger->info($exception->getTraceAsString());
        }
    }

    public function addAttachment(
        $body,
        $filename,
        $mimeType = Mime::TYPE_OCTETSTREAM,
        $disposition = Mime::DISPOSITION_ATTACHMENT,
        $encoding = Mime::ENCODING_BASE64,

    ) {
        $this->parts[] = $this->createMimePart($body, $mimeType, $disposition, $encoding, $filename);
        return $this;
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
        // decode characters (&) in case generate reset password link
        $content = $body->getParts()[0];
        $mimePart = $this->createMimePart(html_entity_decode((string)$content->getContent()), Mime::TYPE_HTML, null, Mime::ENCODING_8BIT);
        $body->setParts([$mimePart]);

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

    /**
     * @param $path
     * @param $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId = null): mixed
    {
        if (!$storeId) {
            $storeId = $this->getStore()->getStoreId();
        }
        return $this->scopeConfig->getValue(
            $path,
            'stores',
            $storeId
        );
    }


    /**
     * @param $xmlPath
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTemplateId($xmlPath)
    {
        return $this->getConfigValue($xmlPath, $this->getStore()->getStoreId());
    }


    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

}
