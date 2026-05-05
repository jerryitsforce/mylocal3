<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Webapi\Rest\Response;
use Psr\Log\LoggerInterface;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Ecpay\Invoice\Model\HotaiOrderInvoiceLogs as InvoiceStatus;
use HotaiConnected\FinancialReconciliation\Helper\EcpaySellerRevenueExporter;
use Magento\Framework\DB\Sql\Expression;
use ZipArchive;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class ReconciliationManagement implements \HotaiConnected\FinancialReconciliation\Api\ReconciliationManagementInterface
{
    const TOPIC_RECONCILIATION_EXPORT = 'hotaiconnected.reconciliation.revenue.export';
    const TOPIC_ORDER_EXPORT = 'hotaiconnected.reconciliation.order.export';
    const TOPIC_TICKET_EXPORT = 'hotaiconnected.reconciliation.ticket.export';

    const SETTLEMENT_STATUS = [
        1 => '建立',
        2 => '解除',
        3 => '寄出對帳單'
    ];

    const EXCEPTION_STATUS = [
        1 => '待審核',
        2 => '拒絕',
        3 => '審核通過'
    ];

    const ERROR_COLUMN_REQUIRED      = '001';
    const ERROR_DUPLICATE_TIMERANGE  = '002';
    const ERROR_BATCH_NUM_EXIST      = '003';
    const ERROR_DATETIME_FORMAT      = '004';
    const ERROR_INVALID_SELLER_CODE  = '005';

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    private $response;
    private $authSession;
    private $ecpaySellerRevenueExporter;
    private $fileSystem;
    private $publisher;
    private $jsonSerializer;
    private $aclHelper;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param Response $response
     * @param AuthSession $authSession
     * @param EcpaySellerRevenueExporter $ecpaySellerRevenueExporter
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param PublisherInterface $publisher
     * @param JsonSerializer $jsonSerializer
     * @param \HotaiConnected\FinancialReconciliation\Helper\Acl $aclHelper
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        Response $response,
        AuthSession $authSession,
        EcpaySellerRevenueExporter $ecpaySellerRevenueExporter,
        \Magento\Framework\Filesystem $fileSystem,
        PublisherInterface $publisher,
        JsonSerializer $jsonSerializer,
        \HotaiConnected\FinancialReconciliation\Helper\Acl $aclHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->response = $response;
        $this->authSession = $authSession;
        $this->ecpaySellerRevenueExporter = $ecpaySellerRevenueExporter;
        $this->fileSystem = $fileSystem;
        $this->publisher = $publisher;
        $this->jsonSerializer = $jsonSerializer;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function postReconciliation(
        string $from,
        string $to,
        string $seller_code,
        string $batch_num,
        string $shipping_status,
        string $invoice_status,
        string $ticket_status,
        int $exclude_settled_orders = 0,
        int $exclude_pending_gift_order = 0
    ) {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('financial_reconciliation');

        try {
            if (empty($from) || empty($to) || empty($seller_code) || empty($batch_num)) {
                $missingFields = [];
                if (empty($from)) {
                    $missingFields[] = 'from';
                }
                if (empty($to)) {
                    $missingFields[] = 'to';
                }
                if (empty($seller_code)) {
                    $missingFields[] = 'seller_code';
                }
                if (empty($batch_num)) {
                    $missingFields[] = 'batch_num';
                }
                $message = implode(', ', $missingFields) . ' is required.';
                return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => $message, 'code' => self::ERROR_COLUMN_REQUIRED]))
                ->sendResponse();
            }

            if (!\DateTime::createFromFormat('Y-m-d H:i:s', $from) && !\DateTime::createFromFormat('Y-m-d', $from)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Invalid \'from\' date format. Expected Y-m-d H:i:s or Y-m-d.', 'code' => self::ERROR_DATETIME_FORMAT]))
                ->sendResponse();
            }

            if (!\DateTime::createFromFormat('Y-m-d H:i:s', $to) && !\DateTime::createFromFormat('Y-m-d', $to)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Invalid \'to\' date format. Expected Y-m-d H:i:s or Y-m-d.', 'code' => self::ERROR_DATETIME_FORMAT]))
                ->sendResponse();
            }

            // Check for batch_num uniqueness for the current seller_code
            $select = $connection->select()->from($tableName)
                ->where('batch_num = ?', $batch_num);
            $result = $connection->fetchOne($select);

            if ($result !== false) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Batch number ' . $batch_num . ' already exists', 'code' => self::ERROR_BATCH_NUM_EXIST]))
                ->sendResponse();
            }

            $adminUserId = null;
            if ($this->authSession->isLoggedIn()) {
                $adminUserId = $this->authSession->getUser()->getId();
            }

            $sellerCodesToProcess = [];
            $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');

            if ($seller_code === 'all') {
                $allSellerCodesSelect = $connection->select()
                    ->from($marketplaceUserdataTableName, ['seller_code']);
                $sellerCodesToProcess = $connection->fetchCol($allSellerCodesSelect);
                if (empty($sellerCodesToProcess)) {
                    return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'No seller codes found in marketplace userdata.']))
                    ->sendResponse();
                }
            } else {
                $sellerCodesInput = [];
                if (strpos($seller_code, ",") !== false) {
                    $sellerCodesInput = explode(',', $seller_code);
                }
                else {
                    $sellerCodesInput[] = $seller_code;
                }

                $invalidSellerCodes = [];
                foreach ($sellerCodesInput as $code) {
                    $code = trim($code);
                    if (empty($code)) {
                        continue;
                    }
                    $sellerCodeSelect = $connection->select()
                        ->from($marketplaceUserdataTableName, 'seller_code')
                        ->where('seller_code = ?', $code);
                    $sellerCodeExists = $connection->fetchOne($sellerCodeSelect);

                    if (!$sellerCodeExists) {
                        $invalidSellerCodes[] = $code;
                    } else {
                        $sellerCodesToProcess[] = $code;
                    }
                }

                if (!empty($invalidSellerCodes)) {
                    return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid seller codes: ' . implode(', ', $invalidSellerCodes) . '. Please ensure all seller codes exist in marketplace userdata.', 'code' => self::ERROR_INVALID_SELLER_CODE]))
                    ->sendResponse();
                }

                if (empty($sellerCodesToProcess)) {
                    return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'No valid seller codes provided.', 'code' => self::ERROR_INVALID_SELLER_CODE]))
                    ->sendResponse();
                }
            }

            // Check for overlapping time ranges for each seller code
            $overallResponse = ['success' => true, 'message' => []];
            if (!empty($sellerCodesToProcess)) {
                $overlapSelect = $connection->select()
                    ->from($tableName, ['seller_code']) 
                    ->where('seller_code IN (?)', $sellerCodesToProcess)
                    ->where('`from` <= ?', $to)
                    ->where('`to` >= ?', $from);

                // fetchCol() 會回傳一個包含所有符合條件的 seller_code 的陣列
                $overlappingSellers = $connection->fetchCol($overlapSelect);

                if (!empty($overlappingSellers)) {
                    $overallResponse['success'] = false;
                    $overallResponse['code'] = self::ERROR_DUPLICATE_TIMERANGE;
                    foreach ($overlappingSellers as $sellerCode) {
                        $overallResponse['message'][] = $sellerCode;
                    }
                }
            }

            if (!empty($overallResponse['message'])) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode($overallResponse))
                    ->sendResponse();
            }

            foreach ($sellerCodesToProcess as $currentSellerCode) {
                if (empty($currentSellerCode)) {
                    continue;
                }

                // Check total_paid before inserting
                $totalPaidSelect = $connection->select()
                    ->from(
                        ['order_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
                        [
                            'total_paid' => new Expression('
                                ROUND(
                                    SUM(
                                        CASE WHEN order_log.is_reverse = 1 THEN -1 ELSE 1 END * 
                                        (
                                            (COALESCE(sales_order_item.base_cost * order_item_log.qty, 0)) 
                                            - COALESCE(sales_order_item.seller_borne_total_amount, 0)
                                            + 0
                                        )
                                    )
                                )
                            ')
                        ]
                    )
                    ->join(
                        ['order_item_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
                        'order_log.hotai_order_invoice_logs_id = order_item_log.hotai_order_invoice_log_id',
                        []
                    )
                    ->join(
                        ['sales_order_item' => $this->resourceConnection->getTableName('sales_order_item')],
                        'order_item_log.order_item_id = sales_order_item.item_id',
                        []
                    )
                    ->where('sales_order_item.seller_code = ?', $currentSellerCode)
                    ->where('order_log.created_at >= ?', $from)
                    ->where('order_log.created_at <= ?', $to)
                    ->where('order_item_log.type = ?', 'item');

                $totalPaid = $connection->fetchOne($totalPaidSelect);

                if ($totalPaid == 0) {
                    $overallResponse['message'][] = 'Skipped seller code ' . $currentSellerCode . ': Total paid is 0.';
                    continue;
                }

                $data = [
                    'from' => $from,
                    'to' => $to,
                    'seller_code' => $currentSellerCode,
                    'batch_num' => $batch_num,
                    'exclude_settled_orders' => $exclude_settled_orders,
                    'exclude_pending_gift_order' => $exclude_pending_gift_order,
                    'shipping_status' => $shipping_status,
                    'invoice_status' => $invoice_status,
                    'ticket_status' => $ticket_status,
                    'settlement_status' => 1,
                    'changed_by_id' => $adminUserId ?? 0,
                ];

                $connection->insert($tableName, $data);
                $overallResponse['message'][] = 'Data inserted successfully for seller code ' . $currentSellerCode . '.';
            }

            $response = $overallResponse;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode($response))
                ->sendResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() {
        $connection = $this->resourceConnection->getConnection();
        $adminUser = $this->authSession->getUser();
        $adminUserId = $adminUser ? $adminUser->getId() : null;
        $roleIds = [];

        if ($adminUserId) {
            $authorizationRoleTableName = $this->resourceConnection->getTableName('authorization_role');
            $roleSelect = $connection->select()
                ->from(['ar' => $authorizationRoleTableName], ['parent_id'])
                ->where('ar.user_id = ?', (int)$adminUserId);
            $roleIds = $connection->fetchCol($roleSelect);
        }

        $orderStatuses = [
            OrderStatus::STATUS_PROCESSING,
            OrderStatus::STATUS_SHIPPING,
            OrderStatus::STATUS_ARRIVED,
            OrderStatus::STATUS_COMPLETE,
            OrderStatus::STATUS_TALLYING,
            OrderStatus::STATUS_GIFT_INFO_PENDING,
            OrderStatus::STATUS_GIFT_INFO_COMPLETE
        ];

        $ticketStatuses = [
            [
                "code"   => TicketStatus::STATUS_OVER_DUE,
                "en_name"=>"STATUS_OVER_DUE",
                "zh_name"=> "已過期"
            ],
            [
                "code"   => TicketStatus::STATUS_RETURNED,
                "en_name"=>"STATUS_RETURNED",
                "zh_name"=> "已退貨(已取消)"
            ],
            [
                "code"   => TicketStatus::STATUS_UNUSED,
                "en_name"=>"STATUS_UNUSED",
                "zh_name"=> "未使用;已賣出"
            ],
            [
                "code"   => TicketStatus::STATUS_USED,
                "en_name"=>"STATUS_USED",
                "zh_name"=> "已使用"
            ]
        ];

        $invoiceStatuses = [
            [
                "code"   => InvoiceStatus::CREATED,
                "en_name"=>"CREATED",
                "zh_name"=> "開立"
            ],
            [
                "code"   => InvoiceStatus::INVALID,
                "en_name"=>"INVALID",
                "zh_name"=> "作廢"
            ],
            [
                "code"   => InvoiceStatus::DISCOUNT,
                "en_name"=>"DISCOUNT",
                "zh_name"=> "折讓"
            ],
            [
                "code"   => InvoiceStatus::NO_INVOICE,
                "en_name"=>"NO_INVOICE",
                "zh_name"=> "不開發票"
            ]
        ];

        $connection = $this->resourceConnection->getConnection();
        $authorizationRoleTableName = $this->resourceConnection->getTableName('authorization_role');
        $shopTitleTableName = $this->resourceConnection->getTableName('marketplace_userdata');

        // 判斷是否具備全覽權限 (1: Admin, 232: 財務)
        $isFullAccess = $this->aclHelper->isFullAccess($adminUserId);

        $select = $connection->select()
            ->from($authorizationRoleTableName, ['role_id', 'role_name'])
            ->where('user_id = ?', 0);

        if (!$isFullAccess) {
            if (!empty($roleIds)) {
                $select->where('role_id IN (?)', $roleIds);
            } else {
                $select->where('1=0');
            }
        }
        $salespersonRoles = $connection->fetchAll($select);
        $shopTitleSelect = $connection->select()
            ->from($shopTitleTableName, ['seller_id', 'shop_title']);

        if (!$isFullAccess) {
            if (!empty($roleIds)) {
                $shopTitleSelect->where('salesperson IN (?)', $roleIds);
            } else {
                $shopTitleSelect->where('1=0');
            }
        }
        $shopTitleData = $connection->fetchAll($shopTitleSelect);

        return $this->response->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode([
                'order_status' => array_values($orderStatuses),
                'ticket_status' => $ticketStatuses,
                'invoice_status' => array_values($invoiceStatuses),
                'sales_person' => $salespersonRoles,
                'shop_title' => $shopTitleData
            ]))
            ->sendResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function getReconciliation(
        string $seller_code = null,
        string $from_date = null,
        string $to_date = null,
        int $settlement_status = null,
        string $batch_num = null,
        string $sales_person = null,
        string $tax_id = null,
        string $ticket_status = null,
        string $shipping_status = null,
        string $invoice_status = null,
        string $invoice_number = null,
        int $page_size = null,
        int $current_page = null
    ) {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('financial_reconciliation');
        $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');

        try {
            if (empty($page_size)) {
                $page_size = 10;
            }
            if (empty($current_page)) {
                $current_page = 1;
            }

            $adminUser = $this->authSession->getUser();
            $adminUserId = $adminUser ? $adminUser->getId() : null;
            $roleIds = [];

            if ($adminUserId) {
                $authorizationRoleTableName = $this->resourceConnection->getTableName('authorization_role');
                $roleSelect = $connection->select()
                    ->from(['ar' => $authorizationRoleTableName], ['parent_id'])
                    ->where('ar.user_id = ?', (int)$adminUserId);
                $roleIds = $connection->fetchCol($roleSelect);
            }

            $select = $connection->select()
                ->from(['fr' => $tableName])
                ->joinLeft(
                    ['mu' => $marketplaceUserdataTableName],
                    'fr.seller_code = mu.seller_code',
                    ['shop_title' => 'mu.shop_title', 'salesperson']
                )
                ->joinLeft(
                    ['ar' => $this->resourceConnection->getTableName('authorization_role')],
                    'ar.role_id = mu.salesperson',
                    ['salesperson_role_name' => 'ar.role_name']
                )
                ->joinLeft(
                    ['ms' => $this->resourceConnection->getTableName('marketplace_saleperpartner')],
                    'mu.seller_id = ms.seller_id',
                    ['commission_rate' => 'ms.commission_rate']
                )
                ->joinLeft(
                    ['fri' => $this->resourceConnection->getTableName('financial_reconciliation_invoice')],
                    'fr.id = fri.fr_id',
                    []
                )
                ->joinLeft(
                    ['fre' => $this->resourceConnection->getTableName('financial_reconciliation_exception')],
                    'fre.fr_id = fr.id',
                    []
                );

            if (!empty($seller_code)) {
                $seller_code = urldecode($seller_code);
                $sellerCodes = explode(',', $seller_code);
                $sellerCodes = array_map('trim', $sellerCodes);
                $sellerCodes = array_filter($sellerCodes);
                if (!empty($sellerCodes)) {
                    $select->where('fr.seller_code IN (?)', $sellerCodes);
                }
            }
            if ($from_date !== null) {
                $select->where('fr.`from` >= ?', $from_date);
            }
            if ($to_date !== null) {
                $select->where('fr.`to` <= ?', $to_date);
            }
            if ($settlement_status !== null) {
                $select->where('fr.settlement_status = ?', $settlement_status);
            }
            if (!empty($batch_num)) {
                $select->where('fr.batch_num = ?', $batch_num);
            }
            if (!empty($sales_person)) {
                $select->where('ar.role_id IN (?)', $sales_person);
            }
            if (!empty($tax_id)) {
                $select->where('mu.invoice_company_no = ?', $tax_id);
            }
            if (!empty($ticket_status)) {
                $select->where('fr.ticket_status = ?', $ticket_status);
            }
            if (!empty($shipping_status)) {
                $select->where('fr.shipping_status = ?', $shipping_status);
            }
            if (!empty($shipping_status)) {
                $select->where('fr.invoice_status = ?', $invoice_status);
            }
            if (!empty($invoice_number)) {
                $select->where('fri.invoice_number = ?', $invoice_number);
            }

            // 判斷是否具備全覽權限 (1: Admin, 232: 財務)
            $isFullAccess = $this->aclHelper->isFullAccess($adminUserId);
            if (!$isFullAccess) {
                if (!empty($roleIds)) {
                    $select->where('mu.salesperson IN (?)', $roleIds);
                } else {
                    $select->where('1=0');
                }
            }

            // Ensure page_size and current_page are not null for internal logic
            $page_size = $page_size ?? 10;
            $current_page = $current_page ?? 1;

            if ($page_size <= 0) {
                $page_size = 10;
            }
            if ($current_page <= 0) {
                $current_page = 1;
            }

            // Clone for counting BEFORE grouping and adding specific columns
            $countSelect = clone $select;
            $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
            $countSelect->columns('COUNT(DISTINCT fr.id)'); // Count distinct IDs to get correct total records
            $totalRecords = $connection->fetchOne($countSelect);

            // Now apply grouping and specific columns to the main select for fetching items
            $select->group('fr.id')
                   ->columns([
                       'invoice_numbers' => new Expression('GROUP_CONCAT(DISTINCT fri.invoice_number)'),
                       'has_exception' => new Expression('CASE WHEN SUM(CASE WHEN fre.exception_status = 3 THEN 1 ELSE 0 END) > 0 THEN TRUE ELSE FALSE END')
                   ]);

            $select->limitPage($current_page, $page_size);
            $result = $connection->fetchAll($select);

            foreach ($result as &$item) {
                if (isset($item['has_exception'])) {
                    $item['has_exception'] = (bool)$item['has_exception'];
                }

                if (isset($item['created_at'])) {
                    $item['created_at'] = (new \DateTime($item['created_at']))->setTimezone(new \DateTimeZone('Asia/Taipei'))->format('Y-m-d H:i:s');
                }
                if (isset($item['updated_at'])) {
                    $item['updated_at'] = (new \DateTime($item['updated_at']))->setTimezone(new \DateTimeZone('Asia/Taipei'))->format('Y-m-d H:i:s');
                }

                // Calculate total_paid
                $sellerCode = $item['seller_code'];
                $fromDate = $item['from'];
                $toDate = $item['to'];

                $totalPaidSelect = $connection->select()
                    ->from(
                        ['order_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
                        [
                            'total_paid' => new Expression('
                                ROUND(
                                    SUM(
                                        CASE WHEN order_log.is_reverse = 1 THEN -1 ELSE 1 END * 
                                        (
                                            (COALESCE(sales_order_item.base_cost * order_item_log.qty, 0)) 
                                            - COALESCE(sales_order_item.seller_borne_total_amount, 0)
                                            + 0
                                        )
                                    )
                                )
                            '),
                            'special_price' => new Expression('
                                SUM(
                                    CASE
                                        WHEN sales_order_item.variation_price > 0
                                            THEN (CASE WHEN order_log.is_reverse = 1 THEN -ROUND(sales_order_item.variation_price) ELSE ROUND(sales_order_item.variation_price) END)
                                        ELSE (CASE WHEN order_log.is_reverse = 1 THEN -ROUND(sales_order_item.special_price) ELSE ROUND(sales_order_item.special_price) END)
                                    END
                                    * sales_order_item.qty_ordered
                                )
                            ')
                        ]
                    )
                    ->join(
                        ['order_item_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
                        'order_log.hotai_order_invoice_logs_id = order_item_log.hotai_order_invoice_log_id',
                        []
                    )
                    ->join(
                        ['sales_order_item' => $this->resourceConnection->getTableName('sales_order_item')],
                        'order_item_log.order_item_id = sales_order_item.item_id',
                        []
                    )
                    ->where('sales_order_item.seller_code = ?', $sellerCode)
                    ->where('order_log.created_at >= ?', $fromDate)
                    ->where('order_log.created_at <= ?', $toDate)
                    ->where('order_item_log.type = ?', 'item');

                $calculatedData = $connection->fetchRow($totalPaidSelect);

                $item['data_source'] = '對帳單建立';
                $item['price'] = ($calculatedData && isset($calculatedData['special_price'])) ? (float)$calculatedData['special_price'] : 0;
                $item['total_paid'] = ($calculatedData && isset($calculatedData['total_paid'])) ? (float)$calculatedData['total_paid'] : 0;
                if (isset($item['settlement_status']) && isset(self::SETTLEMENT_STATUS[(int)$item['settlement_status']])) {
                    $item['del_btn_visible'] = true;
                    if ($item['settlement_status'] == 2) {
                        $item['del_btn_visible'] = false;
                    }
                    $item['settlement_status'] = self::SETTLEMENT_STATUS[(int)$item['settlement_status']];
                }
            }

            $response = [
                'items' => $result,
                'total_record' => (int)$totalRecords,
                'pagination' => $current_page,
                'page_size' => $page_size
            ];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode($response))
                ->sendResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteReconciliation()
    {
        $postDataOrigin = file_get_contents('php://input');
        try{
            $postData = json_decode($postDataOrigin, true);
        }catch (\Exception $e){
            $postData = null;
        }
        $ids = $postData['ids'] ?? [];

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('financial_reconciliation');

        try {
            if (empty($ids)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'IDs array cannot be empty.']))
                    ->sendResponse();
            }

            $connection->beginTransaction();

            // Delete related invoices
            $connection->delete(
                $this->resourceConnection->getTableName('financial_reconciliation_invoice'),
                ['fr_id IN (?)' => $ids]
            );

            // Delete related exceptions
            $connection->delete(
                $this->resourceConnection->getTableName('financial_reconciliation_exception'),
                ['fr_id IN (?)' => $ids]
            );

            // Delete main reconciliation records
            $connection->delete($tableName, ['id IN (?)' => $ids]);

            $connection->commit();

            $response = ['success' => true, 'message' => 'Reconciliation records deleted successfully.'];
        } catch (\Exception $e) {
            if ($connection->getTransactionLevel() > 0) {
                $connection->rollBack();
            }
            $this->logger->error($e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        return $this->response->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode($response))
            ->sendResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function downloadEcpaySellerRevenueDetail(string $ids)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('financial_reconciliation');

        try {
            $reconciliationIds = array_filter(array_map('trim', explode(',', $ids)));

            if (empty($reconciliationIds)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Reconciliation IDs cannot be empty.']))
                    ->sendResponse();
            }

            $select = $connection->select()
                ->from($tableName, ['from', 'to', 'seller_code', 'exclude_pending_gift_order'])
                ->where('id IN (?)', $reconciliationIds);
            $reconciliationRecords = $connection->fetchAll($select);

            if (empty($reconciliationRecords)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'No reconciliation records found for the provided IDs.']))
                    ->sendResponse();
            }

            $exportData = [];
            $sellerCodeAry = [];
            $sellerCode = '';
            foreach ($reconciliationRecords as $record) {
                $sellerCode = $record['seller_code'];
                // Group by seller_code, from and to date, as the export command processes per seller/date range
                $key = $record['seller_code'] . '|' . $record['from'] . '|' . $record['to'];
                $exportData[$key] = [
                    'seller_code' => $record['seller_code'],
                    'from_date' => $record['from'],
                    'to_date' => $record['to'],
                ];
            }

            $generatedExcelFiles = [];
            $directoryWrite = $this->fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
            $tempRelativePath = 'export/seller_revenue_detail/';
            $tempDir = $directoryWrite->getAbsolutePath($tempRelativePath);

            // Create temporary directory if it doesn't exist
            if (!$directoryWrite->isExist($tempRelativePath)) {
                $directoryWrite->create($tempRelativePath);
            }

            foreach ($exportData as $key => $data) {
                // exportAndZip now returns the path to an Excel file
                $excelFilePath = $this->ecpaySellerRevenueExporter->exportAndZip(
                    $data['from_date'],
                    $data['to_date'],
                    $data['seller_code']
                );
                if (file_exists($excelFilePath)) {
                    $generatedExcelFiles[] = $excelFilePath;
                }
            }

            if (empty($generatedExcelFiles)) {
                // Clean up temp directory if no files were generated
                $this->deleteDirectory($tempRelativePath);
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'No export files were generated.']))
                    ->sendResponse();
            }

            $shopTitle = $connection->fetchOne(
                $connection->select()
                    ->from($this->resourceConnection->getTableName('marketplace_userdata'), 'shop_title')
                    ->where('seller_code = ?', $sellerCode)
            );
            $dateStr = (new \DateTime('now', new \DateTimeZone('Asia/Taipei')))->format('Ymd');
            $finalZipFileName = '廠商對帳單-' . $sellerCode . ($shopTitle ? '(' . $shopTitle . ')' : '') . '_' . $dateStr . '.zip';
            $finalZipFilePath = $tempDir . $finalZipFileName;

            $zip = new ZipArchive();
            if ($zip->open($finalZipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                foreach ($generatedExcelFiles as $excelFile) {
                    $zip->addFile($excelFile, basename($excelFile));
                }
                $zip->close();

                // Clean up individual excel files after adding to the main zip
                foreach ($generatedExcelFiles as $excelFile) {
                    unlink($excelFile);
                }
            } else {
                // Clean up temp directory on zip creation failure
                $this->deleteDirectory($tempRelativePath);
                throw new \Exception('Could not create zip archive.');
            }

            $asciiZipFileName = 'reconciliation-' . $sellerCode . '_' . $dateStr . '.zip';
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($finalZipFileName) . '; filename="' . $asciiZipFileName . '"');

            ob_clean();
            flush();

            readfile($finalZipFilePath);

            // Clean up the generated final zip file and temp directory after sending
            unlink($finalZipFilePath);
            $this->deleteDirectory($tempRelativePath);
            exit;

        } catch (\Exception $e) {
            $this->logger->error('Download API Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     * 寄出對帳單
     */
    public function updateSettlementStatus(array $ids)
    {
        if (empty($ids)) {
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'IDs array cannot be empty.']))
                ->sendResponse();
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('financial_reconciliation');

        try {
            $connection->beginTransaction();

            $adminUserId = null;
            if ($this->authSession->isLoggedIn()) {
                $adminUserId = $this->authSession->getUser()->getId();
            }

            $bind = [
                'settlement_status' => 3,
                'changed_by_id' => $adminUserId ?? 0,
            ];
            $where = [
                'id IN (?)' => $ids,
                'settlement_status = ?' => 1
            ];

            $updatedRows = $connection->update($tableName, $bind, $where);
            
            if ($updatedRows === 0) {
                $connection->rollBack();
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'No records were updated. Ensure IDs are valid and settlement status is 1.', 'updated_rows' => 0]))
                    ->sendResponse();
            }

            $connection->commit();

            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => true, 'message' => 'Settlement status updated successfully.', 'updated_rows' => $updatedRows]))
                ->sendResponse();

        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->error('Update Settlement Status Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Could not update settlement status: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addInvoices(int $id, array $invoices)
    {
        if (empty($invoices)) {
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Invoices array cannot be empty.']))
                ->sendResponse();
        }

        $connection = $this->resourceConnection->getConnection();
        $invoiceTableName = $this->resourceConnection->getTableName('financial_reconciliation_invoice');

        try {
            $invalidInvoices = [];
            $invoicePattern = '/^[A-Z]{2}\d{8}$/'; // Two uppercase letters followed by 8 digits

            foreach ($invoices as $invoiceNumber) {
                if (!preg_match($invoicePattern, $invoiceNumber)) {
                    $invalidInvoices[] = $invoiceNumber;
                }
            }

            if (!empty($invalidInvoices)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid invoice number format for: ' . implode(', ', $invalidInvoices) . '. Expected format: two uppercase letters followed by 8 digits.']))
                    ->sendResponse();
            }

            // Check for existing invoice numbers
            $select = $connection->select()
                ->from($invoiceTableName, ['invoice_number'])
                ->where('invoice_number IN (?)', $invoices);
            $existingInvoices = $connection->fetchCol($select);

            if (!empty($existingInvoices)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Duplicate invoice numbers found: ' . implode(', ', $existingInvoices)]))
                    ->sendResponse();
            }

            $dataToInsert = [];
            foreach ($invoices as $invoiceNumber) {
                $dataToInsert[] = [
                    'fr_id' => $id,
                    'invoice_number' => $invoiceNumber
                ];
            }

            $connection->insertMultiple($invoiceTableName, $dataToInsert);

            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => true, 'message' => 'Invoices added successfully.', 'added_count' => count($dataToInsert)]))
                ->sendResponse();

        } catch (\Exception $e) {
            $this->logger->error('Add Invoices Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Could not add invoices: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateInvoices(int $id, string $old_invoice_number, string $new_invoice_number)
    {
        $connection = $this->resourceConnection->getConnection();
        $invoiceTableName = $this->resourceConnection->getTableName('financial_reconciliation_invoice');

        try {
            $invoicePattern = '/^[A-Z]{2}\d{8}$/';

            if (!preg_match($invoicePattern, $old_invoice_number)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid old invoice number format. Expected format: two uppercase letters followed by 8 digits.']))
                    ->sendResponse();
            }

            if (!preg_match($invoicePattern, $new_invoice_number)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid new invoice number format. Expected format: two uppercase letters followed by 8 digits.']))
                    ->sendResponse();
            }

            // Check if old invoice number exists for this reconciliation ID
            $selectOld = $connection->select()
                ->from($invoiceTableName, ['invoice_number'])
                ->where('fr_id = ?', $id)
                ->where('invoice_number = ?', $old_invoice_number);
            $oldInvoiceExists = $connection->fetchOne($selectOld);

            if (!$oldInvoiceExists) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Old invoice number ' . $old_invoice_number . ' not found for this reconciliation record.']))
                    ->sendResponse();
            }

            // Check if new invoice number already exists
            $select = $connection->select()
                ->from($invoiceTableName, ['invoice_number'])
                ->where('invoice_number = ?', $new_invoice_number);
            $existingInvoice = $connection->fetchOne($select);

            if ($existingInvoice) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'New invoice number already exists: ' . $new_invoice_number]))
                    ->sendResponse();
            }

            $connection->beginTransaction();

            // Delete old invoice
            $where = [
                'fr_id = ?' => $id,
                'invoice_number = ?' => $old_invoice_number
            ];
            $connection->delete($invoiceTableName, $where);

            // Insert new invoice
            $dataToInsert = [
                'fr_id' => $id,
                'invoice_number' => $new_invoice_number
            ];
            $connection->insert($invoiceTableName, $dataToInsert);

            $connection->commit();

            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => true, 'message' => 'Invoices update successfully.', 'added_count' => 1]))
                ->sendResponse();

        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->error('Update Invoices Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Could not update invoices: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteInvoice()
    {
        $postDataOrigin = file_get_contents('php://input');
        try {
            $postData = json_decode($postDataOrigin, true);
        } catch (\Exception $e) {
            $postData = null;
        }

        $id = $postData['id'] ?? null;
        $invoice = $postData['invoice'] ?? null;

        $connection = $this->resourceConnection->getConnection();
        $invoiceTableName = $this->resourceConnection->getTableName('financial_reconciliation_invoice');

        try {
            if (empty($id) || empty($invoice)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'id and invoice are required.']))
                    ->sendResponse();
            }

            // Simple validation: at least 10 characters (adjusting for user example)
            if (strlen($invoice) < 10) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid invoice number format.']))
                    ->sendResponse();
            }

            // Check if invoice number exists for this reconciliation ID
            $select = $connection->select()
                ->from($invoiceTableName, ['invoice_number'])
                ->where('fr_id = ?', (int)$id)
                ->where('invoice_number = ?', $invoice);
            $invoiceExists = $connection->fetchOne($select);

            if (!$invoiceExists) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invoice record not found.']))
                    ->sendResponse();
            }

            // Delete invoice
            $where = [
                'fr_id = ?' => (int)$id,
                'invoice_number = ?' => $invoice
            ];
            $connection->delete($invoiceTableName, $where);

            $response = ['success' => true, 'message' => 'Invoice deleted successfully.'];
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode($response))
                ->sendResponse();

        } catch (\Exception $e) {
            $this->logger->error('Delete Invoice Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Could not delete invoice: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionAuth(string $ids)
    {
        $connection = $this->resourceConnection->getConnection();
        $financialReconciliationExceptionTableName = $this->resourceConnection->getTableName('financial_reconciliation_exception');
        $reconciliationTableName = $this->resourceConnection->getTableName('financial_reconciliation');
        $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');
        $authorizationRoleTableName = $this->resourceConnection->getTableName('authorization_role');

        try {
            $frIds = array_filter(array_map('trim', explode(',', $ids)));

            if (empty($frIds)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Exception IDs cannot be empty.']))
                    ->sendResponse();
            }

            // Select for financial_reconciliation_exception records with status = 3
            $exceptionSelect = $connection->select()
                ->from(['fre' => $financialReconciliationExceptionTableName], [
                    'id' => 'fre.id',
                    'fr_id' => 'fre.fr_id',
                    'created_at' => 'fre.created_at',
                    'updated_at' => 'fre.updated_at',
                    'commission_rate' => 'fre.commission_rate',
                    'agreed_service_fee' => 'fre.agreed_service_fee',
                    'net_sales' => 'fre.net_sales',
                    'purchase_cost' => 'fre.purchase_cost',
                    'marketing_fee' => 'fre.marketing_fee',
                    'logistic_support_fee' => 'fre.logistic_support_fee',
                    'reason' => 'fre.reason',
                    'exception_status' => 'fre.exception_status',
                ])
                ->joinLeft(
                    ['fr' => $reconciliationTableName],
                    'fre.fr_id = fr.id',
                    [
                        'from' => 'fr.from',
                        'to' => 'fr.to',
                        'exclude_settled_orders' => 'fr.exclude_settled_orders',
                        'seller_code' => 'fr.seller_code',
                        'shipping_status' => 'fr.shipping_status',
                        'invoice_status' => 'fr.invoice_status',
                        'ticket_status' => 'fr.ticket_status',
                        'batch_num' => 'fr.batch_num',
                        'settlement_status' => 'fr.settlement_status',
                        'changed_by_id' => 'fr.changed_by_id',
                    ]
                )
                ->joinLeft(
                    ['mu' => $marketplaceUserdataTableName],
                    'fr.seller_code = mu.seller_code',
                    ['shop_title' => 'mu.shop_title']
                )
                ->joinLeft(
                    ['ar' => $authorizationRoleTableName],
                    'ar.role_id = mu.salesperson',
                    ['salesperson_role_name' => 'ar.role_name']
                )
                ->where('fre.exception_status = ?', 3)
                ->where('fre.fr_id IN (?)', $frIds); // Filter by provided IDs

            $result = $connection->fetchAll($exceptionSelect);

            $groupedResult = [];
            $batchCounter = [];
            foreach ($result as &$item) {
                $frId = $item['fr_id'];
                if (!isset($batchCounter[$frId])) {
                    $batchCounter[$frId] = 0;
                }
                $batchCounter[$frId]++;
                $item['batch_num'] = $item['batch_num'] . sprintf('-%02d', $batchCounter[$frId]);

                $item['data_source'] = '例外授權';

                $exceptionStatus = (int)$item['exception_status'];
                if (isset(self::EXCEPTION_STATUS[$exceptionStatus])) {
                    $item['exception_status'] = self::EXCEPTION_STATUS[$exceptionStatus];
                } else {
                    $item['exception_status'] = '未知狀態';
                }

                $showValue = [];
                $valueFields = [
                    'commission_rate',
                    'agreed_service_fee',
                    'net_sales',
                    'purchase_cost',
                    'marketing_fee',
                    'logistic_support_fee'
                ];

                $fieldTranslations = [
                    'commission_rate' => '抽成%',
                    'agreed_service_fee' => '約定服務費',
                    'net_sales' => '特約商專櫃銷售淨額',
                    'purchase_cost' => '採購成本',
                    'marketing_fee' => '行銷負擔(廠商)',
                    'logistic_support_fee' => '運費補貼'
                ];

                foreach ($valueFields as $field) {
                    if (isset($item[$field]) && $item[$field] !== null && (float)$item[$field] != 0) {
                        $translatedFieldName = $fieldTranslations[$field] ?? $field;
                        $showValue[$translatedFieldName] = $item[$field];
                    }
                }
                $item['show_value'] = $showValue;

                // Remove original fields if they are not needed in the final output
                foreach ($valueFields as $field) {
                    unset($item[$field]);
                }

                if (isset($item['settlement_status']) && isset(self::SETTLEMENT_STATUS[(int)$item['settlement_status']])) {
                    $item['del_btn_visible'] = true;
                    if ($item['settlement_status'] == 2) {
                        $item['del_btn_visible'] = false;
                    }
                    $item['settlement_status'] = self::SETTLEMENT_STATUS[(int)$item['settlement_status']];
                }
                $groupedResult[$item['fr_id']][] = $item;
            }

            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => true, 'items' => $groupedResult]))
                ->sendResponse();

        } catch (\Exception $e) {
            $this->logger->error('Get Exception Auth Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exportTicket(
        string $start_date = null,
        string $end_date = null,
        string $type = null,
        string $order_created_from = null,
        string $order_created_to = null,
        string $event_created_from = null,
        string $event_created_to = null
    ) {
        // Handle inconsistent parameters from frontend
        if ($type === 'ticket') {
            $start_date = $start_date ?: $order_created_from;
            $end_date = $end_date ?: $order_created_to;
        } elseif ($type === 'event') {
            $start_date = $start_date ?: $event_created_from;
            $end_date = $end_date ?: $event_created_to;
        }

        try {
            $adminName = '';
            if ($this->authSession->isLoggedIn()) {
                $adminName = $this->authSession->getUser()->getUserName();
            }

            $messageData = [
                'admin_name' => $adminName,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'type' => $type
            ];

            $this->publisher->publish(
                self::TOPIC_TICKET_EXPORT,
                $this->jsonSerializer->serialize($messageData)
            );

            $message = ($type === 'ticket') ? '批次票券報表匯出(2.0) - Jobs enqueued successfully.' : '批次票券報表匯出(球池) - Jobs enqueued successfully.';

            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => true, 'message' => $message]))
                ->sendResponse();

        } catch (\Exception $e) {
            $this->logger->error('Enqueue ExportTicket Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Enqueue failed: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
        public function listTicket(
            string $order_created_from = null,
            string $order_created_to = null,
            string $shop_title = null,
            string $ticket_status = null,
            string $order_status = null,
            string $serial_number = null,
            string $use_start_time = null,
            string $use_end_time = null,
            string $redeemed_from = null,
            string $redeemed_to = null,
            string $invoice_created_from = null,
            string $invoice_created_to = null,
            string $type = null,
            string $event_created_from = null,
            string $event_created_to = null,
            int $page_size = null,
            int $current_page = null
        )
        {
            $connection = $this->resourceConnection->getConnection();
            $totalRecords = 0; // Initialize $totalRecords
    
            if (empty($type) || !in_array($type, ['event', 'ticket'])) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid or missing type parameter. Must be event or ticket']))
                    ->sendResponse();
            }

            if ($type === 'ticket') {
                if ((empty($order_created_from) || 
                    empty($order_created_to)) && 
                    (empty($invoice_created_from) ||
                    empty($invoice_created_to))) {
                    $missingFields = [];

                    if (empty($order_created_from) || empty($order_created_to)) {
                        $missingFields[] = 'order_created_from';
                        $missingFields[] = 'order_created_to';
                    }
                    if (empty($invoice_created_from) || empty($invoice_created_to)) {
                        $missingFields[] = 'invoice_created_from';
                        $missingFields[] = 'invoice_created_to';
                    }
                    $message = implode(', ', $missingFields) . ' is required for type ticket.';
                    return $this->response->setHeader('Content-Type', 'application/json', true)
                        ->setBody(json_encode(['success' => false, 'message' => $message, 'code' => self::ERROR_COLUMN_REQUIRED]))
                        ->sendResponse();
                }
            }

            if ($type === 'event') {
                if (empty($event_created_from) || empty($event_created_to)) {
                    $missingFields = [];
                    if (empty($event_created_from)) {
                        $missingFields[] = 'event_created_from';
                    }
                    if (empty($event_created_to)) {
                        $missingFields[] = 'event_created_to';
                    }
                    $message = implode(', ', $missingFields) . ' is required for type event.';
                    return $this->response->setHeader('Content-Type', 'application/json', true)
                        ->setBody(json_encode(['success' => false, 'message' => $message, 'code' => self::ERROR_COLUMN_REQUIRED]))
                        ->sendResponse();
                }
            }

            try {
            if ($type === 'event') {
                $select = $connection->select()
                    ->from(
                        ['mu' => $this->resourceConnection->getTableName('marketplace_userdata')],
                        [
                            'created_at' => new Expression("''"),
                            'ecpay_created_at' => new Expression("''"),
                            'shop_title' => 'mu.shop_title',
                            'hotai_checkout_number' => new Expression("''"),
                            'increment_id' => new Expression("''"),
                            'order_item_name' => new Expression("''"),
                            'qty' => new Expression("''"),
                            'special_price' => new Expression("''"),
                            'ticket_status' => new Expression("CASE
                                WHEN ct.status = " . TicketStatus::STATUS_OVER_DUE . " THEN '已過期'
                                WHEN ct.status = " . TicketStatus::STATUS_RETURNED . " THEN '已退貨（已取消）'
                                WHEN ct.status = " . TicketStatus::STATUS_ERROR . " THEN '異常'
                                WHEN ct.status = " . TicketStatus::STATUS_IMPORTED . " THEN '初始匯入'
                                WHEN ct.status = " . TicketStatus::STATUS_ALLOCATED . " THEN '預分配（已指定給quote_item）'
                                WHEN ct.status = " . TicketStatus::STATUS_UNUSED . " THEN '未核銷；已賣出'
                                WHEN ct.status = " . TicketStatus::STATUS_USED . " THEN '已使用'
                                END"),
                            'serial_number' => 'ct.ticket_unique_content',
                            'redeemed_at' => 'tet.redeemed_at',
                            'transaction_no' => new Expression("'-'"),
                            'use_end_time' => 'tet.end_date'
                        ]

                    )
                    ->joinLeft(
                        ['te' => $this->resourceConnection->getTableName('ticket_event')],
                        'mu.seller_id = te.seller_id',
                        []
                    )
                    ->joinLeft(
                        ['tet' => $this->resourceConnection->getTableName('ticket_event_ticket')],
                        'te.entity_id = tet.event_id',
                        []
                    )
                    ->joinLeft(
                        ['ct' => $this->resourceConnection->getTableName('customer_ticket')],
                        'tet.entity_id = ct.ticket_table_record_id AND ct.ticket_table_name = \'ticket_event_ticket\'',
                        []
                    )
                    ->group('tet.batch_code')
                    ->group('tet.serial_number')
                    ->order('ct.created_at');

                if ($event_created_from !== null) {
                    $select->where('ct.created_at >= DATE_SUB(?, INTERVAL 8 HOUR)', $event_created_from);
                }
                if ($event_created_to !== null) {
                    $select->where('ct.created_at <= DATE_SUB(?, INTERVAL 8 HOUR)', $event_created_to);
                }
            } else { // type is 'ticket' or null
                $select = $connection->select()
                    ->from(
                        ['ecpay_item' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
                        [
                            'created_at' => new Expression("CONVERT_TZ(so.created_at, '+00:00', '+08:00')"),
                            'ecpay_created_at' => 'ecpay_main.created_at',
                            'shop_title' => 'mu.shop_title',
                            'hotai_checkout_number' => 'so.hotai_checkout_number',
                            'increment_id' => 'so.increment_id',
                            'order_item_name' => 'ecpay_item.order_item_name',
                            'qty' => 'ecpay_item.qty',
                            'type' => 'ct.ticket_table_name',
                            'special_price' => 'soi.special_price',
                            'ticket_status' => new Expression("CASE
                                WHEN ct.status = " . TicketStatus::STATUS_OVER_DUE . " THEN '已過期'
                                WHEN ct.status = " . TicketStatus::STATUS_RETURNED . " THEN '已退貨（已取消）'
                                WHEN ct.status = " . TicketStatus::STATUS_ERROR . " THEN '異常'
                                WHEN ct.status = " . TicketStatus::STATUS_IMPORTED . " THEN '初始匯入'
                                WHEN ct.status = " . TicketStatus::STATUS_ALLOCATED . " THEN '預分配（已指定給quote_item）'
                                WHEN ct.status = " . TicketStatus::STATUS_UNUSED . " THEN '未核銷；已賣出'
                                WHEN ct.status = " . TicketStatus::STATUS_USED . " THEN '已使用'
                                END"),
                            'serial_number' => 'ct.ticket_unique_content',
                            'redeemed_at' => 'ct.redeemed_at',
                            'transaction_no' => new Expression("CASE
                                WHEN ct.status = 3 AND ct.ticket_table_name = 'edenred_ticket_record' THEN COALESCE(edenred.used_transaction_no)
                                WHEN ct.status = 3 AND ct.ticket_table_name = 'qware_ticket_record' THEN COALESCE(qware.used_transaction_no)
                                WHEN ct.status = 3 AND ct.ticket_table_name = 'family_bonus_pin_ticket_record_v2' THEN COALESCE(fami.used_transaction_no)
                                WHEN ct.status = 3 AND ct.ticket_table_name = 'yoxi_ticket_record_v2' THEN COALESCE(yoxi.used_transaction_no)
                                WHEN ct.status = 3 AND ct.ticket_table_name = 'general_notify_ticket_record' THEN COALESCE(g_notify.used_transaction_no)
                                WHEN ct.status = 3 AND ct.ticket_table_name = 'general_non_notify_ticket_record' THEN COALESCE(gn_notify.used_transaction_no)
                                WHEN ct.status = 3 AND ct.ticket_table_name = 'openhub_ticket_record' THEN COALESCE(openhub.transaction_no)
                                ELSE '-'
                                END"),
                            'use_end_time' => 'ct.use_end_time'
                        ]
                    )
                    ->joinLeft(
                        ['ecpay_main' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
                        'ecpay_item.hotai_order_invoice_log_id = ecpay_main.hotai_order_invoice_logs_id',
                        []
                    )
                    ->joinLeft(
                        ['soi' => $this->resourceConnection->getTableName('sales_order_item')],
                        'ecpay_item.order_item_id = soi.item_id',
                        []
                    )
                    ->joinLeft(
                        ['mu' => $this->resourceConnection->getTableName('marketplace_userdata')],
                        'mu.seller_code = soi.seller_code',
                        []
                    )
                    ->joinLeft(
                        ['so' => $this->resourceConnection->getTableName('sales_order')],
                        'soi.order_id = so.entity_id',
                        []
                    )
                    ->joinLeft(
                        ['ct' => $this->resourceConnection->getTableName('customer_ticket')],
                        'soi.item_id = ct.sales_order_item_id',
                        []
                    )
                    ->joinLeft(
                        ['edenred' => $this->resourceConnection->getTableName('edenred_ticket_record')],
                        'edenred.record_id = ct.ticket_table_record_id AND edenred.edenred_voucher_no = ct.ticket_unique_content',
                        []
                    )
                    ->joinLeft(
                        ['qware' => $this->resourceConnection->getTableName('qware_ticket_record')],
                        'qware.record_id = ct.ticket_table_record_id AND qware.qware_sn = ct.ticket_unique_content',
                        []
                    )
                    ->joinLeft(
                        ['fami' => $this->resourceConnection->getTableName('family_bonus_pin_ticket_record_v2')],
                        'fami.record_id = ct.ticket_table_record_id AND fami.serial_number = ct.ticket_unique_content',
                        []
                    )
                    ->joinLeft(
                        ['yoxi' => $this->resourceConnection->getTableName('yoxi_ticket_record_v2')],
                        'yoxi.record_id = ct.ticket_table_record_id AND yoxi.serial_number = ct.ticket_unique_content',
                        []
                    )
                    ->joinLeft(
                        ['g_notify' => $this->resourceConnection->getTableName('general_notify_ticket_record')],
                        'g_notify.record_id = ct.ticket_table_record_id AND g_notify.serial_number = ct.ticket_unique_content',
                        []
                    )
                    ->joinLeft(
                        ['gn_notify' => $this->resourceConnection->getTableName('general_non_notify_ticket_record')],
                        'gn_notify.record_id = ct.ticket_table_record_id AND gn_notify.serial_number = ct.ticket_unique_content',
                        []
                    )
                    ->joinLeft(
                        ['openhub' => $this->resourceConnection->getTableName('openhub_ticket_record')],
                        'openhub.record_id = ct.ticket_table_record_id AND openhub.serial_number = ct.ticket_unique_content',
                        []
                    )
                    ->where('ecpay_item.type = ?', 'item')
                    ->where('so.increment_id NOT LIKE ?', '%hotai_order_%')
                    ->group('ct.batch_code')
                    ->group('ct.ticket_unique_content')
                    ->order('so.created_at');
                
                if ($order_created_from !== null) {
                    $select->where('so.created_at >= DATE_SUB(?, INTERVAL 8 HOUR)', $order_created_from);
                }
                if ($order_created_to !== null) {
                    $select->where('so.created_at <= DATE_SUB(?, INTERVAL 8 HOUR)', $order_created_to);
                }

                if ($invoice_created_from !== null) {
                    $select->where('ecpay_main.created_at >= ?', $invoice_created_from);
                }
                if ($invoice_created_to !== null) {
                    $select->where('ecpay_main.created_at <= ?', $invoice_created_to);
                }
            }

            if (!empty($shop_title)) {
                $shopTitles = explode(',', $shop_title);
                $shopTitles = array_map('trim', $shopTitles);
                $shopTitles = array_filter($shopTitles);
                if (!empty($shopTitles)) {
                    $select->where('mu.shop_title IN (?)', $shopTitles);
                }
            }
            if (!empty($ticket_status)) {
                // Mapping UI status to DB status if necessary, or directly use if they match
                $ticketStatusesMap = [
                    '已使用' => TicketStatus::STATUS_USED,
                    '已過期' => TicketStatus::STATUS_OVER_DUE,
                    '未使用;已賣出' => TicketStatus::STATUS_UNUSED,
                    '預分配' => TicketStatus::STATUS_ALLOCATED,
                    '初始匯入' => TicketStatus::STATUS_IMPORTED,
                    '已退貨(已取消)' => TicketStatus::STATUS_RETURNED,
                    '異常' => TicketStatus::STATUS_ERROR
                    // Add other mappings as needed
                ];

                $dbTicketStatuses = [];
                $inputTicketStatuses = explode(',', $ticket_status);
                foreach ($inputTicketStatuses as $status) {
                    $status = trim($status);
                    if (isset($ticketStatusesMap[$status])) {
                        $dbTicketStatuses[] = $ticketStatusesMap[$status];
                    } else {
                        // Handle cases where the UI status directly matches the DB status, or log/throw error
                        $dbTicketStatuses[] = $status;
                    }
                }
                $dbTicketStatuses = array_filter($dbTicketStatuses);
                if (!empty($dbTicketStatuses)) {
                    $select->where('ct.status IN (?)', $dbTicketStatuses);
                }
            }
            if (!empty($order_status)) {
                $orderStatuses = explode(',', $order_status);
                $orderStatuses = array_map('trim', $orderStatuses);
                $orderStatuses = array_filter($orderStatuses);
                if (!empty($orderStatuses)) {
                    $select->where('so.status IN (?)', $orderStatuses);
                }
            }
            if (!empty($serial_number)) {
                $select->where('ct.ticket_unique_content = ?', $serial_number);
            }
            if ($use_start_time !== null) {
                $select->where('ct.use_start_time >= ?', $use_start_time);
            }
            if ($use_end_time !== null) {
                $select->where('ct.use_end_time <= ?', $use_end_time);
            }
            if ($redeemed_from !== null) {
                $select->where('ct.redeemed_at >= ?', $redeemed_from);
            }
            if ($redeemed_to !== null) {
                $select->where('ct.redeemed_at <= ?', $redeemed_to);
            }

            // Clone the main select to build the count query
            $countSelect = clone $select;
            // Clear columns, order, and limit to count the distinct groups
            $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
            $countSelect->reset(\Magento\Framework\DB\Select::ORDER);
            $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
            $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);

            // Add back the grouping columns to make the subquery valid for counting distinct groups
            if ($type === 'event') {
                $countSelect->columns(['tet.batch_code', 'tet.serial_number']);
            } else { // type is 'ticket' or null
                $countSelect->columns(['ct.batch_code', 'ct.ticket_unique_content']);
            }

            // For grouped queries, count the number of distinct groups by wrapping in a subquery
            $totalRecords = (int)$connection->fetchOne(
                $connection->select()->from($countSelect, new Expression('COUNT(*)'))
            );

            // For grouped queries, count the number of distinct groups by wrapping in a subquery
            $page_size = (int)($page_size ?? 10);
            $current_page = (int)($current_page ?? 1);

            if ($page_size <= 0) {
                $page_size = 10;
            }
            if ($current_page <= 0) {
                $current_page = 1;
            }

            $select->limitPage($current_page, $page_size);
            $result = $connection->fetchAll($select);
            if (empty($result)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'No ticket data found for the provided date range.', 'total_record' => 0, 'pagination' => $current_page, 'page_size' => $page_size]))
                    ->sendResponse();
            }

            $response = [
                'success' => true,
                'items' => $result,
                'total_record' => $totalRecords,
                'pagination' => $current_page,
                'page_size' => $page_size
            ];

        } catch (\Exception $e) {
            $this->logger->error('List Ticket Error: ' . $e->getMessage());
            $response = ['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage(), 'total_record' => 0, 'pagination' => $current_page, 'page_size' => $page_size];
        }

        return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode($response))
                ->sendResponse();
    }

    /**
     * @param $dir
     * @return bool
     */
    private function deleteDirectory($dir)
    {
        try {
            $directory = $this->fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
            if ($directory->isExist($dir)) {
                return $directory->delete($dir);
            }
        } catch (\Exception $e) {
            $this->logger->error('Delete Directory Error: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function listOrder(
        string $sales_person = null,
        string $seller_code = null,
        string $invoice_created_from = null,
        string $invoice_created_to = null,
        int $page_size = null,
        int $current_page = null
    ) {
        $sales_person = $sales_person ? urldecode($sales_person) : null;
        $seller_code = $seller_code ? urldecode($seller_code) : null;
        $invoice_created_from = $invoice_created_from ? urldecode($invoice_created_from) : null;
        $invoice_created_to = $invoice_created_to ? urldecode($invoice_created_to) : null;

        $connection = $this->resourceConnection->getConnection();
        $totalRecords = 0;

        try {

            $adminUser = $this->authSession->getUser();
            $adminUserId = $adminUser ? $adminUser->getId() : null;
            $roleIds = [];

            if ($adminUserId) {
                $authorizationRoleTableName = $this->resourceConnection->getTableName('authorization_role');
                $roleSelect = $connection->select()
                    ->from(['ar' => $authorizationRoleTableName], ['parent_id'])
                    ->where('ar.user_id = ?', (int)$adminUserId);
                $roleIds = $connection->fetchCol($roleSelect);
            }

            $select = $connection->select()
                ->from(['soi' => $this->resourceConnection->getTableName('sales_order_item')], [])
                ->joinLeft(
                    ['so' => $this->resourceConnection->getTableName('sales_order')],
                    'so.entity_id = soi.order_id',
                    [
                        'increment_id' => 'so.increment_id',
                        'hotai_checkout_number' => 'so.hotai_checkout_number',
                        'order_created_at_tz' => new Expression("CONVERT_TZ(so.created_at, '+00:00', '+08:00')"),
                        'invoice_status' => new Expression("CASE
                            WHEN so.ecpay_invoice_status = 1 THEN '開立'
                            WHEN so.ecpay_invoice_status = 2 THEN '作廢'
                            WHEN so.ecpay_invoice_status = 3 THEN '折讓'
                            WHEN so.ecpay_invoice_status = 4 THEN '不開發票'
                            ELSE '未知'
                        END"),
                    ]
                )
                ->joinLeft(
                    ['mu' => $this->resourceConnection->getTableName('marketplace_userdata')],
                    'mu.seller_code = soi.seller_code',
                    ['seller_code' => 'mu.seller_code', 'shop_title' => 'mu.shop_title']
                )
                ->joinLeft(
                    ['ar' => $this->resourceConnection->getTableName('authorization_role')],
                    'ar.role_id = mu.salesperson',
                    ['role_name' => 'ar.role_name']
                )
                ->joinLeft(
                    ['order_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
                    'order_log.order_id = so.entity_id',
                    ['created_at' => 'order_log.created_at']
                )
                ->joinLeft(
                    ['order_item_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
                    'order_item_log.order_item_id = soi.item_id',
                    ['flow_status' => 'soi.flow_status']
                )
                ->where('so.increment_id NOT LIKE ?', '%hotai_order%')
                ->order('so.created_at DESC');
            if (!empty($sales_person)) {
                $salesPersons = explode(',', $sales_person);
                $salesPersons = array_map('trim', $salesPersons);
                $salesPersons = array_filter($salesPersons);
                if (!empty($salesPersons)) {
                    $select->where('mu.salesperson IN (?)', $salesPersons);
                }
            }

            if (!empty($seller_code) && $seller_code !== 'all') {
                $sellerCodes = explode(',', $seller_code);
                $sellerCodes = array_map('trim', $sellerCodes);
                $sellerCodes = array_filter($sellerCodes);
                if (!empty($sellerCodes)) {
                    $select->where('mu.seller_code IN (?)', $sellerCodes);
                }
            }

            if ($invoice_created_from !== null) {
                // Adjust for timezone difference: UI input is +8, DB is UTC (need to subtract 8 hours from UI input for DB comparison)
                $select->where('order_log.created_at >= ?', $invoice_created_from);
            }
            if ($invoice_created_to !== null) {
                // Adjust for timezone difference: UI input is +8, DB is UTC (need to subtract 8 hours from UI input for DB comparison)
                $select->where('order_log.created_at <= ?', $invoice_created_to);
            }

            // 判斷是否具備全覽權限 (1: Admin, 232: 財務)
            $isFullAccess = $this->aclHelper->isFullAccess($adminUserId);
            if (!$isFullAccess) {
                if (!empty($roleIds)) {
                    $select->where('mu.salesperson IN (?)', $roleIds);
                } else {
                    $select->where('1=0');
                }
            }

            $selectBeforeGroup = clone $select; 
            $select->group('so.entity_id'); // Group by order entity ID to avoid duplicate orders

            $countSelect = clone $selectBeforeGroup;
            $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
            $countSelect->columns(new Expression('COUNT(DISTINCT soi.order_id)')); // Count distinct orders
            $totalRecords = (int)$connection->fetchOne($countSelect);

            $page_size = (int)($page_size ?? 10);
            $current_page = (int)($current_page ?? 1);

            if ($page_size <= 0) {
                $page_size = 10;
            }
            if ($current_page <= 0) {
                $current_page = 1;
            }

            $select->limitPage($current_page, $page_size);
            $result = $connection->fetchAll($select);

            $response = [
                'success' => true,
                'items' => $result,
                'total_record' => $totalRecords,
                'pagination' => $current_page,
                'page_size' => $page_size
            ];

        } catch (\Exception $e) {
            $this->logger->error('List Order Error: ' . $e->getMessage());
            $response = ['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage(), 'total_record' => 0, 'pagination' => $current_page, 'page_size' => $page_size];
        }

        return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode($response))
                ->sendResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function exportOrder(
        string $invoice_created_from = null,
        string $invoice_created_to = null,
        string $seller_code = null
    ) {
        $invoice_created_from = $invoice_created_from ? urldecode($invoice_created_from) : null;
        $invoice_created_to = $invoice_created_to ? urldecode($invoice_created_to) : null;
        $seller_code = $seller_code ? urldecode($seller_code) : null;

        if (is_null($invoice_created_from) || is_null($invoice_created_to)) {
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Please set invoice_created_from and invoice_created_to']))
                ->sendResponse();
        }

        try {
            $adminName = '';
            if ($this->authSession->isLoggedIn()) {
                $adminName = $this->authSession->getUser()->getUserName();
            }

            $messageData = [
                'admin_name' => $adminName,
                'seller_code' => $seller_code,
                'invoice_created_from' => $invoice_created_from,
                'invoice_created_to' => $invoice_created_to
            ];

            $this->publisher->publish(
                self::TOPIC_ORDER_EXPORT,
                $this->jsonSerializer->serialize($messageData)
            );

            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => true, 'message' => '批次訂單結帳匯出 Jobs enqueued successfully.']))
                ->sendResponse();

        } catch (\Exception $e) {
            $this->logger->error('Enqueue ExportOrder Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Enqueue failed: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * @param $fromdate
     * @param $todate
     * @param $limit
     * @param $offset
     * @param array $sellerCodes
     * @return array
     */
    private function getOrdersWithSellerFilter($fromdate, $todate, $limit, $offset, $sellerCodes = [])
    {
        $connection = $this->resourceConnection->getConnection();
        $columns = [
            'order_id' => new \Zend_Db_Expr('DISTINCT (main_table.order_id)'),
        ];
        
        $select = $connection->select()->from(
            ['main_table' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
            $columns
        )->join(
            ['item' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
            'main_table.hotai_order_invoice_logs_id = item.hotai_order_invoice_log_id',
            []
        )->joinLeft(
            ['soi' => $this->resourceConnection->getTableName('sales_order_item')],
            'item.order_item_id = soi.item_id',
            []
        )->where('main_table.created_at >= ?', $fromdate)
        ->where('main_table.created_at <= ?', $todate)
        ->where('item.export_report = ? ', 1);

        if (!empty($sellerCodes)) {
            $select->where('soi.seller_code IN (?)', $sellerCodes);
        }

        $select->order('main_table.order_id');
        $select->limit($limit, $offset);
        
        return $connection->fetchCol($select);
    }

    /**
     * @param $logItems
     * @param $allRecords
     * @return array
     */
    private function buildExcelRecordsForExport($logItems, $allRecords)
    {
        $needToExcelRecord = [];

        foreach ($logItems as $logItem) {
            $orderId = $logItem['order_id'];
            $orderItems = [];
            $lastItem = null;
            if (isset($allRecords[$orderId])) {
                $orderItems = $allRecords[$orderId];
                $lastItem = end($orderItems);
            }
            if ($logItem['item_type'] === 'item') {
                if (isset($allRecords[$orderId][$logItem['invoice_order_item_id']])) {
                    $itemRecord = array_merge($allRecords[$orderId][$logItem['invoice_order_item_id']], $logItem);
                    $needToExcelRecord[] = $itemRecord;
                }
            } elseif ($lastItem) {
                // clone last row of order then assign value it
                $newRecord = $lastItem;
                $itemRecord = array_merge($newRecord, $logItem);
                $needToExcelRecord[] = $itemRecord;
            }
        }
        return $needToExcelRecord;
    }

    /**
     * @param $storeID
     * @param $fromdate
     * @param $todate
     * @param array $orderIds
     * @return array
     */
    private function getLogItemsForExport($storeID, $fromdate, $todate, $orderIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $columns = $this->getColumnsForExport($storeID);
        $select = $this->getSelectForExport($fromdate, $todate, $columns);
        $select->where('main_table.order_id IN (?)', $orderIds);
        $logItems = $connection->fetchAll($select);
        return $logItems ?: [];
    }

    /**
     * @param $storeID
     * @return array
     */
    private function getColumnsForExport($storeID)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $timezone = $objectManager->get(\Magento\Framework\Stdlib\DateTime\Timezone::class);
        $getTZOffsetTransitions = $objectManager->get(\Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions::class);

        $timezoneConfig = $timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeID);
        $dateAdd = $getTZOffsetTransitions->get($timezoneConfig);

        $columns = [
            'invoice_log_id' => 'main_table.hotai_order_invoice_logs_id',
            'invoice_number' => new \Zend_Db_Expr('COALESCE(main_table.invoice_number, \'no_value\')'),
            'order_id' => 'main_table.order_id',
            'invoice_status' => 'main_table.status',
            'invoice_order_item_id' => 'item.order_item_id',
            'invoice_order_item_name' => 'item.order_item_name',
            'item_include_tax' => 'item.include_tax',
            'price_incl_tax' => 'item.include_tax',
            'qty' => 'item.qty',
            'shipping_price_incl_tax' => 'item.include_tax',
            'item_type' => 'item.type',
            'invoice_order_item_price' => 'item.include_tax',
            'product_name' => 'item.order_item_name',
            'is_reverse' => 'main_table.is_reverse',
            'db_invoice_created_date' => new \Zend_Db_Expr('DATE_FORMAT(main_table.created_at, "%Y-%m-%d")'),
            'ecpay_log_hotai_checkout_number' => 'main_table.hotai_checkout_number'
        ];
        if ($dateAdd) {
            // add offset follow locale store
            $offsetDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'main_table.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $offsetItemDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'item.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $columns['order_checkout_serial_number_date'] = $offsetDate;
            $columns['order_item_checkout_serial_number_date'] = $offsetItemDate;

        } else {
            $columns['order_checkout_serial_number_date'] = 'main_table.created_at';
            $columns['order_item_checkout_serial_number_date'] = 'item.created_at';
        }
        return $columns;
    }

    /**
     * @param $fromdate
     * @param $todate
     * @param $columns
     * @param $limit
     * @param $offset
     * @param $orderBy
     * @return \Magento\Framework\DB\Select
     */
    private function getSelectForExport($fromdate, $todate, $columns = [], $limit = null, $offset = null, $orderBy = null)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
            $columns
        )->join(
            ['item' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
            'main_table.hotai_order_invoice_logs_id = item.hotai_order_invoice_log_id',
            []
        )->where('main_table.created_at >= ?', $fromdate)
            ->where('main_table.created_at <= ?', $todate)->where('item.export_report = ? ', 1);
        if ($orderBy) {
            $select->order($orderBy);
        }
        if ($limit) {
            $select->limit($limit, $offset);
        }
        return $select;
    }

    /**
     * {@inheritdoc}
     */
    public function listExceptionAuth(
        string $batch_num = null,
        string $from_date = null,
        string $to_date = null,
        string $sales_person = null,
        string $seller_code = null,
        string $exception_status = null,
        int $page_size = null,
        int $current_page = null
    )
    {
        $connection = $this->resourceConnection->getConnection();
        $financialReconciliationExceptionTableName = $this->resourceConnection->getTableName('financial_reconciliation_exception');
        $reconciliationTableName = $this->resourceConnection->getTableName('financial_reconciliation');
        $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');
        $authorizationRoleTableName = $this->resourceConnection->getTableName('authorization_role');

        try {
            if (empty($page_size)) {
                $page_size = 10;
            }
            if (empty($current_page)) {
                $current_page = 1;
            }

            $select = $connection->select()
                ->from(['fre' => $financialReconciliationExceptionTableName], [
                    'id' => 'fre.id',
                    'created_at' => 'fre.created_at',
                    'updated_at' => 'fre.updated_at',
                    'commission_rate' => 'fre.commission_rate',
                    'agreed_service_fee' => 'fre.agreed_service_fee',
                    'net_sales' => 'fre.net_sales',
                    'purchase_cost' => 'fre.purchase_cost',
                    'marketing_fee' => 'fre.marketing_fee',
                    'logistic_support_fee' => 'fre.logistic_support_fee',
                    'reason' => 'fre.reason',
                    'exception_status' => 'fre.exception_status',
                ])
                ->joinLeft(
                    ['fr' => $reconciliationTableName],
                    'fre.fr_id = fr.id',
                    [
                        'batch_num' => 'fr.batch_num',
                        'seller_code' => 'fr.seller_code',
                        'from' => 'fr.from',
                        'to' => 'fr.to'
                    ]
                )
                ->joinLeft(
                    ['mu' => $marketplaceUserdataTableName],
                    'fr.seller_code = mu.seller_code',
                    ['shop_title' => 'mu.shop_title']
                )
                ->joinLeft(
                    ['ar' => $authorizationRoleTableName],
                    'ar.role_id = mu.salesperson',
                    ['salesperson_role_name' => 'ar.role_name']
                );

            if (!empty($batch_num)) {
                $select->where('fr.batch_num = ?', $batch_num);
            }
            if ($from_date !== null) {
                $select->where('fre.created_at >= ?', $from_date);
            }
            if ($to_date !== null) {
                $select->where('fre.created_at <= ?', $to_date);
            }
            if (!empty($sales_person)) {
                $select->where('ar.role_id IN (?)', $sales_person);
            }
            if (!empty($seller_code)) {
                $sellerCodes = explode(',', $seller_code);
                $sellerCodes = array_map('trim', $sellerCodes);
                $sellerCodes = array_filter($sellerCodes);
                if (!empty($sellerCodes)) {
                    $select->where('fr.seller_code IN (?)', $sellerCodes);
                }
            }
            if (!empty($exception_status)) {
                $exceptionStatuses = explode(',', $exception_status);
                $exceptionStatuses = array_map('trim', $exceptionStatuses);
                $exceptionStatuses = array_filter($exceptionStatuses);
                if (!empty($exceptionStatuses)) {
                    $select->where('fre.exception_status IN (?)', $exceptionStatuses);
                }
            }

            $countSelect = clone $select;
            $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
            $countSelect->columns('COUNT(DISTINCT fre.id)');
            $totalRecords = $connection->fetchOne($countSelect);

            $select->limitPage($current_page, $page_size);
            $result = $connection->fetchAll($select);

            foreach ($result as &$item) {
                $exceptionStatus = (int)$item['exception_status'];
                if (isset(self::EXCEPTION_STATUS[$exceptionStatus])) {
                    $item['exception_status'] = self::EXCEPTION_STATUS[$exceptionStatus];
                } else {
                    $item['exception_status'] = '未知狀態';
                }

                $showValue = [];
                $valueFields = [
                    'commission_rate',
                    'agreed_service_fee',
                    'net_sales',
                    'purchase_cost',
                    'marketing_fee',
                    'logistic_support_fee'
                ];

                $fieldTranslations = [
                    'commission_rate' => '抽成%',
                    'agreed_service_fee' => '約定服務費',
                    'net_sales' => '特約商專櫃銷售淨額',
                    'purchase_cost' => '採購成本',
                    'marketing_fee' => '行銷負擔(廠商)',
                    'logistic_support_fee' => '運費補貼'
                ];

                foreach ($valueFields as $field) {
                    if (isset($item[$field]) && $item[$field] !== null && (float)$item[$field] != 0) {
                        $translatedFieldName = $fieldTranslations[$field] ?? $field;
                        $showValue[$translatedFieldName] = $item[$field];
                    }
                }
                $item['show_value'] = $showValue;

                // Remove original fields if they are not needed in the final output
                foreach ($valueFields as $field) {
                    unset($item[$field]);
                }
            }

            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => true, 'items' => $result, 'total_record' => (int)$totalRecords, 'pagination' => $current_page, 'page_size' => $page_size]))
                ->sendResponse();

        } catch (\Exception $e) {
            $this->logger->error('List Exception Auth Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage(), 'total_record' => 0, 'pagination' => $current_page, 'page_size' => $page_size]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exceptionAuth(int $id, string $file, string $reason)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('financial_reconciliation');

        try {
            // Get seller_code from financial_reconciliation for the given id
            $selectSellerCode = $connection->select()
                ->from($tableName, ['seller_code'])
                ->where('id = ?', $id);
            $frSellerCode = $connection->fetchOne($selectSellerCode);

            if ($frSellerCode === false) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Seller code not found for reconciliation ID ' . $id . '.']))
                    ->sendResponse();
            }

            // Handle XLSX file content from $file parameter (base64 encoded)
            $decodedFileContent = base64_decode($file);
            if ($decodedFileContent === false) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid base64 encoded file content.']))
                    ->sendResponse();
            }

            $tempDir = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::TMP)->getAbsolutePath('financial_reconciliation_exceptions');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $tempFileName = uniqid('exception_auth_') . '.xlsx';
            $filePath = $tempDir . DIRECTORY_SEPARATOR . $tempFileName;

            file_put_contents($filePath, $decodedFileContent); // Write the decoded content to a temporary file

            $connection->beginTransaction();
            $exceptionTableName = $this->resourceConnection->getTableName('financial_reconciliation_exception');

            try {
                // Validate XLSX file content and process
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();

                if ($highestRow < 2) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Uploaded XLSX file is empty or contains only headers.')
                    );
                }

                $orderTableName = $this->resourceConnection->getTableName('sales_order');
                $orderItemTableName = $this->resourceConnection->getTableName('sales_order_item');

                $dataToInsert = [];
                for ($row = 2; $row <= $highestRow; $row++) { // Assuming first row is header
                    $incrementId = trim((string)$sheet->getCell('A' . $row)->getFormattedValue());
                    $productSKU = trim((string)$sheet->getCell('B' . $row)->getFormattedValue());
                    $vendorSKU = trim((string)$sheet->getCell('C' . $row)->getFormattedValue());
                    
                    // Skip empty rows
                    if (empty($incrementId) || empty($productSKU) || empty($vendorSKU)) {
                        continue;
                    }

                    $commissionRate = $sheet->getCell('E' . $row)->getCalculatedValue();
                    $agreedServiceFee = $sheet->getCell('F' . $row)->getCalculatedValue();
                    $netSales = $sheet->getCell('G' . $row)->getCalculatedValue();
                    $purchaseCost = $sheet->getCell('H' . $row)->getCalculatedValue();
                    $marketingFee = $sheet->getCell('I' . $row)->getCalculatedValue();
                    $logisticSupportFee = $sheet->getCell('J' . $row)->getCalculatedValue();

                    if (empty($incrementId) || empty($productSKU) ) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('Missing data in row %1. Order Increment ID, Product SKU are required.', $row)
                        );
                    }

                    // 使用單一 JOIN 查詢，完全符合您提供的 SQL 結構
                    $vendorSKUWildcard = '%' . $vendorSKU . '%';
                    $selectOrderItem = $connection->select()
                        ->from(['soi' => $orderItemTableName], ['item_id', 'order_id', 'seller_code'])
                        ->join(['so' => $orderTableName], 'soi.order_id = so.entity_id', [])
                        ->where('so.increment_id = ?', (string)$incrementId)
                        ->where('soi.sku = ?', (string)$productSKU)
                        ->where(
                            $connection->quoteInto('soi.sku LIKE ?', $vendorSKUWildcard) . ' OR ' .
                            $connection->quoteInto('soi.variation_sku LIKE ?', $vendorSKUWildcard) . ' OR ' .
                            $connection->quoteInto('soi.option_sku LIKE ?', $vendorSKUWildcard) . ' OR ' .
                            $connection->quoteInto('soi.origin_sku LIKE ?', $vendorSKUWildcard)
                        );

                    // Log Excel values and generated SQL for debugging
                    $this->logger->info(sprintf(
                        "ExceptionAuth Debug - Row %d: IncrementID: %s, ProductSKU: %s, VendorSKU: %s",
                        $row, $incrementId, $productSKU, $vendorSKU
                    ));
                    $this->logger->info("ExceptionAuth SQL: " . $selectOrderItem->__toString());

                    $orderItemData = $connection->fetchRow($selectOrderItem);

                    if (!$orderItemData) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('sales_order_item not found, Product SKU "%1" (Vendor SKU: "%2") Order Increment ID "%3" in row %4. Please check SKU, variation SKU, option SKU, origin SKU, or SKU.', $productSKU, $vendorSKU, $incrementId, $row)
                        );
                    }

                    $orderEntityId = (int)$orderItemData['order_id'];
                    $orderItemId = (int)$orderItemData['item_id'];
                    $orderItemSellerCode = $orderItemData['seller_code'];

                    if ($orderItemSellerCode === false || $orderItemSellerCode !== $frSellerCode) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('Seller code mismatch for order item ID "%1". Financial reconciliation seller code "%2" does not match order item seller code "%3".', $orderItemId, $frSellerCode, $orderItemSellerCode)
                        );
                    }

                    $dataToInsert[] = [
                        'fr_id' => $id,
                        'order_id' => (int)$orderEntityId,
                        'item_id' => (int)$orderItemId,
                        'commission_rate' => (float)$commissionRate,
                        'agreed_service_fee' => (float)$agreedServiceFee,
                        'net_sales' => (float)$netSales,
                        'purchase_cost' => (float)$purchaseCost,
                        'marketing_fee' => (float)$marketingFee,
                        'logistic_support_fee' => (float)$logisticSupportFee,
                        'exception_status' => 1,
                        'reason' => $reason,
                        'created_at' => (new \DateTime())->setTimezone(new \DateTimeZone('Asia/Taipei'))->format('Y-m-d H:i:s'),
                        'updated_at' => (new \DateTime())->setTimezone(new \DateTimeZone('Asia/Taipei'))->format('Y-m-d H:i:s')
                    ];
                }

                if (!empty($dataToInsert)) {
                    $connection->insertMultiple($exceptionTableName, $dataToInsert);
                }

                $connection->commit();
                unlink($filePath); // Delete the uploaded file after processing

                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => true, 'message' => 'File uploaded, processed, and data inserted successfully.']))
                    ->sendResponse();

            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e; // Re-throw to be caught by the outer catch block
            }

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error('Exception Auth Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => $e->getMessage()]))
                ->sendResponse();
        } catch (\Exception $e) {
            $this->logger->error('Exception Auth Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setExceptionAuthStatus(string $status, array $ids)
    {
        $connection = $this->resourceConnection->getConnection();
        $exceptionTableName = $this->resourceConnection->getTableName('financial_reconciliation_exception');

        try {
            if (empty($ids)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'IDs array cannot be empty.']))
                    ->sendResponse();
            }

            if (!in_array($status, ['approved', 'rejected'])) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid status provided. Must be \'approved\' or \'rejected\'.']))
                    ->sendResponse();
            }

            $newStatus = ($status === 'approved') ? 3 : 2;

            // Validate if IDs exist and have status 1 (待審核)
            $select = $connection->select()
                ->from($exceptionTableName, ['id', 'exception_status'])
                ->where('id IN (?)', $ids);
            $existingExceptions = $connection->fetchAll($select);

            $foundIds = array_column($existingExceptions, 'id');
            $invalidIds = array_diff($ids, $foundIds);

            if (!empty($invalidIds)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid exception IDs provided: ' . implode(', ', $invalidIds) . '.']))
                    ->sendResponse();
            }

            $notPendingIds = [];
            foreach ($existingExceptions as $exception) {
                if ((int)$exception['exception_status'] !== 1) {
                    $notPendingIds[] = $exception['id'];
                }
            }

            if (!empty($notPendingIds)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Cannot update status for exceptions that are not in \'待審核\' status (ID: ' . implode(', ', $notPendingIds) . ').']))
                    ->sendResponse();
            }

            $connection->beginTransaction();

            $bind = [
                'exception_status' => $newStatus,
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ];
            $where = ['id IN (?)' => $ids];

            $updatedRows = $connection->update($exceptionTableName, $bind, $where);

            $connection->commit();

            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => true, 'message' => 'Exception authorization status updated successfully.', 'updated_rows' => $updatedRows]))
                ->sendResponse();

        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->error('Set Exception Auth Status Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exportExceptionAuth(array $ids)
    {
        $connection = $this->resourceConnection->getConnection();
        $financialReconciliationExceptionTableName = $this->resourceConnection->getTableName('financial_reconciliation_exception');
        $reconciliationTableName = $this->resourceConnection->getTableName('financial_reconciliation');
        $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');
        $authorizationRoleTableName = $this->resourceConnection->getTableName('authorization_role');

        try {
            if (empty($ids)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'IDs array cannot be empty.']))
                    ->sendResponse();
            }

            // Validate if IDs exist
            $select = $connection->select()
                ->from($financialReconciliationExceptionTableName, ['id'])
                ->where('id IN (?)', $ids);
            $existingIds = $connection->fetchCol($select);

            $invalidIds = array_diff($ids, $existingIds);
            if (!empty($invalidIds)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'Invalid exception IDs provided: ' . implode(', ', $invalidIds) . '.']))
                    ->sendResponse();
            }

            $select = $connection->select()
                ->from(['fre' => $financialReconciliationExceptionTableName], [
                    'id' => 'fre.id',
                    'fr_id' => 'fre.fr_id',
                    'batch_num' => 'fr.batch_num',
                    'seller_code' => 'fr.seller_code',
                    'exception_status' => 'fre.exception_status',
                    'reason' => 'fre.reason',
                    'commission_rate' => 'fre.commission_rate',
                    'agreed_service_fee' => 'fre.agreed_service_fee',
                    'net_sales' => 'fre.net_sales',
                    'purchase_cost' => 'fre.purchase_cost',
                    'marketing_fee' => 'fre.marketing_fee',
                    'logistic_support_fee' => 'fre.logistic_support_fee',
                    'created_at' => 'fre.created_at',
                    'updated_at' => 'fre.updated_at',
                ])
                ->joinLeft(
                    ['fr' => $reconciliationTableName],
                    'fre.fr_id = fr.id',
                    []
                )
                ->joinLeft(
                    ['mu' => $marketplaceUserdataTableName],
                    'fr.seller_code = mu.seller_code',
                    ['shop_title' => 'mu.shop_title']
                )
                ->joinLeft(
                    ['ar' => $authorizationRoleTableName],
                    'ar.role_id = mu.salesperson',
                    ['salesperson_role_name' => 'ar.role_name']
                )
                ->where('fre.id IN (?)', $ids);

            $exportData = $connection->fetchAll($select);

            if (empty($exportData)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'No data found for the provided IDs.']))
                    ->sendResponse();
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = [
                'ID',
                '對帳單ID',
                '批次號碼',
                '賣家代碼',
                '商店名稱',
                '業務角色',
                '例外狀態',
                '原因',
                '抽成%',
                '約定服務費',
                '特約商專櫃銷售淨額',
                '採購成本',
                '行銷負擔(廠商)',
                '運費補貼',
                '建立時間',
                '更新時間'
            ];
            $sheet->fromArray($headers, NULL, 'A1');

            $rowNum = 2;
            foreach ($exportData as $item) {
                $exceptionStatusLabel = self::EXCEPTION_STATUS[(int)$item['exception_status']] ?? '未知狀態';

                $rowData = [
                    $item['id'],
                    $item['fr_id'],
                    $item['batch_num'],
                    $item['seller_code'],
                    $item['shop_title'],
                    $item['salesperson_role_name'],
                    $exceptionStatusLabel,
                    $item['reason'],
                    $item['commission_rate'],
                    $item['agreed_service_fee'],
                    $item['net_sales'],
                    $item['purchase_cost'],
                    $item['marketing_fee'],
                    $item['logistic_support_fee'],
                    $item['created_at'],
                    $item['updated_at']
                ];
                $sheet->fromArray($rowData, NULL, 'A' . $rowNum++);
            }

            $writer = new Xlsx($spreadsheet);
            $fileName = 'exception_auth_export_' . time() . '.xlsx';
            $filePathToDownload = $this->fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::TMP)->getAbsolutePath($fileName);

            $writer->save($filePathToDownload);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');

            ob_clean();
            flush();

            readfile($filePathToDownload);

            unlink($filePathToDownload);
            exit;

        } catch (\Exception $e) {
            $this->logger->error('Export Exception Auth Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exportEcpaySellerRevenueJobs(array $ids)
    {
        $adminName = '';
        if ($this->authSession->isLoggedIn()) {
            $adminName = $this->authSession->getUser()->getUserName();
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('financial_reconciliation');

        try {
            $select = $connection->select()
                ->from($tableName, ['id', 'seller_code', 'from', 'to'])
                ->where('id IN (?)', $ids);
            
            $records = $connection->fetchAll($select);

            if (empty($records)) {
                return $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['success' => false, 'message' => 'No reconciliation records found for the provided IDs.']))
                    ->sendResponse();
            }

            $messageData = [];
            foreach ($records as $record) {
                $messageData[] = [
                    'admin_name' => $adminName,
                    'reconciliation_id' => $record['id'],
                    'seller_code' => $record['seller_code'],
                    'from' => $record['from'],
                    'to' => $record['to']
                ];
            }
            $this->publisher->publish(
                self::TOPIC_RECONCILIATION_EXPORT,
                $this->jsonSerializer->serialize($messageData)
            );

            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => true, 'message' => '批次廠商月結對帳單 - Jobs enqueued successfully.']))
                ->sendResponse();
        } catch (\Exception $e) {
            $this->logger->error('Enqueue Error: ' . $e->getMessage());
            return $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'Enqueue failed: ' . $e->getMessage()]))
                ->sendResponse();
        }
    }
}

