<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Test\Unit\Cron;

use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Api\ChatNotificationServiceInterface;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Cron\CleanupQueue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CleanupQueueTest extends TestCase
{
    /** @var CleanupQueue */
    private $cron;

    /** @var ChatNotificationServiceInterface|MockObject */
    private $notificationServiceMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->notificationServiceMock = $this->getMockBuilder(ChatNotificationServiceInterface::class)
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->cron = new CleanupQueue(
            $this->notificationServiceMock,
            $this->loggerMock
        );
    }

    public function testExecute(): void
    {
        $this->notificationServiceMock->expects($this->once())
            ->method('cleanUp')
            ->willReturn(10);

        $this->loggerMock->expects($this->once())
            ->method('info');

        $this->cron->execute();
    }
}
