<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue;

use AllowDynamicProperties;
use Branch8\MarketPlaceOrderExport\Model\Writer;
use Branch8\MarketPlaceOrderExport\Model\Writer2;
use Branch8\MarketPlaceOrderExportRabbitMQ\Api\Data\ProfileInterface;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Batch;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Config;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Mail;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Profile;
use Magento\Framework\App\Area;
use Psr\Log\LoggerInterface;

ini_set("memory_limit", -1);

#[AllowDynamicProperties] class MailConsumer
{
    private Mail $mail;
    private Config $config;
    private $profileFactory;

    /**
     * @param Config $config
     * @param Mail $mail
     * @param \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory $profileFactory
     */
    public function __construct(
        Config                                                       $config,
        Mail                                                         $mail,
        \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory $profileFactory
    )
    {
        $this->config = $config;
        $this->mail = $mail;
        $this->profileFactory = $profileFactory;
    }
    /**
     * @param $jsonData
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($jsonData)
    {
        /**
         * @var $profile ProfileInterface
         */
        if (!$this->config->enable()) {
            return;
        }
        try {
            $data = @json_decode($jsonData, TRUE);
            if (empty($data) || empty($data['profile_id'])) {
               return;
            }
        } catch (\Exception $e) {
            return;
        }
        $profileId = $data['profile_id'];
        $profile = $this->profileFactory->create()->load($profileId);
        $this->mail->send($profile);
        gc_collect_cycles();
    }
}

