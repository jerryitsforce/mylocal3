<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Test\Unit\Plugin;

use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Api\ChatNotificationServiceInterface;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Plugin\SaveMessagePlugin;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\SaveMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveMessagePluginTest extends TestCase
{
    /** @var SaveMessagePlugin */
    private $plugin;

    /** @var ChatNotificationServiceInterface|MockObject */
    private $notificationServiceMock;

    /** @var ChatProfileRepository|MockObject */
    private $chatProfileRepositoryMock;

    protected function setUp(): void
    {
        $this->notificationServiceMock = $this->getMockBuilder(ChatNotificationServiceInterface::class)
            ->getMock();
        $this->chatProfileRepositoryMock = $this->getMockBuilder(ChatProfileRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new SaveMessagePlugin(
            $this->notificationServiceMock,
            $this->chatProfileRepositoryMock
        );
    }

    public function testAfterSaveChatMessageWithSellerAndCustomer(): void
    {
        $subjectMock = $this->getMockBuilder(SaveMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = true;
        
        $senderProfileMock = $this->getMockBuilder(ChatProfile::class)
            ->disableOriginalConstructor()
            ->getMock();
        $receiverProfileMock = $this->getMockBuilder(ChatProfile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->chatProfileRepositoryMock->method('getByUniqueId')
            ->willReturnMap([
                ['sender_id', $senderProfileMock],
                ['receiver_id', $receiverProfileMock]
            ]);

        $senderProfileMock->method('getRegisteredAs')->willReturn(ChatRole::SELLER);
        $receiverProfileMock->method('getRegisteredAs')->willReturn(ChatRole::CUSTOMER);
        $receiverProfileMock->method('getObjectId')->willReturn(123);
        $senderProfileMock->method('getNickName')->willReturn('Seller');

        $this->notificationServiceMock->expects($this->once())
            ->method('schedule')
            ->with(123, 1, 'Hello', 'Seller');

        $this->plugin->afterSaveChatMessage(
            $subjectMock,
            $result,
            1,
            'sender_id',
            'receiver_id',
            'Hello',
            '2026-04-07 15:00:00',
            'text'
        );
    }
}
