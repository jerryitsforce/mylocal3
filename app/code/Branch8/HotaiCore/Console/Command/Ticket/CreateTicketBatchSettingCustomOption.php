<?php

namespace Branch8\HotaiCore\Console\Command\Ticket;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;

class CreateTicketBatchSettingCustomOption extends Command
{
    const LOG_FOLDER_NAME = 'HotaiCore/Console/CreateTicketBatchSettingCustomOption';

    const OPTION_PRODUCT_IDS = 'product_ids';

    /** @var State */
    protected $state;

    /** @var VirtualProductHelper */
    protected $virtualProductHelper;

    protected $productIds;
    protected $productIdArray;

    public function __construct(
        State $state,
        VirtualProductHelper $virtualProductHelper
    ) {
        $this->state                = $state;
        $this->virtualProductHelper = $virtualProductHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_core:create_ticket_batch_setting_custom_option');
        $this->setDescription('Pass in --product_ids=100,101,102, will execute createBatchSettingCustomOptionValueBasedOnCurrentBatchSetting function under app/code/Branch8/HotaiCore/Helper/VirtualProduct.php.');

        $this->addOption(
            self::OPTION_PRODUCT_IDS,
            null,
            InputOption::VALUE_REQUIRED,
            'input product_ids like this: --product_ids=100,101,102'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInput($input);

        foreach($this->productIdArray as $productId) {
            $this->virtualProductHelper->createBatchSettingCustomOptionValueBasedOnCurrentBatchSetting($productId);
        }

        $output->writeln("<info>Done</info>");

        return Command::SUCCESS;
    }

    /**
     * 檢查傳入參數--product_ids
     *
     * @param string|null $orderItemId
     * @return void
     */
    protected function checkInput(InputInterface $input): void
    {
        $this->productIds = $input->getOption(self::OPTION_PRODUCT_IDS);

        if (empty($this->productIds)) {
            throw new \Exception("Input " . self::OPTION_PRODUCT_IDS . " is mandatory.");
        }

        $this->productIdArray = explode(',', $this->productIds);

        foreach ($this->productIdArray as $productId) {
            if (empty($productId) || !is_numeric($productId)) {
                throw new \Exception("Input " . self::OPTION_PRODUCT_IDS . " must be numeric.");
            }
        }
    }
}
