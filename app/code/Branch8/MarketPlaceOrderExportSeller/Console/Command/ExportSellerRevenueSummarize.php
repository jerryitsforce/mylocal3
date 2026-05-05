<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportSeller\Console\Command;

use Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions;
use Branch8\MarketPlaceOrderExportSeller\Model\Actions\MonthlySummarizeReport;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\CommissionRate;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\NetSale;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\SalesRepresentatives;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Summarize\NetTotal;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Summarize\PlatformShare;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Summarize\VendorShare;
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
class ExportSellerRevenueSummarize extends \Symfony\Component\Console\Command\Command
{
    const  FROM_DATE = 'from_date';
    const  TO_DATE = 'to_date';

    const  SELLER_CODE = 'seller_code';
    /**
     * @var \Magento\Framework\App\State
     */
    private \Magento\Framework\App\State $appState;


    private StoreManager $storeManager;

    /**
     * @var Emulation|mixed
     */
    private mixed $emulation;

    private MonthlySummarizeReport $monthlySummarizeReport;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param StoreManager $storeManager
     * @param LoggerInterface $logger
     * @param MonthlySummarizeReport $monthlySummarizeReport
     * @param Emulation|null $emulation
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        StoreManager                 $storeManager,
        LoggerInterface              $logger,
        MonthlySummarizeReport       $monthlySummarizeReport,
        Emulation                    $emulation = null,
    )
    {

        $this->storeManager = $storeManager;
        $this->appState = $appState;
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
        $this->monthlySummarizeReport = $monthlySummarizeReport;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('export:ecpay-seller-revenue-summarize');
        $this->setDescription('build seller revenue
         ex: php bin/magento export:ecpay-seller-revenue-summarize --from_date=\'2024-08-05 00:00:00\' --to_date=\'2032-01-01 23:59:00\'');
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
            InputOption::VALUE_OPTIONAL,
            'input to_date'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);
        $fromdate = $input->getOption(self::FROM_DATE);
        $todate = $input->getOption(self::TO_DATE);
        $sellerCode = $input->getOption(self::SELLER_CODE);
        if (is_null($fromdate) || is_null($todate)) {
            throw new \Exception('Please set From Date and To Date');
        }
        $storeID = $this->storeManager->getStore()->getId();
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Exception $e) {
        }

        $filePath = $this->monthlySummarizeReport->execute($fromdate, $todate, $sellerCode);
        $this->emulation->stopEnvironmentEmulation();
        echo $filePath . PHP_EOL;
        return Command::SUCCESS;
    }
}
