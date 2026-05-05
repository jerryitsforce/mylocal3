<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Test\Unit\Model\Queue;

use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Api\ChatNotificationServiceInterface;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\Queue\Consumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConsumerTest extends TestCase
{
    /** @var Consumer */
    private $consumer;

    /** @var ChatNotificationServiceInterface|MockObject */
    private $notificationServiceMock;

    protected function setUp(): void
    {
        $this->notificationServiceMock = $this->getMockBuilder(ChatNotificationServiceInterface::class)
            ->getMock();

        $this->consumer = new Consumer($this->notificationServiceMock);
    }

    public function testProcess(): void
    {
        $this->notificationServiceMock->expects($this->once())
            ->method('processQueue');

        $this->consumer->process('trigger');
    }
}
