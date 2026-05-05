<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Cron;

use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Exception\LockException;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\Config;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\SellerRemind;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Helper\Logger;

class SellerRemindUnreadMessage
{
    /**
     * @var ProcessQueue
     */
    private $process;
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        SellerRemind    $sellerRemind,
        Config          $config,
        Logger $logger
    )
    {
        $this->process = $sellerRemind;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if (!$this->config->isEnable()) {
            return;
        }
        try {
            $this->process->process();
        } catch (LockException $e) {
            $this->logger->info(__('Can\'t get a file lock for queue processing process: %1', $e->getMessage()));
        }
    }
}
