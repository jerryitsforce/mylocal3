<?php

declare(strict_types=1);

namespace Branch8\Edenred\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\Edenred\Helper\Common as EdenredCommonHelper;

/**
 * Command to generate encrypted VoucherNo for Edenred API
 */
class GenerateEncryptedVoucherNo extends Command
{
    const VOUCHER_NO = 'voucher-no';

    /** @var State */
    protected $state;

    /** @var EdenredCommonHelper */
    protected $edenredCommonHelper;

    /**
     * @param State $state
     * @param EdenredCommonHelper $edenredCommonHelper
     */
    public function __construct(
        State $state,
        EdenredCommonHelper $edenredCommonHelper
    ) {
        $this->state = $state;
        $this->edenredCommonHelper = $edenredCommonHelper;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('edenred:generate-encrypted-voucher-no');
        $this->setDescription('Generate encrypted VoucherNo for Edenred receive-notification API');

        $this->addOption(
            self::VOUCHER_NO,
            null,
            InputOption::VALUE_REQUIRED,
            'Voucher number to encrypt'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        try {
            $voucherNo = $input->getOption(self::VOUCHER_NO);
            
            if (empty($voucherNo)) {
                $output->writeln('<error>Voucher number is required. Use --voucher-no="YOUR_VOUCHER_NO"</error>');
                return Cli::RETURN_FAILURE;
            }

            // Validate SSL key and IV configuration
            $sslKey = $this->edenredCommonHelper->getApiConfigByConfigCode(
                \Branch8\Edenred\Helper\Common::CONFIG_CODE_SSL_KEY
            );
            $sslIv = $this->edenredCommonHelper->getApiConfigByConfigCode(
                \Branch8\Edenred\Helper\Common::CONFIG_CODE_SSL_IV
            );

            if (empty($sslKey)) {
                $output->writeln('<error>SSL Key is not configured. Please configure it in Magento admin.</error>');
                return Cli::RETURN_FAILURE;
            }

            if (empty($sslIv)) {
                $output->writeln('<error>SSL IV is not configured. Please configure it in Magento admin.</error>');
                return Cli::RETURN_FAILURE;
            }

            // Encrypt VoucherNo
            $encryptedVoucherNo = $this->edenredCommonHelper->encryptString($voucherNo);

            // Output results
            $output->writeln('');
            $output->writeln('<info>=== Encrypted VoucherNo ===</info>');
            $output->writeln($encryptedVoucherNo);
            $output->writeln('');
            $output->writeln('<comment>Original VoucherNo: ' . $voucherNo . '</comment>');
            $output->writeln('');

            // Show example request
            $output->writeln('<info>=== Example API Request ===</info>');
            $output->writeln('curl -X POST "https://your-domain.com/rest/V1/branch8-edenred/receive-notification" \\');
            $output->writeln('  -H "Content-Type: application/json" \\');
            $output->writeln('  -H "ConsumerCode: {encrypted_consumer_code}" \\');
            $output->writeln('  -d \'{');
            $output->writeln('    "ClientOrderNumber": "ORDER123456",');
            $output->writeln('    "VoucherNo": "' . $encryptedVoucherNo . '",');
            $output->writeln('    "MerchantCode": "MERCHANT001",');
            $output->writeln('    "Status": "001",');
            $output->writeln('    "ActionDate": "2024-01-15 10:30:00"');
            $output->writeln('  }\'');
            $output->writeln('');

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}

