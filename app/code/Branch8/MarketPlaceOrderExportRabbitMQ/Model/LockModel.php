<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model;

use Magento\Framework\FlagManager;

class LockModel
{
    public const PROCESS_FLAG = 'order_export_notify_user_complete_processing';
    /**
     * @var FlagManager
     */
    private $flagManager;

    public function __construct(
        FlagManager $flagManager
    )
    {
        $this->flagManager = $flagManager;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function setIsQueueLocked($flag, bool $value): void
    {
        $this->saveFlag($flag, (string)$value);
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getFlag(string $code): string
    {
        return (string)$this->flagManager->getFlagData($code);
    }

    /**
     * @param string $code
     * @param $value
     * @return void
     *
     */
    protected function saveFlag(string $code, $value): void
    {
        $this->flagManager->saveFlag($code, (string)$value);
    }

    /**
     * @return bool
     */
    public function isQueueLocked($flag): bool
    {
        return (bool)$this->getFlag($flag);
    }

}
