<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Test\Unit\Model;

use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\ChatNotificationService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ChatNotificationServiceTest extends TestCase
{
    /** @var ChatNotificationService */
    private $model;

    /** @var ResourceConnection|MockObject */
    private $resourceMock;

    /** @var Json|MockObject */
    private $jsonMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfigMock;

    /** @var PublisherInterface|MockObject */
    private $publisherMock;

    /** @var AdapterInterface|MockObject */
    private $connectionMock;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);

        $this->resourceMock->method('getConnection')->willReturn($this->connectionMock);

        $this->model = new ChatNotificationService(
            $this->resourceMock,
            $this->jsonMock,
            $this->loggerMock,
            $this->scopeConfigMock,
            $this->publisherMock
        );
    }

    public function testScheduleDisabled(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(false);
        $this->connectionMock->expects($this->never())->method('getTableName');

        $this->model->schedule(1, 1, 'test', 'sender');
    }

    public function testScheduleNewNotification(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->connectionMock->method('getTableName')->willReturn('tableName');

        $selectMock = $this->createMock(Select::class);
        $this->connectionMock->method('select')->willReturn($selectMock);
        $selectMock->method('from')->willReturn($selectMock);
        $selectMock->method('where')->willReturn($selectMock);

        $this->connectionMock->method('fetchRow')->willReturn(false);

        $this->jsonMock->method('serialize')->willReturn('{"msg":"test"}');
        
        $this->connectionMock->expects($this->once())->method('insert');
        $this->publisherMock->expects($this->once())->method('publish');

        $this->model->schedule(1, 1, 'test', 'sender');
    }

    public function testProcessQueueDisabled(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(false);
        $this->connectionMock->expects($this->never())->method('getTableName');

        $result = $this->model->processQueue();
        $this->assertEquals(0, $result);
    }

    public function testCleanUp(): void
    {
        $this->scopeConfigMock->method('getValue')->willReturn(7);
        $this->connectionMock->method('getTableName')->willReturn('tableName');
        
        $this->connectionMock->expects($this->once())
            ->method('delete')
            ->with(
                'tableName',
                $this->callback(function($condition) {
                    return isset($condition['status IN (?)']) && isset($condition['updated_at < ?']);
                })
            )
            ->willReturn(5);

        $result = $this->model->cleanUp();
        $this->assertEquals(5, $result);
    }
}
