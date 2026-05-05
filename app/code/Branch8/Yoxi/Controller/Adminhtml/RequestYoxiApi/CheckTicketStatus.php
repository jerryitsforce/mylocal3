<?php

namespace Branch8\Yoxi\Controller\Adminhtml\RequestYoxiApi;

use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\Collection as YoxiTicketRecordCollection;
use Branch8\Yoxi\Model\YoxiTicketRecordRepository;
use Branch8\Yoxi\Model\YoxiTicketRecord;
use Magento\Backend\App\Action;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Branch8\Yoxi\Helper\Api as ApiHelper;

class CheckTicketStatus extends Action
{
    /** @var YoxiTicketRecordRepository */
    protected $yoxiTicketRecordRepository;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var MessageManagerInterface */
    protected $messageManager;

    public function __construct(
        YoxiTicketRecordRepository $yoxiTicketRecordRepository,
        OrderRepositoryInterface $orderRepository,
        ApiHelper $apiHelper,
        MessageManagerInterface $messageManager,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->yoxiTicketRecordRepository = $yoxiTicketRecordRepository;
        $this->orderRepository            = $orderRepository;
        $this->apiHelper                  = $apiHelper;
        $this->messageManager             = $messageManager;

        return parent::__construct($context);
    }

    public function execute()
    {
        try {
            $orderId     = $this->getRequest()->getParam("order_id");
            $orderItemId = $this->getRequest()->getParam("order_item_id");

            $collection = $this->yoxiTicketRecordRepository->getRecordsByOrderItemId($orderItemId);

            if (count($collection->getItems()) == 0) {
                $this->messageManager->addErrorMessage(__("Can't find any YOXI records by giving order item id: {$orderItemId}"));
                return $this->redirectToOrderDetailPage($orderId);
            }

            $serialNumberArray = [];
            /** @var YoxiTicketRecord $record */
            foreach ($collection->getItems() as $record) {
                $serialNumberArray[] = $record->getSerialNumber();
            }

            $apiResponse       = $this->apiHelper->requestApiGetCouponList($serialNumberArray);
            $serialNumberArray = $this->apiHelper->getDecryptContentFromResponse($apiResponse);

            $comment = $this->formatYoxiApiDataToCommentString($serialNumberArray);

            $this->writeOrderComment($orderId, $comment);

            return $this->redirectToOrderDetailPage($orderId);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->messageManager->addErrorMessage(__("Something went wrong while executing CheckTicketStatus controller, exception message: {$message}"));
            return $this->redirectToOrderDetailPage($orderId);
        }
    }

    /**
     * 將YOXI API票券狀態查詢結果整理成order comment字串
     * @param array $serialNumberArray
     * @return string
     */
    private function formatYoxiApiDataToCommentString(array $serialNumberArray): string
    {
        $comment = "";

        foreach ($serialNumberArray["CouponList"] as $serialumberData) {
            $serialCode      = $serialumberData["SerialCode"];
            $totalCount      = $serialumberData["TotalCount"];
            $usedCount       = $serialumberData["UsedCount"];
            $invalidateCount = $serialumberData["InvalidateCount"];

            $comment .= "序號: {$serialCode}, 總張數: {$totalCount}, 已使用張數: {$usedCount}, 已註銷張數: {$invalidateCount}";
            $comment .= "<br><br>";
        }

        $comment .= "解密後回傳資料: " . json_encode($serialNumberArray, JSON_UNESCAPED_UNICODE);

        return $comment;
    }

    /**
     * 將comment寫入order
     *
     * @param string $comment
     * @return void
     */
    private function writeOrderComment(int $orderId, string $comment): void
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);
        $order->addCommentToStatusHistory($comment);
        $this->orderRepository->save($order);
    }

    /**
     * 將網址導回訂單檢視頁面
     *
     * @param integer $orderId
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirectToOrderDetailPage(int $orderId): \Magento\Framework\Controller\Result\Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ["order_id" => $orderId]);
        return $resultRedirect;
    }
}
