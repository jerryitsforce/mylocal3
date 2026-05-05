<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model;

use Magento\Framework\Flag\FlagResource;
use Magento\Framework\FlagFactory;
use Magento\Framework\FlagManager;

class LockModel
{
    public const SCHEDULE_FLAG = 'chat_unread_generate_schedule_processing';

    public const PROCESS_FLAG = 'chat_unread_process_schedule_processing';
    /**
     * @var FlagManager
     */
    private $flagManager;
    /**
     * The factory of flags.
     *
     * @var FlagFactory
     * @see Flag
     */
    private $flagFactory;

    /**
     * The flag resource.
     *
     * @var FlagResource
     */
    private $flagResource;

    /**
     * @param FlagManager $flagManager
     * @param FlagFactory $flagFactory
     * @param FlagResource $flagResource
     */
    public function __construct(
        FlagManager  $flagManager,
        FlagFactory  $flagFactory,
        FlagResource $flagResource
    )
    {
        $this->flagManager = $flagManager;
        $this->flagFactory = $flagFactory;
        $this->flagResource = $flagResource;
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
        $data = (string)$this->flagManager->getFlagData($code);
        return $data ?? '';
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

    /**
     * @param $flag
     * @return bool
     * @throws \DateMalformedStringException
     */
    public function isLockExpired($flag)
    {
        $flagObject = $this->getFlagObject($flag);
        if (!$flagObject->getLastUpdate()) {
            return true;
        }
        $lastUpdate = $flagObject->getLastUpdate();
        $lastUpdate = new \DateTime($lastUpdate, new \DateTimeZone('UTC'));
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $diff = $now->diff($lastUpdate);
        $hours = ($diff->days * 24) + $diff->h + ($diff->i / 60);
        return $hours > 24;
    }

    /**
     * @param $code
     * @return mixed
     */
    private function getFlagObject($code)
    {
        $flag = $this->flagFactory->create(['data' => ['flag_code' => $code]]);
        $this->flagResource->load(
            $flag,
            $code,
            'flag_code'
        );
        return $flag;
    }
}
