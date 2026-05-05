<?php
namespace Branch8\Sales\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Branch8\Sales\Cron\UpdateStatusToArrived;
use \Branch8\Sales\Cron\UpdateStatusToComplete;
use \Branch8\Sales\Cron\UpdateStatusToTallying;
use \Branch8\Sales\Cron\UpdateTicketRmaStatus;
use \Branch8\Sales\Cron\UpdateParentAndSubOrderStatus;
use \Branch8\Sales\Cron\UpdateStatusToApplyingReturnReview;
use \Branch8\Sales\Cron\UpdateStatusToApplyingReplaceReview;
use Branch8\Sales\Cron\UpdateStatusToReplaceShippingArrived;
use Branch8\Sales\Cron\UpdateStatusToReplaced;
use Branch8\Sales\Cron\UpdateStatusToPicked;
use Branch8\Sales\Cron\UpdateSubOrderGiftConfirmed;
use Branch8\Sales\Cron\UpdateGiftOrderStatusToProcessing;

use Branch8\Sales\Cron\PaymentCheck;
use Branch8\Sales\Cron\UpdateFlagshipFakeOrderStatus;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;

class CronOrderStatusUpdate extends Command
{

    /** @var \Branch8\Sales\Cron\UpdateStatusToComplete $completeCron */
    protected $completeCron;
    
    /** @var \Branch8\Sales\Cron\UpdateStatusToArrived $arrivedCron */
    protected $arrivedCron;
    
    /** @var \Branch8\Sales\Cron\UpdateStatusToTallying $tallyingCron */
    protected $tallyingCron;
    
    /** @var \Branch8\Sales\Cron\UpdateTicketRmaStatus $updateRmaStatus */
    protected $updateTicketRmaStatus;
    
    /** @var \Branch8\Sales\Cron\UpdateParentAndSubOrderStatus $updateParentAndSubOrderStatus */
    protected $updateParentAndSubOrderStatus;

    /** @var \Branch8\Sales\Cron\UpdateStatusToApplyingReturnReview $updateStatusToApplyingReturnReview */
    protected $updateStatusToApplyingReturnReview;

    /** @var \Branch8\Sales\Cron\UpdateStatusToApplyingReplaceReview $updateStatusToApplyingReplaceReview */
    protected $updateStatusToApplyingReplaceReview;

    /**　@var \Branch8\Sales\Cron\UpdateStatusToReplaceShippingArrived $updateStatusToReplaceShippingArrived */
    protected $updateStatusToReplaceShippingArrived;

    /** @var \Branch8\Sales\Cron\UpdateStatusToReplaced $updateStatusToReplaced */
    protected $updateStatusToReplaced;

    /** @var \Magento\Framework\App\State $state */
    private $state;

    protected $updateStatusToPicked;

    /** @var \Branch8\Sales\Cron\UpdateSubOrderGiftConfirmed $updateSubOrderGiftConfirmed */
    protected $updateSubOrderGiftConfirmed;

    /** @var \Branch8\Sales\Cron\UpdateGiftOrderStatusToProcessing $updateGiftOrderStatusToProcessing */
    protected $updateGiftOrderStatusToProcessing;

    /** @var \Branch8\Sales\Cron\PaymentCheck $paymentCheck */
    protected $paymentCheck;

    protected $updateFlagshipFakeOrderStatus;


    public function __construct(
        UpdateStatusToComplete $completeCron,
        UpdateStatusToArrived $arrivedCron,
        UpdateStatusToTallying $tallyingCron,
        UpdateTicketRmaStatus $updateTicketRmaStatus,
        UpdateParentAndSubOrderStatus $updateParentAndSubOrderStatus,
        UpdateStatusToApplyingReturnReview $updateStatusToApplyingReturnReview,
        UpdateStatusToApplyingReplaceReview $updateStatusToApplyingReplaceReview,
        UpdateStatusToReplaceShippingArrived $updateStatusToReplaceShippingArrived,
        UpdateStatusToReplaced $updateStatusToReplaced,
        State $state,
        UpdateStatusToPicked $updateStatusToPicked,
        UpdateSubOrderGiftConfirmed $updateSubOrderGiftConfirmed,
        UpdateGiftOrderStatusToProcessing $updateGiftOrderStatusToProcessing,
        PaymentCheck $paymentCheck,
        UpdateFlagshipFakeOrderStatus $updateFlagshipFakeOrderStatus

    ) {
        $this->state = $state;
        $this->completeCron = $completeCron;
        $this->arrivedCron = $arrivedCron;
        $this->tallyingCron = $tallyingCron;
        $this->updateTicketRmaStatus = $updateTicketRmaStatus;
        $this->updateParentAndSubOrderStatus = $updateParentAndSubOrderStatus;
        $this->updateStatusToApplyingReturnReview = $updateStatusToApplyingReturnReview;
        $this->updateStatusToApplyingReplaceReview = $updateStatusToApplyingReplaceReview;
        $this->updateStatusToReplaceShippingArrived = $updateStatusToReplaceShippingArrived;
        $this->updateStatusToReplaced = $updateStatusToReplaced;
        $this->updateStatusToPicked = $updateStatusToPicked;
        $this->updateSubOrderGiftConfirmed = $updateSubOrderGiftConfirmed;
        $this->updateGiftOrderStatusToProcessing = $updateGiftOrderStatusToProcessing;
        $this->paymentCheck = $paymentCheck;
        $this->updateFlagshipFakeOrderStatus = $updateFlagshipFakeOrderStatus;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                'status',
                null,
                InputOption::VALUE_REQUIRED,
                'status'
            ),
        ];

        $this->setName("sales:updateOrderStatus")
            ->setDescription("Execute Update Order Status Cron")
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $status = $input->getOption('status');
        $this->state->setAreaCode(Area::AREA_CRONTAB);

        switch ($status) {
            case 'tallying':
                $this->tallyingCron->execute();
                break;
            case 'arrived':
                $this->arrivedCron->execute();
                break;
            case 'complete':
                $this->completeCron->execute();
                break;
            case 'ticket':
                $this->updateTicketRmaStatus->execute();
                break;
            case 'all-order':
                $this->updateParentAndSubOrderStatus->execute();
                break;
            case 'applying-return-review':
                $this->updateStatusToApplyingReturnReview->execute();
                break;
            case 'applying-replace-review':
                $this->updateStatusToApplyingReplaceReview->execute();
                break;
            case 'replace-arrived':
                $this->updateStatusToReplaceShippingArrived->execute();
                break;
            case 'replaced':
                $this->updateStatusToReplaced->execute();
                break;
            case 'picked':
                $this->updateStatusToPicked->execute();
                break;
            case 'gift-confirmed':
                $this->updateSubOrderGiftConfirmed->execute();
                break;
            case 'gift-processing':
                $this->updateGiftOrderStatusToProcessing->execute();
                break;
            case 'payment_check':
                $this->paymentCheck->execute();
                break;
            case 'flagship':
                $this->updateFlagshipFakeOrderStatus->execute();
                break;
            default:
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
