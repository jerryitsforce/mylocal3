<?php

namespace Branch8\Yoxi\Observer;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\Yoxi\Helper\Common as CommonHelper;
use Branch8\Yoxi\Model\YoxiTicketRecordRepository;
use Branch8\Yoxi\Model\Config\Source\LogOption;

class ResetTicketStatusForReturnOrder implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'Yoxi/Observer/ResetTicketStatusForReturnOrder';
    const LOG_OPTION_VALUE = LogOption::LOG_OPTION_VALUE_RESET_TICKET_STATUS_FOR_RETURN_ORDER;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ManagerInterface */
    protected $eventManager;

    protected $walkthroughLog;
    protected $logTitle;

    /** @var OrderItem */
    protected $orderItem;
    protected $quantityOld;
    protected $quantityNew;

    public function __construct(
        CommonHelper $commonHelper,
        ManagerInterface $eventManager
    ) {
        $this->commonHelper          = $commonHelper;
        $this->eventManager          = $eventManager;
    }

    public function execute(Observer $observer)
    {
        $this->writeLog("ModifyPointForReturnOrder observer start.");

        $this->initParameters($observer);

        // 確認是否YOXI票券, 不是就退出
        if (!$this->checkIsYoxiProduct()) {
            $this->writeLog("Not YOXI product, ModifyPointForReturnOrder observer end.");
            return;
        }

        $this->writeLog("ModifyPointForReturnOrder observer end.");
    }

    protected function checkIsYoxiProduct(): bool
    {
        return $this->commonHelper->IsYoxiTicketProduct($this->orderItem->getProductId());
    }

    /**
     * 初始化參數
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    protected function initParameters(Observer $observer)
    {
        $this->orderItem = $observer->getData("orderItem");

        $this->quantityOld = (float) $observer->getData("quantityOld");
        $this->quantityNew = (float) $observer->getData("quantityNew");

        $this->logTitle = "Order item ID: " . $this->orderItem->getId() . ", ";

        $this->writeLog("quantityOld: {$this->quantityOld}, quantityNew: {$this->quantityNew}.");
    }

    protected function checkRemainingQuantityForReturn()
    {
        $returnQuantity = $this->quantityOld - $this->quantityNew;

        if ($returnQuantity <= 0) {
            throw new \Exception("Something went wrong while counting return quantity, return quantity: {$returnQuantity}.");
        }
    }

    /**
     * 寫log
     *
     * @param string $message
     * @return void
     */
    protected function writeLog(string $message): void
    {
        $this->commonHelper->writeLogIfEnabled(
            $this->logTitle . $message,
            self::LOG_OPTION_VALUE,
            self::LOG_FOLDER_NAME
        );
    }
}