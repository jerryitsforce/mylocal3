<?php

namespace Branch8\Edenred\Console;

use Branch8\Edenred\Helper\Api as ApiHelper;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\Order\Item;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\GiftToFriend\Helper\Order as GiftToFriendHelper;

class RequestVoucher extends Command
{
    const LOG_FOLDER_NAME = 'Edenred/Console/RequestVoucher';

    const OPTION_ORDER_ITEM_ID    = 'order_item_id';
    const OPTION_REQUEST_QUANTITY = 'request_quantity';

    /** @var State */
    protected $state;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var EdenredTicketRecordRepository */
    protected $edenredTicketRecordRepository;

    /** @var EventManager */
    protected $eventManager;

    /** @var GiftToFriendHelper */
    protected $giftToFriendHelper;

    public function __construct(
        State $state,
        OrderItemRepository $orderItemRepository,
        ApiHelper $apiHelper,
        EdenredTicketRecordRepository $edenredTicketRecordRepository,
        EventManager $eventManager,
        GiftToFriendHelper $giftToFriendHelper
    ) {
        $this->state                         = $state;
        $this->orderItemRepository           = $orderItemRepository;
        $this->apiHelper                     = $apiHelper;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
        $this->eventManager                  = $eventManager;
        $this->giftToFriendHelper            = $giftToFriendHelper;
        parent::__construct();
    }

    /** @inheritDoc */
    protected function configure()
    {
        $this->setName('branch8:edenred:request');
        $this->setDescription('Request Edenred ticket data and store into database manually(Branch8\Edenred\Console\RequestVoucher).');

        $this->addOption(
            self::OPTION_ORDER_ITEM_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_item_id like this: --order_item_id=123'
        );

        $this->addOption(
            self::OPTION_REQUEST_QUANTITY,
            null,
            InputOption::VALUE_OPTIONAL,
            'input request_quantity like this: --request_quantity=5 (optional, defaults to order item qty_ordered if not provided)'
        );

        parent::configure();
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $orderItemId     = $input->getOption(self::OPTION_ORDER_ITEM_ID);
        $requestQuantity = $input->getOption(self::OPTION_REQUEST_QUANTITY);

        $this->checkOrderItemId($orderItemId);

        $output->writeln("<info>input " . self::OPTION_ORDER_ITEM_ID . ": " . $orderItemId . "</info>");
        $output->writeln("<info>input " . self::OPTION_REQUEST_QUANTITY . ": " . var_export($requestQuantity, true) . "</info>");

        try {
            /** @var Item $orderItem */
            $orderItem = $this->orderItemRepository->get($orderItemId);
        } catch (NoSuchEntityException $e) {
            throw new \Exception("Can't find order item by input order_item_id: {$orderItemId}");
        }

        $requestQuantity = (($requestQuantity) === null) ? $orderItem->getQtyOrdered() : $this->getRequestQuantity($requestQuantity);

        $output->writeln("<info>sales_order_item.qty_ordered: " . $orderItem->getQtyOrdered() . "</info>");
        $output->writeln("<info>final " . self::OPTION_REQUEST_QUANTITY . ": " . $requestQuantity . "</info>");

        $customOwner = $this->giftToFriendHelper->resolveCustomOwnerForGiftOrder($orderItem->getOrderId());

        $apiResponse = $this->apiHelper->requestApiGetMultiVouchers($orderItem->getId(), (int) $requestQuantity);

        $this->edenredTicketRecordRepository->storeVoucherDataInDatabase($apiResponse, $orderItem, $customOwner);

        $this->eventManager->dispatch(
            "ecpay_inovice_ticket_item_arrived_check",
            [
                "orderId" => $orderItem->getOrderId(),
            ]
        );

        $output->writeln("<info>Done</info>");

        return Command::SUCCESS;
    }

    /**
     * 檢查傳入參數--order_item_id
     *
     * @param string|null $orderItemId
     * @return void
     */
    protected function checkOrderItemId(?string $orderItemId): void
    {
        if (empty($orderItemId)) {
            throw new \Exception("Input " . self::OPTION_ORDER_ITEM_ID . " is madatory.");
        }
    }

    /**
     * 檢查並轉換傳入參數--request_quantity
     *
     * @param string $requestQuantity
     * @return int
     */
    protected function getRequestQuantity(string $requestQuantity): int
    {
        if (!ctype_digit($requestQuantity)) {
            throw new \Exception("Input " . self::OPTION_REQUEST_QUANTITY . " must be a positive integer.");
        }

        $quantity = (int) $requestQuantity;
        if ($quantity <= 0) {
            throw new \Exception("Input " . self::OPTION_REQUEST_QUANTITY . " must be greater than 0.");
        }

        return $quantity;
    }
}
