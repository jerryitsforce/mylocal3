<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipant;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipantFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\GeneralConfig;
use Magento\Framework\App\ResourceConnection;

class MessageReminderManagement
{
    /**
     * @var GeneralConfig
     */
    private GeneralConfig $generalConfig;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    private ChatParticipantFactory $chatParticipantFactory;

    /**
     * @param GeneralConfig $generalConfig
     * @param ResourceConnection $resourceConnection
     * @param ChatParticipantFactory $chatParticipantFactory
     */
    public function __construct(
        GeneralConfig          $generalConfig,
        ResourceConnection     $resourceConnection,
        ChatParticipantFactory $chatParticipantFactory,
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->generalConfig = $generalConfig;
        $this->chatParticipantFactory = $chatParticipantFactory;
    }

    /**
     * @return int|null
     */
    private function getFrequency()
    {
        return $this->generalConfig->getReminderFrequency();
    }

    /**
     * @param $dateString
     * @return string
     * @throws \Exception
     */
    public function calculateExpireReminderDate($dateString)
    {
        $reminderDayOffset = $this->generalConfig->getReminderDayOffset();
        $UTC = new \DateTimeZone("UTC");
        $date = new \DateTime($dateString, $UTC);
        $date->modify("+$reminderDayOffset days");
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param int $conversationId
     * @param int $profileId
     * @return void
     * @throws \Exception
     */
    public function calculateAndSaveExpireRemindDateForChatProfile(
        int $conversationId,
        int $profileId
    )
    {
        $chatParticipant = $this->chatParticipantFactory->create()->loadByConversationAndProfileId(
            $conversationId,
            $profileId
        );
        if ($chatParticipant->getId()) {
            $this->calCulateAndSaveExpireRemindDate($chatParticipant);
        }
    }

    /**
     * @param ChatParticipant $chatParticipant
     * @return \DateTime|false|string
     * @throws \Exception
     */
    public function calCulateAndSaveExpireRemindDate(
        ChatParticipant $chatParticipant
    )
    {
        $UTC = new \DateTimeZone("UTC");
        $reminderDayOffset = $this->generalConfig->getReminderDayOffset();
        $dbExpiredReminderDate = $chatParticipant->getExpireReminderDate();
        $now = new \DateTime(
            "now", new \DateTimeZone("UTC")
        );
        $resetSchedule = false;
        if ($dbExpiredReminderDate) {
            $dbExpiredReminderDate = new \DateTime(
                $dbExpiredReminderDate, $UTC
            );
        } else {
            $dbExpiredReminderDate = $now;
        }
        $expireReminderDate = $dbExpiredReminderDate->modify("+$reminderDayOffset days");
        $this->saveExpireReminderDate(
            $chatParticipant,
            [
                'expire_reminder_date' => $expireReminderDate->format('Y-m-d H:i:s'),
                'is_schedule' => 1
            ]
        );
        return $expireReminderDate;
    }

    /**
     * @param ChatParticipant $chatParticipant
     * @param string $expireReminderDate
     * @return void
     */
    private function saveExpireReminderDate(
        ChatParticipant $chatParticipant,
        array           $data
    )
    {
        $connection = $this->resourceConnection->getConnection();
        $where = ['entity_id = ? ' => $chatParticipant->getId()];
        $connection->update(
            $connection->getTableName('marketplace_chat_participant'),
            $data,
            $where
        );
    }
}
