<?php
namespace Branch8\Edenred\Console;

use Branch8\Edenred\Helper\Api as ApiHelper;
use Branch8\Edenred\Model\EdenredTicketRecord as EdenredTicketRecordModel;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Magento\Framework\App\State;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CancelVoucher extends Command
{
    const LOG_FOLDER_NAME = 'Edenred/Console/RequestVoucher';

    const OPTION_ORDER_ITEM_ID = 'order_item_id';

    /** @var State */
    protected $state;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var EdenredTicketRecordRepository */
    protected $edenredTicketRecordRepository;

    public function __construct(
        State $state,
        OrderItemRepository $orderItemRepository,
        ApiHelper $apiHelper,
        EdenredTicketRecordRepository $edenredTicketRecordRepository
    ) {
        $this->state                         = $state;
        $this->orderItemRepository           = $orderItemRepository;
        $this->apiHelper                     = $apiHelper;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('branch8:edenred:cancel');
        $this->setDescription('Cancel Edenred ticket data and store into database manually(Branch8\Edenred\Console\CancelVoucher).');

        $this->addOption(
            self::OPTION_ORDER_ITEM_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_item_id like this: --order_item_id=123'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $orderItemId = $input->getOption(self::OPTION_ORDER_ITEM_ID);

        $this->checkOrderItemId($orderItemId);

        try {
            /** @var OrderItem $orderItem */
            $orderItem = $this->orderItemRepository->get($orderItemId);
        } catch (NoSuchEntityException $e) {
            throw new \Exception("Can't find order item by input order_item_id: {$orderItemId}");
        }

        $collection = $this->edenredTicketRecordRepository->getRecordsByOrderItemIdAndStatus(
            $orderItem->getId(),
            EdenredTicketRecordModel::STATUS_IMPORTED
        );

        if (empty($collection->getItems())) {
            $output->writeln("<info>No ticket record with status 'imported' can be canceled with order_item_id: {$orderItemId}</info>");
            return Command::SUCCESS;
        }

        $response = $this->apiHelper->requestApiCancelMultiVouchers($orderItem->getId());

        $this->edenredTicketRecordRepository->setCanceledStatusToEdenredTicketRecordsInDb($collection, $response);

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
}
