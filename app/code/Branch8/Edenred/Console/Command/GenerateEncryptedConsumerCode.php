<?php

declare(strict_types=1);

namespace Branch8\Edenred\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\Edenred\Helper\Common as EdenredCommonHelper;

/**
 * Command to generate encrypted ConsumerCode for Edenred API
 */
class GenerateEncryptedConsumerCode extends Command
{
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
        $this->setName('edenred:generate-encrypted-consumer-code');
        $this->setDescription('Generate encrypted ConsumerCode for Edenred receive-notification API from configuration');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        try {
            // Get ConsumerCode from configuration
            $consumerCode = $this->edenredCommonHelper->getApiConfigByConfigCode(
                \Branch8\Edenred\Helper\Common::CONFIG_CODE_CONSUMER_CODE
            );

            if (empty($consumerCode)) {
                $output->writeln('<error>ConsumerCode is not configured. Please configure it in Magento admin.</error>');
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

            // Encrypt ConsumerCode
            $encryptedConsumerCode = $this->edenredCommonHelper->encryptString($consumerCode);

            // Output results
            $output->writeln('');
            $output->writeln('<info>=== Encrypted ConsumerCode ===</info>');
            $output->writeln($encryptedConsumerCode);
            $output->writeln('');
            $output->writeln('<comment>Original ConsumerCode: ' . $consumerCode . '</comment>');
            $output->writeln('');
            $output->writeln('<info>=== Usage in API Request ===</info>');
            $output->writeln('Add this header to your API request:');
            $output->writeln('  ConsumerCode: ' . $encryptedConsumerCode);
            $output->writeln('');

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}

