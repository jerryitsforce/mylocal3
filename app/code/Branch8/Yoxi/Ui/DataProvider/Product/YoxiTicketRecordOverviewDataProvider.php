<?php

namespace Branch8\Yoxi\Ui\DataProvider\Product;

use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Branch8\Yoxi\Model\YoxiBatchSetting;

class YoxiTicketRecordOverviewDataProvider extends AbstractDataProvider
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
            $data                = $item->toArray([]);
            $data["sold_count"]  = $soldArray[$item["batch_code"]]["sold_count"] ?? 0;
            $data["used_count"]  = $usedArray[$item["batch_code"]]["used_count"] ?? 0;
            $arrItems['items'][] = $data;
        }

        return $arrItems;
    }

    /**
     * 取得與此產品相關的所有YOXI ticket collection
     *
     * @return \Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\Collection
     */
    private function getTotalCollection(): \Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\Collection
    {
        $this->getCollection()
            ->join(
                [YoxiBatchSetting::TABLE_NAME => YoxiBatchSetting::TABLE_NAME],
                'main_table.batch_setting_id = ' . YoxiBatchSetting::TABLE_NAME . '.setting_id'
            )
            ->addFieldToFilter(
                'main_table.belong_to_product_id',
                $this->request->getParam('current_product_id', 0)
            );

        $this->getCollection()->getSelect()
            ->columns([new \Zend_Db_Expr('COUNT(`record_id`) as total_count')])
            ->group('main_table.batch_setting_id')
            ->order('setting_id DESC');

        return $this->getCollection();
    }

    /**
     * 獲取各batch_code底下已賣出的yoxi_serial_number總數
     * 陣列index: batch_code
     * 陣列value: 已賣出的yoxi_serial_number總數
     *
     * @return array
     */
    private function getSoldCountData(): array
    {
        $soldCollection = $this->collectionFactory->create();
        $soldCollection->join(
            [YoxiBatchSetting::TABLE_NAME => YoxiBatchSetting::TABLE_NAME],
            'main_table.batch_setting_id = ' . YoxiBatchSetting::TABLE_NAME . '.setting_id'
        )
            ->addFieldToFilter(
                'main_table.belong_to_product_id',
                $this->request->getParam('current_product_id', 0)
            )
            ->addFieldToFilter(
                \Branch8\Yoxi\Model\YoxiTicketRecord::SALES_ORDER_ITEM_ID,
                ['neq' => null]
            );

        $soldCollection->getSelect()
            ->columns([new \Zend_Db_Expr('COUNT(`record_id`) as sold_count')])
            ->group('main_table.batch_setting_id');

        $soldArray = [];
        foreach ($soldCollection as $item) {
            $soldArray[$item["batch_code"]] = $item->toArray([]);
        }

        return $soldArray;
    }

    /**
     * 獲取各batch_code底下已使用的yoxi_serial_number總數
     * 陣列index: batch_code
     * 陣列value: 已使用的yoxi_serial_number總數
     *
     * @return array
     */
    private function getUsedCountData(): array
    {
        $usedCollection = $this->collectionFactory->create();
        $usedCollection->join(
            [YoxiBatchSetting::TABLE_NAME => YoxiBatchSetting::TABLE_NAME],
            'main_table.batch_setting_id = ' . YoxiBatchSetting::TABLE_NAME . '.setting_id'
        )
            ->addFieldToFilter(
                'main_table.belong_to_product_id',
                $this->request->getParam('current_product_id', 0)
            )
            ->addFieldToFilter(
                \Branch8\Yoxi\Model\YoxiTicketRecord::USED_COUNT,
                ['gteq' => 1]
            );

        $usedCollection->getSelect()
            ->columns([new \Zend_Db_Expr('COUNT(`record_id`) as used_count')])
            ->group('main_table.batch_setting_id');

        $usedArray = [];
        foreach ($usedCollection as $item) {
            $usedArray[$item["batch_code"]] = $item->toArray([]);
        }

        return $usedArray;
    }
}
