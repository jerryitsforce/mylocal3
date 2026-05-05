<?php

namespace Branch8\GeneralNotifyTicket\Ui\DataProvider\Product;

use Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketRecord\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecord as Record;

class GeneralNotifyTicketRecordOverviewDataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     * @since 100.1.0
     */
    protected $collectionFactory;

    /**
     * @var RequestInterface
     * @since 100.1.0
     */
    protected $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collectionFactory = $collectionFactory;
        $this->collection        = $this->collectionFactory->create();
        $this->request           = $request;
    }

    /**
     * Get filtered data
     */
    public function getData()
    {
        $totalCollection = $this->getTotalCollection();

        $arrItems = [
            'totalRecords' => $totalCollection->getSize(),
            'items'        => [],
        ];

        $soldArray = $this->getSoldCountData();
        $usedArray = $this->getUsedCountData();

        foreach ($totalCollection as $item) {
            $data                  = $item->toArray([]);
            $data["use_end_time"] = $data["use_end_time"] ?? "--";
            $data["due_days"]      = $data["due_days"] ?? "--";
            $data["sold_count"]    = $soldArray[$item["batch_code"]]["sold_count"] ?? 0;
            $data["used_count"]    = $usedArray[$item["batch_code"]]["used_count"] ?? 0;
            $arrItems['items'][]   = $data;
        }

        return $arrItems;
    }

    /**
     * 取得與此產品相關的所有 ticket collection
     *
     * @return \Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketRecord\Collection
     */
    private function getTotalCollection(): \Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketRecord\Collection
    {
        $this->getCollection()
            ->join(
                ['general_notify_ticket_batch_setting' => 'general_notify_ticket_batch_setting'],
                'main_table.batch_setting_id = general_notify_ticket_batch_setting.setting_id'
            )
            ->addFieldToFilter(
                'main_table.belong_to_product_id',
                $this->request->getParam('current_product_id', 0)
            );

        $this->getCollection()->getSelect()
            ->columns([new \Zend_Db_Expr('COUNT(`record_id`) as total_count')])
            ->group('batch_setting_id')
            ->order('setting_id DESC');

        return $this->getCollection();
    }

    /**
     * 獲取各batch_code底下的已賣出總數
     * 陣列index: batch_code
     * 陣列value: 已賣出的serial_number總數
     *
     * @return array
     */
    private function getSoldCountData(): array
    {
        $soldCollection = $this->collectionFactory->create();
        $soldCollection->join(
            ['general_notify_ticket_batch_setting' => 'general_notify_ticket_batch_setting'],
            'main_table.batch_setting_id = general_notify_ticket_batch_setting.setting_id'
        )
            ->addFieldToFilter(
                'main_table.belong_to_product_id',
                $this->request->getParam('current_product_id', 0)
            )
            ->addFieldToFilter(
                Record::SALES_ORDER_ITEM_ID,
                ['neq' => null]
            );

        $soldCollection->getSelect()
            ->columns([new \Zend_Db_Expr('COUNT(`record_id`) as sold_count')])
            ->group('batch_setting_id');

        $soldArray = [];
        foreach ($soldCollection as $item) {
            $soldArray[$item["batch_code"]] = $item->toArray([]);
        }

        return $soldArray;
    }

    /**
     * 獲取各batch_code底下已使用的serial_number總數
     * 陣列index: batch_code
     * 陣列value: 已使用的serial_number總數
     *
     * @return array
     */
    private function getUsedCountData(): array
    {
        $usedCollection = $this->collectionFactory->create();
        $usedCollection->join(
            ['general_notify_ticket_batch_setting' => 'general_notify_ticket_batch_setting'],
            'main_table.batch_setting_id = general_notify_ticket_batch_setting.setting_id'
        )
            ->addFieldToFilter(
                'main_table.belong_to_product_id',
                $this->request->getParam('current_product_id', 0)
            )
            ->addFieldToFilter(
                Record::USED_COUNT,
                ['gteq' => 1]
            );

        $usedCollection->getSelect()
            ->columns([new \Zend_Db_Expr('COUNT(`record_id`) as used_count')])
            ->group('batch_setting_id');

        $usedArray = [];
        foreach ($usedCollection as $item) {
            $usedArray[$item["batch_code"]] = $item->toArray([]);
        }

        return $usedArray;
    }
}
