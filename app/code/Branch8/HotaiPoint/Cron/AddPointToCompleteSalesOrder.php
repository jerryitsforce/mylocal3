<?php

namespace Branch8\HotaiPoint\Cron;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class AddPointToCompleteSalesOrder
{
    const DEFAULT_AFTER_MINUTES = 30;
    const LOG_FOLDER_NAME       = 'Cron';
    private const LOG_SUBFOLDER_NAME = 'AddPointToCompleteSalesOrder';
    private const DEBUG_LOG_OPTION = LogOption::LOG_ADD_POINT_TO_COMPLETE_SALES_ORDER;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    protected $logFileName;
    protected $walkthroughLog;

    /** @var OrderRepository */
    protected $orderRepository;

    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        ApiHelper $apiHelper,
        CommonHelper $commonHelper
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository        = $orderRepository;
        $this->apiHelper              = $apiHelper;
        $this->commonHelper           = $commonHelper;

        $this->logFileName    = "add_point_to_complete_sales_order_" . date("Y_m") . ".log";
        $this->walkthroughLog = [];
    }

    public function execute()
    {
        $this->writeLog("AddPointToCompleteSalesOrder cron start.");

        $orderCollection = $this->getOrderCollectionReadyForAddPoint();

        $orders = $orderCollection->getItems();

        if (!$orderCollection->getSize()) {
            $this->writeLog("No order needs to be handled, AddPointToCompleteSalesOrder cron end.");
            return;
        }

        $orderIds = [];
        foreach ($orders as $order) {
            $orderIds[] = $order->getId();
        }
        $this->writeLog("Order counts ready to be handled: " . $orderCollection->getSize());
        $this->writeLog("Order IDs: " . implode(',', $orderIds));

        foreach ($orders as $order) {
            foreach ($order->getAllVisibleItems() as $item) {
                $this->walkthroughLog = [];
                $transSN              = $order->getIncrementId() . "_" . $item->getId() . "_" . time();
                $transTimestamp       = time();
                $transDesc            = "OrderIncrementId: {$order->getIncrementId()}, ItemId: {$item->getId()}";
                $traceNo              = "";

                if ($item->getData('hotai_point_add_point_success')) {
                    continue;
                }

                try {
                    $this->walkthroughLog[] = "Ready to request AddPoint API.";
                    $addPointResponse       = $this->apiHelper->requestApiAddPoint($order->getCustomerId(), $transSN, $transTimestamp, $item->getPrice(), $transDesc);
                    $this->walkthroughLog[] = "AddPoint API done, request data: " . $this->apiHelper->getRequestDataString();
                    $this->walkthroughLog[] = "AddPoint API done, response data: " . json_encode($addPointResponse);

                    $traceNo = $this->apiHelper->getTraceNoFromResponse($addPointResponse);

                    $this->walkthroughLog[] = "Ready to request Commit API.";
                    $commitResponse         = $this->apiHelper->requestApiCommit($order->getCustomerId(), $traceNo);
                    $this->walkthroughLog[] = "Commit API done, request data: " . $this->apiHelper->getRequestDataString();
                    $this->walkthroughLog[] = "Commit API done, response data: " . json_encode($commitResponse);

                    $this->orderItemUpdateIfSuccess($item, $traceNo, $transSN, $transTimestamp);
                } catch (\Exception $e) {
                    $this->walkthroughLog[] = "Exception, last API request data: " . $this->apiHelper->getRequestDataString();
                    $this->writeLog("Exception, order item ID: " . $item->getId() . ", message: " . $e->getMessage());
                    $this->orderItemUpdateIfFail($item, $traceNo, $e->getMessage());
                }
            }

            $this->updateOrderFieldIfAllItemSuccess($order);
        }

        $this->writeLog("AddPointToCompleteSalesOrder cron end.");
    }

    protected function getOrderCollectionReadyForAddPoint()
    {
        $afterMinutes = $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_CRON_CREATED_AFTER_MINUTES_FOR_ADDING_POINT_TO_COMPLETE_SALES_ORDER);
        $afterMinutes = ($afterMinutes) ? $afterMinutes : self::DEFAULT_AFTER_MINUTES;

        $filterDate = date('Y-m-d 00:00:00', strtotime("-" . $afterMinutes . " minutes"));

        $orderCollection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('status', 'complete')
            ->addFieldToFilter('created_at', ['lt' => $filterDate])
            ->addFieldToFilter('hotai_point_add_point_complete', 0);

        return $orderCollection;
    }

    protected function orderItemUpdateIfSuccess($item, $traceNo, $transSN, $transTimestamp, $message = "")
    {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_add_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Add point success.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp($transTimestamp);

        $item->setData('hotai_point_add_point_trace_no', $traceNo);
        $item->setData('hotai_point_add_point_success', 1);
        $item->setData('hotai_point_add_point_trans_s_n', $transSN);
        $item->setData('hotai_point_add_point_trans_datetime', $taiwanDateObj->format("Y-m-d H:i:s"));
        $item->setData('hotai_point_add_point_memo', $memoMessage);
        $item->save();
    }

    protected function orderItemUpdateIfFail($item, $traceNo, $message)
    {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_add_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Add point fail.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $item->setData('hotai_point_add_point_trace_no', $traceNo);
        $item->setData('hotai_point_add_point_success', 0);
        $item->setData('hotai_point_add_point_memo', $memoMessage);
        $item->save();
    }

    protected function updateOrderFieldIfAllItemSuccess($order)
    {
        $allSuccessFlag = true;

        foreach ($order->getAllVisibleItems() as $item) {
            if (!$item->getData('hotai_point_add_point_success')) {
                $allSuccessFlag = false;
            }
        }

        if ($allSuccessFlag) {
            $order->setData('hotai_point_add_point_complete', 1);
            $this->orderRepository->save($order);
        }
    }

    protected function writeLog($message)
    {
        $this->commonHelper->writeLogIfEnabled(
            $message,
            self::LOG_FOLDER_NAME . '/' . self::LOG_SUBFOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }
}
