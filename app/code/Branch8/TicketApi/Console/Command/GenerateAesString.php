<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\TicketApi\Helper\Api;
use Branch8\TicketApi\Model\TicketApiMerchantRepository;

/**
 * Command to generate AES encrypted string for ticket API
 */
class GenerateAesString extends Command
{
    const MERCHANT_ID = 'merchant-id';
    const BRAND = 'brand';
    const SERIAL_NO = 'serial-no';
    const TRANSACTION_NO = 'transaction-no';
    const STORE_NO = 'store-no';
    const UNIQUE_ID = 'unique-id';
    const JSON_FILE = 'json-file';

    /** @var State */
    protected $state;

    /** @var Api */
    protected $apiHelper;

    /** @var TicketApiMerchantRepository */
    protected $merchantRepository;

    /**
     * @param State $state
     * @param Api $apiHelper
     * @param TicketApiMerchantRepository $merchantRepository
     */
    public function __construct(
        State $state,
        Api $apiHelper,
        TicketApiMerchantRepository $merchantRepository
    ) {
        $this->state = $state;
        $this->apiHelper = $apiHelper;
        $this->merchantRepository = $merchantRepository;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('ticket-api:generate-aes-string');
        $this->setDescription('Generate AES encrypted string for ticket API use endpoint');

        $this->addOption(
            self::MERCHANT_ID,
            'm',
            InputOption::VALUE_REQUIRED,
            'Merchant ID'
        );

        $this->addOption(
            self::BRAND,
            'b',
            InputOption::VALUE_REQUIRED,
            'Brand code (yoxi, edenred, family_bonus_pin, general_notify, openhub)'
        );

        $this->addOption(
            self::SERIAL_NO,
            's',
            InputOption::VALUE_REQUIRED,
            'Serial number'
        );

        $this->addOption(
            self::TRANSACTION_NO,
            't',
            InputOption::VALUE_REQUIRED,
            'Used transaction number'
        );

        $this->addOption(
            self::STORE_NO,
            null,
            InputOption::VALUE_OPTIONAL,
            'Used store number (optional)'
        );

        $this->addOption(
            self::UNIQUE_ID,
            'u',
            InputOption::VALUE_OPTIONAL,
            'Unique ID (optional)'
        );

        $this->addOption(
            self::JSON_FILE,
            'j',
            InputOption::VALUE_OPTIONAL,
            'Path to JSON file containing payload data (alternative to individual options)'
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
            // Get merchant ID
            $merchantId = $input->getOption(self::MERCHANT_ID);
            if (empty($merchantId)) {
                $output->writeln('<error>Merchant ID is required</error>');
                return Cli::RETURN_FAILURE;
            }

            // Get merchant
            $merchant = $this->merchantRepository->getSettingByMerchantId(
                $merchantId,
                \Branch8\TicketApi\Model\TicketApiMerchant::IS_ACTIVE_TRUE
            );

            if (!$merchant) {
                $output->writeln("<error>Merchant with ID '{$merchantId}' not found or inactive</error>");
                return Cli::RETURN_FAILURE;
            }

            // Get payload data
            $payload = $this->getPayloadData($input);

            if (empty($payload)) {
                $output->writeln('<error>Payload data is required. Use --json-file or provide individual options.</error>');
                return Cli::RETURN_FAILURE;
            }

            // Validate required fields
            $requiredFields = ['brand', 'serialNo', 'usedTransactionNo'];
            foreach ($requiredFields as $field) {
                if (empty($payload[$field])) {
                    $output->writeln("<error>Required field '{$field}' is missing</error>");
                    return Cli::RETURN_FAILURE;
                }
            }

            // Validate merchant AES credentials
            $aesKey = $merchant->getAesKey();
            $aesIv = $merchant->getAesIv();
            
            if (empty($aesKey)) {
                $output->writeln('<error>Merchant AES key is empty</error>');
                return Cli::RETURN_FAILURE;
            }
            
            if (empty($aesIv)) {
                $output->writeln('<error>Merchant AES IV is empty</error>');
                return Cli::RETURN_FAILURE;
            }

            // Clean and sanitize payload to ensure valid UTF-8 encoding
            $payload = $this->sanitizeUtf8($payload);
            
            // Encrypt the payload
            $jsonString = json_encode($payload, JSON_UNESCAPED_UNICODE);
            
            if ($jsonString === false) {
                $errorMsg = json_last_error_msg();
                $output->writeln("<error>Failed to encode payload to JSON: {$errorMsg}</error>");
                $output->writeln("<error>Payload data: " . print_r($payload, true) . "</error>");
                return Cli::RETURN_FAILURE;
            }
            
            $aesString = $this->apiHelper->aesEncrypt(
                $jsonString,
                $aesKey,
                $aesIv
            );

            // Output results
            $output->writeln('');
            $output->writeln('<info>=== AES Encrypted String ===</info>');
            $output->writeln($aesString);
            $output->writeln('');
            $output->writeln('<info>=== Original Payload (for reference) ===</info>');
            $output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $output->writeln('');

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * Get payload data from input options or JSON file
     *
     * @param InputInterface $input
     * @return array
     */
    protected function getPayloadData(InputInterface $input): array
    {
        // Check if JSON file is provided
        $jsonFile = $input->getOption(self::JSON_FILE);
        if (!empty($jsonFile)) {
            if (!file_exists($jsonFile)) {
                throw new \Exception("JSON file not found: {$jsonFile}");
            }

            $jsonContent = file_get_contents($jsonFile);
            $payload = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON file: " . json_last_error_msg());
            }

            return $payload;
        }

        // Get data from individual options
        $payload = [];

        $brand = $input->getOption(self::BRAND);
        if (!empty($brand)) {
            $payload['brand'] = $brand;
        }

        $serialNo = $input->getOption(self::SERIAL_NO);
        if (!empty($serialNo)) {
            $payload['serialNo'] = $serialNo;
        }

        $transactionNo = $input->getOption(self::TRANSACTION_NO);
        if (!empty($transactionNo)) {
            $payload['usedTransactionNo'] = $transactionNo;
        }

        $storeNo = $input->getOption(self::STORE_NO);
        if (!empty($storeNo)) {
            $payload['usedStoreNo'] = $storeNo;
        }

        $uniqueId = $input->getOption(self::UNIQUE_ID);
        if (!empty($uniqueId)) {
            $payload['uniqueId'] = $uniqueId;
        }

        return $payload;
    }

    /**
     * Sanitize array data to ensure all strings are valid UTF-8
     *
     * @param mixed $data
     * @return mixed
     */
    protected function sanitizeUtf8($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeUtf8($value);
            }
        } elseif (is_string($data)) {
            // Use iconv to remove invalid UTF-8 sequences if available
            if (function_exists('iconv')) {
                $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $data);
                if ($cleaned !== false) {
                    $data = $cleaned;
                }
            }
            
            // Use mb_convert_encoding to ensure valid UTF-8
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            
            // Remove any remaining non-printable control characters (except newlines, tabs, carriage returns)
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
        }
        
        return $data;
    }
}

