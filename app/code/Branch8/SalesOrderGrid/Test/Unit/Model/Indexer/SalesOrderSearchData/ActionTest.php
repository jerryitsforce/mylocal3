<?php
declare(strict_types=1);

namespace Branch8\SalesOrderGrid\Test\Unit\Model\Indexer\SalesOrderSearchData;

use Branch8\SalesOrderGrid\Model\Indexer\SalesOrderSearchData\Action;
use Branch8\SalesOrderGrid\Model\Indexer\SalesOrderSearchData\IndexStructure;
use Branch8\SalesOrderGrid\Model\ResourceModel\Indexer\OrderSearchData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ActionTest extends TestCase
{
    /**
     * @var OrderSearchData|MockObject
     */
    private $orderResourceMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Action
     */
    private $action;

    protected function setUp(): void
    {
        // We use addMethods for 'retrieveCustomerIdsByOrderIds' because it is called in Action.php
        // but does not appear in the provided OrderSearchData class snippet.
        $this->orderResourceMock = $this->getMockBuilder(OrderSearchData::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['retrieveIndexData'])
            ->addMethods(['retrieveCustomerIdsByOrderIds'])
            ->getMock();

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->action = new Action(
            $this->orderResourceMock,
            $this->loggerMock
        );
    }

    public function testConvertOrderIdsToCustomerIds()
    {
        $orderIds = [1, 2, 3];
        $customerIds = [10, 20, 30];

        $this->orderResourceMock->expects($this->once())
            ->method('retrieveCustomerIdsByOrderIds')
            ->with($orderIds)
            ->willReturn($customerIds);

        $result = $this->action->convertOrderIdsToCustomerIds($orderIds);

        $this->assertEquals($customerIds, $result);
    }

    public function testGetIndexInsertIterator()
    {
        $ids = [1];
        $rawData = [
            [
                'order_id' => '1',
                'skus' => 'sku1||sku2',
                'costs' => '10||20',
                'commissions' => '1||2'
            ]
        ];

        $this->orderResourceMock->expects($this->once())
            ->method('retrieveIndexData')
            ->with($ids)
            ->willReturn($rawData);

        $iterator = $this->action->getIndexInsertIterator($ids);

        $this->assertInstanceOf(\Generator::class, $iterator);

        foreach ($iterator as $key => $value) {
            $this->assertEquals(1, $key);
            $this->assertEquals(1, $value[IndexStructure::ORDER_ID]);
            $this->assertEquals('sku1||sku2', $value[IndexStructure::ALL_ITEM_SKUS]);
        }
    }

    public function testGetIndexInsertIteratorLogsException()
    {
        $ids = [1];
        $exceptionMessage = 'Database error';

        $this->orderResourceMock->expects($this->once())
            ->method('retrieveIndexData')
            ->with($ids)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($exceptionMessage);

        $iterator = $this->action->getIndexInsertIterator($ids);

        // Iterate to trigger execution
        foreach ($iterator as $item) {
            // Nothing should be yielded
        }
    }

    public function testFormatIndexDataReturnsEmptyIfOrderIdMissing()
    {
        $data = ['skus' => 'abc'];
        $result = $this->action->formatIndexData($data);
        $this->assertEmpty($result);
    }

    public function testFormatIndexData()
    {
        $data = [
            'order_id' => '123',
            'skus' => 'sku-123',
            'costs' => '50.00',
            'commissions' => '5.00'
        ];

        $result = $this->action->formatIndexData($data);

        $this->assertEquals(123, $result[IndexStructure::ORDER_ID]);
        $this->assertEquals('sku-123', $result[IndexStructure::ALL_ITEM_SKUS]);
        $this->assertEquals('50.00', $result[IndexStructure::ALL_ITEMS_COST]);
        $this->assertEquals('5.00', $result[IndexStructure::ALL_ITEMS_COMMISSION_PERCENT]);
    }
}