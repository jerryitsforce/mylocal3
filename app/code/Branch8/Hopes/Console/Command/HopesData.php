<?php
namespace Branch8\Hopes\Console\Command;

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
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Branch8\Hopes\Cron\SendRmaToHopes;

class HopesData extends Command
{

    /** @var \Magento\Framework\App\State $state */
    private $state;

    /** @var \Branch8\Hopes\Cron\SendRmaToHopes $sendRmaToHopes */
    private $sendRmaToHopes;


    public function __construct(
        SendRmaToHopes $sendRmaToHopes,
        State $state
    ) {
        $this->state = $state;
        $this->sendRmaToHopes = $sendRmaToHopes;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                'data',
                null,
                InputOption::VALUE_REQUIRED,
                'data'
            ),
            new InputOption(
                'order_id',
                null,
                InputOption::VALUE_OPTIONAL,
                'sales order entity id'
            ),
            new InputOption(
                'rma_id',
                null,
                InputOption::VALUE_OPTIONAL,
                'marketplace rma details id'
            ),
        ];

        $this->setName("hopes:data")
            ->setDescription("Hopes Data Console")
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
        $data = $input->getOption('data');
        $this->state->setAreaCode(Area::AREA_CRONTAB);

        switch ($data) {
            case 'rma':
                $this->sendRmaToHopes->execute();
                break;
            default:
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
