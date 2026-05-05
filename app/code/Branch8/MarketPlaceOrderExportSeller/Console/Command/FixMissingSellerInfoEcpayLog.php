<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportSeller\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\App\Emulation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixMissingSellerInfoEcpayLog extends \Symfony\Component\Console\Command\Command
{
    const  ORDER_IDS = 'order_ids';
    private \Magento\Framework\App\State $appState;

    private LoggerInterface $logger;
    private ResourceConnection $resourceConnection;
    /**
     * @var Emulation|mixed
     */
    private mixed $emulation;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param Emulation|null $emulation
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        ResourceConnection           $resourceConnection,
        LoggerInterface              $logger,
        Emulation                    $emulation = null,
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('export:ecpay-log-fix-missingseller');
        $this->setDescription('Fix missing seller info');
        $this->addOption(
            self::ORDER_IDS,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_ids'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);
        $orderIds = $input->getOption(self::ORDER_IDS);
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Exception $e) {
        }
        $this->emulation->startEnvironmentEmulation(1,
            Area::AREA_FRONTEND,
            true
        );
        $connection = $this->resourceConnection->getConnection();
        ##############################
        $updateSellerIdSql = "
           UPDATE ecpay_invoice_hotai_order_invoice_logs ecp
           JOIN  marketplace_orders mo ON ecp.order_id = mo.order_id
           SET ecp.seller_id=mo.seller_id
        ";

        if ($orderIds) {
            $updateSellerIdSql .= " WHERE ecp.order_id IN ($orderIds)";
        }
        $connection->query($updateSellerIdSql);

        ##########################
        $updateSellerCodeAndSellerStoreSql = "
           UPDATE ecpay_invoice_hotai_order_invoice_logs ecp
           JOIN  marketplace_userdata mu ON ecp.seller_id = mu.seller_id
           SET ecp.seller_code=mu.seller_code,ecp.seller_company_name=mu.company_name,ecp.seller_shop_name=mu.shop_title
        ";
        if ($orderIds) {
            $updateSellerCodeAndSellerStoreSql .= " WHERE ecp.order_id IN ($orderIds)";
        }

        $connection->query($updateSellerCodeAndSellerStoreSql);
        $this->emulation->stopEnvironmentEmulation();
        return Command::SUCCESS;
    }
}
