<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderChildLink\Model\Import;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;
use Branch8\MarketPlaceParentOrder\Model\Services\CopyAddressesFromSalesOrderToParentOrder;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Branch8\MarketPlaceParentOrderChildLink\Helper\Logger as LoggerInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;

class LinkParentOrderSuborder extends AbstractEntity
{
    const PARENT_ORDER_NUMBER = 'parent_order_number';

    const SUB_ORDER_NUMBER = 'sub_order_number';
    const ENTITY_CODE = 'parent_order_sub_order_link';

    /**
     * If we should check column names
     */
    protected $needColumnCheck = true;

    /**
     * Need to log in import history
     */
    protected $logInHistory = true;
    /**
     * Valid column names
     */
    protected $validColumnNames = [
        'parent_order_number',
        'sub_order_number'
    ];

    /**
     * @var AdapterInterface
     */
    protected $connection;


    private ResourceConnection $resource;
    /**
     * @var OrderRepository
     */
    private OrderRepository $orderRepository;
    /**
     * @var OrderFactory
     */
    private OrderFactory $orderFactory;

    private $orders = [];
    /**
     * @var array
     */
    private $parentOrders = [];

    private $parentOrderBySubOrder = [];
    /**
     * @var ParentOrderRepository
     */
    private ParentOrderRepository $parentOrderRepository;
    private ParentOrderFactory $parentOrderFactory;
    private ParentOrderManagementInterface $parentOrderManagement;
    private \Branch8\MarketPlaceParentOrderChildLink\Model\AssignDataForParentOrder $assignDataForParentOrder;
    private CopyAddressesFromSalesOrderToParentOrder $copyAddressesFromSalesOrderToParentOrder;
    private \Branch8\HotaiOrderNumber\Model\Actions\HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders;
    private \Webkul\Mpsplitorder\Model\MpsplitorderFactory $mpsplitorderFactory;
    private LoggerInterface $logger;

    private $alreadyCheckRecords = [];

    private $detailFactory;


    private $insertLinkedData = [];
    private $insertDetailData = [];

    private $updateDetailData = [];
    private $insertAddressData = [];

    private $updateAddressData = [];
    private $detailColumns = [
        'entity_id',
        'parent_id',
        'state',
        'status',
        'hold_before_state',
        'hold_before_status',
        'shipping_description',
        'increment_id',
        'customer_id',
        'shipping_method',
        'customer_is_guest',
        'customer_email',
        'customer_name',
        'customer_group_id',
        'payment_method',
        'order_currency_code',
        'parent_relation_new_id',
        'parent_relation_new_real_id',
        'is_virtual',
        'customer_note',
        'store_id',
        'remote_ip',
        'store_name',
        'shipping_address_id',
        'billing_address_id',
        'created_at',
        'updated_at',
        'ecpay_payment_merchant_trade_no',
        'ecpay_logistic_cvs_store_id',
        'ecpay_logistic_cvs_store_name',
        'ecpay_logistic_cvs_store_address',
        'ecpay_logistic_cvs_store_telephone',
        'ecpay_invoice_carruer_type',
        'ecpay_invoice_customer_identifier',
        'ecpay_invoice_customer_company',
        'ecpay_invoice_love_code',
        'ecpay_invoice_carruer_num',
        'hotai_parent_order_number',
        'order_note',
        'referrer_code',
        'is_auto_cancelled',
        'rma_status'
    ];

    private $addressColunns = [
        'entity_id',
        'parent_order_id',
        'customer_address_id',
        'region_id',
        'customer_id',
        'fax',
        'region',
        'postcode',
        'lastname',
        'street',
        'city',
        'email',
        'telephone',
        'country_id',
        'firstname',
        'address_type',
        'prefix',
        'middlename',
        'suffix',
        'company',
        'vat_id',
        'vat_is_valid',
        'vat_request_id',
        'vat_request_date',
        'vat_request_success'
    ];

    /**
     * @param JsonHelper $jsonHelper
     * @param ImportHelper $importExportData
     * @param Data $importData
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param OrderRepository $orderRepository
     * @param ParentOrderRepository $parentOrderRepository
     * @param OrderFactory $orderFactory
     * @param ParentOrderFactory $parentOrderFactory
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param \Branch8\MarketPlaceParentOrderChildLink\Model\AssignDataForParentOrder $assignDataForParentOrder
     * @param CopyAddressesFromSalesOrderToParentOrder $copyAddressesFromSalesOrderToParentOrder
     * @param \Branch8\HotaiOrderNumber\Model\Actions\HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders
     * @param \Webkul\Mpsplitorder\Model\MpsplitorderFactory $mpsplitorderFactory
     * @param ParentOrderDetailFactory $detailFactory
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param LoggerInterface $logger
     */
    public function __construct(
        JsonHelper                                                                $jsonHelper,
        ImportHelper                                                              $importExportData,
        Data                                                                      $importData,
        ResourceConnection                                                        $resource,
        Helper                                                                    $resourceHelper,
        OrderRepository                                                           $orderRepository,
        ParentOrderRepository                                                     $parentOrderRepository,
        OrderFactory                                                              $orderFactory,
        ParentOrderFactory                                                        $parentOrderFactory,
        ParentOrderManagementInterface                                            $parentOrderManagement,
        \Branch8\MarketPlaceParentOrderChildLink\Model\AssignDataForParentOrder   $assignDataForParentOrder,
        CopyAddressesFromSalesOrderToParentOrder                                  $copyAddressesFromSalesOrderToParentOrder,
        \Branch8\HotaiOrderNumber\Model\Actions\HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders,
        \Webkul\Mpsplitorder\Model\MpsplitorderFactory                            $mpsplitorderFactory,
        ParentOrderDetailFactory                                                  $detailFactory,
        ProcessingErrorAggregatorInterface                                        $errorAggregator,
        LoggerInterface                                                           $logger
    )
    {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->orderRepository = $orderRepository;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->orderFactory = $orderFactory;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator = $errorAggregator;
        $this->parentOrderFactory = $parentOrderFactory;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->assignDataForParentOrder = $assignDataForParentOrder;
        $this->copyAddressesFromSalesOrderToParentOrder = $copyAddressesFromSalesOrderToParentOrder;
        $this->hotaiGenerateIncrementIdForOrders = $hotaiGenerateIncrementIdForOrders;
        $this->mpsplitorderFactory = $mpsplitorderFactory;
        $this->logger = $logger;
        $this->detailFactory = $detailFactory;
        $this->initMessageTemplates();
    }


    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return self::ENTITY_CODE;
    }

    /**
     * Get available columns
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }

    /**
     * @return void
     */
    private function initMessageTemplates(): void
    {
        $this->addMessageTemplate(
            'SubOrderIsRequired',
            __('Sub-Order Increment ID cannot be empty.')
        );
        $this->addMessageTemplate(
            'SubOrderIsNotExisted',
            __('Sub-Order Increment ID cannot be empty.')
        );
        $this->addMessageTemplate(
            'ParentOrderNotExisted',
            __('Parent-Order is not existed.')
        );
        $this->addMessageTemplate(
            'ParentOrderNotExisted',
            __('Parent-Order is not existed.')
        );
        $this->addMessageTemplate(
            'SubOrderAlreadyLinkWithAnotherParentOrder',
            __('Parent-Order column is empty but sub-order already linked with another parent-order.')
        );
        $this->addMessageTemplate(
            'DuplicateRecord',
            __('Duplicate Record')
        );
    }

    /**
     * Row validation
     *
     * @param array $rowData
     * @param int $rowNum
     *
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum): bool
    {
        //$rowData = array_map('trim', $rowData);
        // Deterministic hash for duplicate row detection (non-security usage)
        $hash = hash('sha256', implode('', $rowData));
        $suborderNumber = isset($rowData[self::SUB_ORDER_NUMBER]) ? trim($rowData[self::SUB_ORDER_NUMBER]) : '';
        $parentOrderNumber = isset($rowData[self::PARENT_ORDER_NUMBER]) ? trim($rowData[self::PARENT_ORDER_NUMBER]) : '';
        if (empty($suborderNumber)) {
            $this->addRowError('SubOrderIsRequired', $rowNum);
        }
        $order = $this->getOrder($rowData[self::SUB_ORDER_NUMBER]);
        if ($suborderNumber && !$order) {
            $this->addRowError('SubOrderIsNotExisted', $rowNum);
        }
        if ($parentOrderNumber && !$parentOrder = $this->getParentOrder($parentOrderNumber)) {
            $this->addRowError('ParentOrderNotExisted', $rowNum);
        }
        /* if ($order && empty($parentOrderNumber) && $this->alreadyLinkWithAnotherParentOrder($order->getId())) {
             $this->addRowError('SubOrderAlreadyLinkWithAnotherParentOrder', $rowNum);
         }*/

        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        if (!in_array($hash, $this->alreadyCheckRecords)) {
            $this->alreadyCheckRecords[] = $hash;
        } else {
            $this->addRowError('DuplicateRecord', $rowNum);
        }
        $this->_validatedRows[$rowNum] = true;
        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * @param $incrementId
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|\Branch8\MarketPlaceParentOrder\Model\ParentOrder|mixed
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    private function getParentOrder($incrementId)
    {
        if (isset($this->parentOrders[$incrementId])) {
            return $this->parentOrders[$incrementId];
        }
        try {
            $parentOrder = $this->parentOrderRepository->getByIncrementId((string)$incrementId);
            $this->parentOrders[$incrementId] = $parentOrder;
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $this->parentOrders[$incrementId] = null;
        }
        return $this->parentOrders[$incrementId];
    }

    /**
     * @param $incrementId
     * @return \Magento\Sales\Model\Order |false
     */
    private function getOrder($incrementId)
    {
        try {
            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            if ($order->getId()) {
                $this->orders[$incrementId] = $order;
                return $this->orders[$incrementId];
            }
            return false;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Import data
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function _importData(): bool
    {
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_APPEND:
                $this->saveAndReplaceEntity();
                break;
        }
        return true;
    }

    /**
     * Save and replace entities
     *
     * @return void
     */
    private function saveAndReplaceEntity()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $this->insertLinkedData = $this->insertDetailData = $this->updateDetailData = [];
            $this->insertAddressData = $this->updateAddressData = [];
            $ids = [];
            foreach ($bunch as $rowNum => $row) {
                if (!$this->validateRow($row, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                $rowId = $row[static::SUB_ORDER_NUMBER];
                $columnValues = [];
                foreach ($this->getAvailableColumns() as $columnKey) {
                    $columnValues[$columnKey] = $row[$columnKey];
                }
                try {
                    $parentOrder = $this->buildInsertData($columnValues);
                    $ids[] = $parentOrder->getId();
                } catch (\Exception $exception) {
                    $this->logger->critical($exception->getMessage());
                }
            }
            if ($this->insertLinkedData) {
                $this->connection->insertOnDuplicate(
                    'sales_parent_order_children',
                    $this->insertLinkedData,
                    ['parent_id', 'children_id']
                );
            }
            if ($this->insertDetailData) {
                /*                print_r(  array_keys($this->insertDetailData[0]));die;*/
                $this->connection->insertOnDuplicate(
                    'sales_parent_order_detail',
                    $this->insertDetailData,
                    array_keys($this->insertDetailData[0])
                );
            }
            if ($this->insertAddressData) {
                $this->connection->insertOnDuplicate(
                    'sales_parent_order_address',
                    $this->insertAddressData,
                    array_keys($this->insertAddressData[0])
                );
            }

            if ($ids) {
                $this->updateAddressIdForDetailTable('shipping_address_id', 'shipping');
                $this->updateAddressIdForDetailTable('billing_address_id', 'billing');
            }
        }

    }

    /**
     * @param $field
     * @param $type
     * @return void
     */
    private function updateAddressIdForDetailTable($field, $type)
    {
        $query = 'UPDATE `sales_parent_order_detail` ';
        $query .= 'JOIN `sales_parent_order_address` ON sales_parent_order_detail.parent_id = sales_parent_order_address.parent_order_id ';
        $query .= 'SET `'.$field.'` = `sales_parent_order_address`.entity_id ';
        $query .= 'WHERE `sales_parent_order_address`.`address_type` = "' . $type . '" ';
        $query .= 'AND  `sales_parent_order_detail`.`' . $field . '` IS NULL ';
        $this->connection->query($query);
    }

    /**
     * @param ParentOrder $parentOrder
     * @param Order $subOrder
     * @return void
     */
    private function buildParentOrderAddress(ParentOrder $parentOrder, Order $subOrder)
    {
        $id = $parentOrder->getId();
        $addresses = $parentOrder->getAddresses();
        if (!$addresses || count($addresses) === 0) {
            $addresses = $this->copyAddressesFromSalesOrderToParentOrder->copy($subOrder);
        }
        $shippingAddress = $billingAddress = null;
        if ($addresses) {
            foreach ($addresses as $address) {
                $address->setParentOrder($parentOrder);
                $address->setParentOrderId((int)$id);
                if ($address->getAddressType() === ParentOrderAddress::TYPE_SHIPPING) {
                    $shippingAddress = $address;
                }
                if ($address->getAddressType() === ParentOrderAddress::TYPE_BILLING) {
                    $billingAddress = $address;
                }
            }
        }
        if ($shippingAddress) {
            $shippingAddressData = $shippingAddress->getData();
            $insertData = [];
            foreach ($this->addressColunns as $column) {
                if (empty($shippingAddressData[$column])) {
                    $insertData[$column] = '';
                } else {
                    $insertData[$column] = $shippingAddressData[$column];
                }
            }
            $insertData['parent_order_id'] = $parentOrder->getId();
            $this->insertAddressData[] = $insertData;
        }
        if ($billingAddress) {
            $billingAddressData = $billingAddress->getData();
            $insertData = [];
            foreach ($this->addressColunns as $column) {
                if (empty($billingAddressData[$column])) {
                    $insertData[$column] = '';
                } else {
                    $insertData[$column] = $billingAddressData[$column];
                }
            }
            $insertData['parent_order_id'] = $parentOrder->getId();
            $this->insertAddressData[] = $insertData;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $subOrder
     * @return void
     */
    private function removeLink(\Magento\Sales\Model\Order $subOrder)
    {
        $connection = $this->resource->getConnection();
        $where = ['children_id IN (?)' => [$subOrder->getId()]];
        $table = $this->resource->getTableName('sales_parent_order_children');
        $connection->delete($table, $where);
    }

    /**
     * @param ParentOrder $parentOrder
     * @param Order $subOrder
     * @return \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail
     * @throws Exception
     */
    private function updateDetailParentOrder(ParentOrder $parentOrder, Order $subOrder)
    {
        $id = $parentOrder->getId();
        $detailObject = $parentOrder->getDetail() ? $parentOrder->getDetail() : $this->detailFactory->create();
        $detail = $this->assignDataForParentOrder->copy($subOrder, $detailObject);
        $detail->setParentId((int)$parentOrder->getId());
        $detail->setCreatedAt($subOrder->getCreatedAt());
        $parentOrder->setDetail($detail);
        $parentOrder->getExtensionAttributes()->setDetail($detail);
        $detail->setPaymentMethod(
            $subOrder->getPayment() ?
                $subOrder->getPayment()->getMethod() : ''
        );
        return $detail;
    }

    /**
     * @param Order $subOrder
     * @return ParentOrder
     * @throws LocalizedException
     */
    private function createNewLink(\Magento\Sales\Model\Order $subOrder)
    {
        /**
         * @var $mpsplitOrder \Webkul\Mpsplitorder\Model\Mpsplitorder
         */
        if ($this->getParentOrderBySubOrder($subOrder)) {
            throw new LocalizedException(__('Can not create new parent order , already exits'));
        }
        $mpsplitOrder = $this->mpsplitorderFactory->create();
        $mpsplitOrder->setPaymentStatus(0);
        $mpsplitOrder->save();
        $isHotaiOldOrder = str_contains($subOrder->getIncrementId(), "hotai_order_") == true;
        $parentOrder = $this->parentOrderFactory->create()->load($mpsplitOrder->getId());
        if ($isHotaiOldOrder) {
            $incrementId = $this->hotaiGenerateIncrementIdForOrders->generateForParentOrder(
                $mpsplitOrder
            );
        } else {
            //hot fix missing parent order https://trello.com/c/84FTNXAx
            $incrementId = substr($subOrder->getIncrementId(), 0, 18);
        }
        $detail = $this->updateDetailParentOrder($parentOrder, $subOrder);
        $detail->setIncrementId($incrementId);
        $detailData = $detail->getData();
        //$detail->save();
        $parentOrder->setData('order_ids', join(',', [$subOrder->getId()]));
        $parentOrder->setData('last_order_id', $subOrder->getId());
        $parentOrder->setData('hotai_reserved_order_id', $incrementId);
        $this->removeLink($subOrder);
        $parentOrder->save();
        $detailData['parent_id'] = $parentOrder->getId();
        $this->insertLinkedData[] = ['parent_id' => $parentOrder->getId(), 'children_id' => $subOrder->getId()];
        $insertData = [];
        foreach ($this->detailColumns as $column) {
            if (empty($detailData[$column])) {
                $insertData[$column] = '';
            } else {
                $insertData[$column] = $detailData[$column];
            }
        }
        unset($insertData['shipping_address_id']);
        unset($insertData['billing_address_id']);
        $insertData['parent_id'] = $parentOrder->getId();
        $this->insertDetailData[] = $insertData;
        $this->countItemsCreated += 1;
        return $parentOrder;
    }

    /**
     * @param \Magento\Sales\Model\Order $subOrder
     * @return ParentOrder|null
     */
    private function getParentOrderBySubOrder(\Magento\Sales\Model\Order $subOrder)
    {
        if (!empty($this->parentOrderBySubOrder[$subOrder->getId()])) {
            return $this->parentOrderBySubOrder[$subOrder->getId()];
        }
        $parentOrder = $this->parentOrderFactory->create();
        $id = $parentOrder->getResource()->getParentOrder((int)$subOrder->getId());
        $parentOrder->load($id);
        if ($id) {
            $this->parentOrderBySubOrder[$subOrder->getId()] = $parentOrder->load($id);
            return $this->parentOrderBySubOrder[$subOrder->getId()];
        }
        return null;
    }

    /**
     * @param $parentOderNumber
     * @param Order $subOrder
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|ParentOrder|mixed|void
     * @throws Exception
     */
    private function updateOldLink($parentOderNumber, \Magento\Sales\Model\Order $subOrder)
    {
        $parentOrder = null;
        if ($parentOderNumber) {
            try {
                $parentOrder = $this->getParentOrder($parentOderNumber);
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }
        } else {
            $parentOrder = $this->getParentOrderBySubOrder($subOrder);
        }
        if (empty($parentOrder)) {
            return;
        }
        $detail = $this->updateDetailParentOrder($parentOrder, $subOrder);
        //$detail->save();
        $currentSuborderIds = $parentOrder->getSuborderIds();
        $currentSuborderIds[] = $subOrder->getId();
        //$this->removeLink($subOrder);
        /* $this->parentOrderManagement->assignSubordersToParentOrder(
             $parentOrder->getId(),
             [$subOrder->getId()]
         );*/
        $parentOrder->setData('order_ids', join(',', $currentSuborderIds));
        $parentOrder->setData('last_order_id', $subOrder->getId());
        $parentOrder->save();
        $detailData = $detail->getData();
        $this->insertLinkedData[] = ['parent_id' => $parentOrder->getId(), 'children_id' => $subOrder->getId()];
        $insertData = [];
        foreach ($this->detailColumns as $column) {
            if (empty($detailData[$column])) {
                $insertData[$column] = '';
            } else {
                $insertData[$column] = $detailData[$column];
            }
        }
        unset($insertData['shipping_address_id']);
        unset($insertData['billing_address_id']);
        $insertData['parent_id'] = $parentOrder->getId();
        $this->insertDetailData[] = $insertData;
        $this->countItemsUpdated += 1;
        return $parentOrder;
    }

    /**
     * @param $row
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|ParentOrder|false|mixed|null
     * @throws LocalizedException
     */
    private function buildInsertData($row)
    {
        if ($row) {
            try {
                $subOrder = $this->getOrder($row[static::SUB_ORDER_NUMBER]);
                try {
                    $parentOrder = $this->getParentOrderBySubOrder($subOrder);
                   /* if (empty($parentOrder)) {
                        $parentOrder = $this->getParentOrderBySubOrder($subOrder);
                    }*/
                } catch (\Exception $exception) {
                    $parentOrder = null;
                }
                if (empty($parentOrder)) {
                    $parentOrder = $this->createNewLink($subOrder);
                } else {
                    $parentOrder = $this->updateOldLink($row[static::PARENT_ORDER_NUMBER], $subOrder);
                }
                if (!empty($parentOrder)) {
                    $this->buildParentOrderAddress($parentOrder, $subOrder);
                }
                $this->_processedRowsCount++;
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                $this->_processedRowsCount++;
                throw $e;
            }
            /* $this->connection->insertOnDuplicate($tableName, $rows, $this->getAvailableColumns());*/
            return $parentOrder;
        }
        return false;
    }

    /**
     * @param $orderId
     * @return mixed
     */
    private function alreadyLinkWithAnotherParentOrder($orderId)
    {
        $select = $this->connection->select();
        $select->from('sales_parent_order_children')->where('children_id = ?', $orderId);
        return $this->connection->fetchRow($select);
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Model\ParentOrder
     * @throws Exception
     */
    private function createParentOrder()
    {
        return $this->parentOrderFactory->create()->save();
    }

    /**
     * Get available columns
     *
     * @return array
     */
    private function getAvailableColumns(): array
    {
        return $this->validColumnNames;
    }
}
