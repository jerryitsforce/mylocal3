<?php

namespace Branch8\HotaiPay\Console\Command;

use Branch8\HotaiPay\Cron\Inquiry;
use Branch8\HotaiPay\Helper\Crypt;
use Branch8\HotaiPay\Logger\Command\Logger;
use Branch8\HotaiPay\Service\HotaiPay;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditMemoItem;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as Status;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;

class Test extends Command
{

    const num = 1;
    private $logger;
    public $scopeConfig;
    protected $crypt;
    protected $hotaiPayService;
    protected $model;
    protected $status;

    /** @var StatusFactory */
    protected $statusFactory;

    /** @var StatusResourceFactory */
    protected $statusResourceFactory;

    protected $creditMemoItem;

    /** @var \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository */
    protected $creditmemoRepository;

    /** @var \Magento\Framework\App\State $state */
    protected $state;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Logger $logger,
        HotaiPay $hotaiPayService,
        Crypt $crypt,
        Status $status,
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory,
        CreditMemoItem $creditMemoItem,
        CreditmemoRepositoryInterface $creditmemoRepository,
        State $state,
    ) {
        $this->logger = $logger;
        $this->hotaiPayService = $hotaiPayService;
        $this->crypt = $crypt;
        $this->status = $status;
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
        $this->creditMemoItem = $creditMemoItem;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->state = $state;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotaipay:test');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->state->setAreaCode(Area::AREA_GLOBAL);

        return self::num;
    }
}
