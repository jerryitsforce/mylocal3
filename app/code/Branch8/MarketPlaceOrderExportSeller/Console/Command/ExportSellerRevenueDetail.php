<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportSeller\Console\Command;

use Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions;
use Branch8\MarketPlaceOrderExportSeller\Model\Actions\MonthlyDetailReport;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\CommissionAmount;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\CommissionRate;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\DetailNetSale;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Detail\NetTotal;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\DetailPlatformShare;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProductAttributesCleanUp
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportSellerRevenueDetail extends \Symfony\Component\Console\Command\Command
{
    const  FROM_DATE = 'from_date';
    const  TO_DATE = 'to_date';

    const  SELLER_CODE = 'seller_code';
    const HALF_WIDTH = 0.5;
    const FULL_WIDTH = 1.0;
    /**
     * @var \Magento\Framework\App\State
     */
    private \Magento\Framework\App\State $appState;

    private LoggerInterface $logger;


    private StoreManager $storeManager;
    /**
     * @var Emulation|mixed
     */
    private mixed $emulation;

    private MonthlyDetailReport $monthlyDetailReport;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param StoreManager $storeManager
     * @param LoggerInterface $logger
     * @param MonthlyDetailReport $monthlyDetailReport
     * @param Emulation|null $emulation
     */
    public function __construct(
        \Magento\Framework\App\State                                          $appState,
        \Magento\Framework\Stdlib\DateTime\Timezone                           $timezone,
        StoreManager                                                          $storeManager,
        LoggerInterface                                                       $logger,
        MonthlyDetailReport                                                   $monthlyDetailReport,
        Emulation                                                             $emulation = null,
    )
    {
        $this->storeManager = $storeManager;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
        $this->monthlyDetailReport = $monthlyDetailReport;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('export:ecpay-seller-revenue-detail');
        $this->setDescription('build seller revenue detail
         ex: php bin/magento export:ecpay-seller-revenue-detail --from_date=\'2024-08-05 00:00:00\' --to_date=\'2032-01-01 23:59:00\' --seller_code=\'all\'');
        $this->addOption(
            self::FROM_DATE,
            null,
            InputOption::VALUE_REQUIRED,
            'input from_date'
        );
        $this->addOption(
            self::TO_DATE,
            null,
            InputOption::VALUE_REQUIRED,
            'input to_date'
        );
        $this->addOption(
            self::SELLER_CODE,
            null,
            InputOption::VALUE_REQUIRED,
            'seller_code'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);
        $fromDate = $input->getOption(self::FROM_DATE);
        $toDate = $input->getOption(self::TO_DATE);
        $sellerCode = $input->getOption(self::SELLER_CODE);
        if (empty($sellerCode)) {
            $sellerCode = 'all';
        }

        if (is_null($fromDate) || is_null($toDate)) {
            throw new \Exception('Please set From Date and To Date');
        }
        $storeID = $this->storeManager->getStore()->getId();
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Exception $e) {
        }
        $zipFile = $this->monthlyDetailReport->execute($fromDate, $toDate, $sellerCode);
        echo $zipFile . PHP_EOL;
        return Command::SUCCESS;
    }
}

