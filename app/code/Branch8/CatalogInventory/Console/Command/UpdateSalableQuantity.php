<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\CatalogInventory\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;

class UpdateSalableQuantity extends Command
{
    /** @var \Magento\Framework\App\State $state */
    protected $state;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param Services $services
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        State $state
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName("branch8_cataloginventory:updatesalablequantity");
        $this->addOption(
            'skus',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Skus'
        );
        $this->setDescription(
            'update salable quantity'
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $skus = [];
        $optionskus = (string)$input->getOption('skus');
        if ($optionskus) {
            $skus = explode(',', $optionskus);
            $skus = array_unique($skus);
        }
        $this->updatesalablequantity($skus);
        $output->writeln('Completed!');

        return Cli::RETURN_SUCCESS;
    }

    protected function updatesalablequantity($skus = []){
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('inventory_reservation');
        $select = $connection->select()->from(
            ['ir' => $table],
            ['reservation_id' => 'ir.reservation_id']
        )->group('ir.metadata')->group('ir.sku')->having('count(*) >= 2');
        if(count($skus)){
            $select->where('ir.sku IN (?)', $skus);
        }
        $select->where('ir.metadata LIKE (?)', '%shipment_created%');
        $select->orWhere('ir.metadata LIKE (?)', '%invoice_created%');
        $select->orWhere('ir.metadata LIKE (?)', '%order_canceled%');
        $select->orWhere('ir.metadata LIKE (?)', '%creditmemo_created%');
        $reservation_ids = $connection->fetchCol($select);
        var_dump($reservation_ids);
        if(count($reservation_ids)){
            $connection->delete($table,['reservation_id IN (?)' => $reservation_ids]);
        }
    }
}
