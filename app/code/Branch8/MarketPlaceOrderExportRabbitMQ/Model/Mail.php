<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Area;
use Psr\Log\LoggerInterface;

class Mail implements MailInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    private LoggerInterface $logger;
    private $localizedFileName;
    private Emulation $emulation;

    /**
     * @param Config $config
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param LoggerInterface $logger
     * @param \Magento\ImportExport\Model\LocalizedFileName $localizedFileName
     * @param Emulation $emulation
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        Config                                        $config,
        TransportBuilder                              $transportBuilder,
        StateInterface                                $inlineTranslation,
        LoggerInterface                               $logger,
        \Magento\ImportExport\Model\LocalizedFileName $localizedFileName,
        Emulation                                     $emulation,
        StoreManagerInterface                         $storeManager = null
    )
    {
        $this->localizedFileName = $localizedFileName;
        $this->logger = $logger;
        $this->config = $config;
        $this->transportBuilder = $transportBuilder;
        $this->emulation = $emulation;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * @param Profile $profile
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function send(Profile $profile)
    {
        /** @see \Magento\Contact\Controller\Index\Post::validatedParams() */
        if (!$this->config->enable()) {
            return;
        }
        $this->inlineTranslation->suspend();
        try {
            $paths = explode('/', (string)$profile->getFilePath());
            $fileName = $paths[count($paths) - 1];
            $object = new DataObject([
                'name' => $profile->getReceiverName(),
                'profile_id' => $profile->getProfileId(),
                'file_name' => $fileName,
                'file_display_name' => $fileName ? $this->localizedFileName->getFileDisplayName($fileName) : '',
                'export_admin_url' => $this->getExportAdminUrl()
            ]);
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'systemlog')){
                $this->logger->info("Sending email to" . $this->getExportAdminUrl());
            }
            $variables = [
                'data' => $object,
                'store' => $profile->getProfileType() == 'admin' ? $this->storeManager->getStore(0) : $this->storeManager->getStore(),
            ];

            $template = $profile->getProfileType() === Profile::TYPE_SELLER ? $this->config->sellerEmailTemplate()
                : $this->config->adminEmailTemplate();
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($template)
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $profile->getProfileType() == 'admin' ? 0 : $this->storeManager->getStore()->getId()
                    ]
                )
                ->setTemplateVars($variables)
                ->setFrom($this->config->emailSender())
                ->addTo($profile->getReceiverEmail(), $profile->getReceiverName())
                ->getTransport();
            $transport->sendMessage();
           // $profile->setEmailSent(true)->getResource()->save($profile);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'exceptionlog')){
                $this->logger->critical($e->getMessage());
            }
        } finally {
            $this->inlineTranslation->resume();
        }
    }

    /**
     * @return mixed
     */
    private function getExportAdminUrl()
    {
        $this->emulation->startEnvironmentEmulation(0, AREA::AREA_ADMINHTML);
        /**
         * @var $url \Magento\Backend\Model\UrlInterface
         */
        $url = ObjectManager::getInstance()->get(\Magento\Backend\Model\UrlInterface::class);
        $secrectKey = $url->getSecretKey();
        $this->emulation->stopEnvironmentEmulation();
        $urlBackend = $url->getUrl('admin/export/index');
        return $urlBackend;
    }
}
