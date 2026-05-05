<?php
namespace HotaiConnected\BigQuery\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

use Symfony\Component\Console\Input\InputOption;

class ImportKeywords extends Command
{
    const OPTION_VERSION = 'import-version';

    protected $resource;
    protected $filesystem;

    public function __construct(
        ResourceConnection $resource,
        Filesystem $filesystem
    ) {
        $this->resource = $resource;
        $this->filesystem = $filesystem;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('keyword:import')
            ->setDescription('Import keywords from pub/keyword/import.csv to static_keyword_list table')
            ->addOption(
                self::OPTION_VERSION,
                null, // 移除 'v' 以避免與系統內建的 verbose 衝突
                InputOption::VALUE_REQUIRED,
                'Version tag for this import (e.g. 20260413)',
                date('Ymd_His')
            );
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getOption(self::OPTION_VERSION);
        $csvPath = 'keyword/import.csv';
        $pubDir = $this->filesystem->getDirectoryRead(DirectoryList::PUB);
        $absolutePath = $pubDir->getAbsolutePath($csvPath);

        if (!$pubDir->isExist($csvPath)) {
            $output->writeln("<error>File not found: {$absolutePath}</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->writeln("<info>Starting import for version [{$version}]...</info>");

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('static_keyword_list');

        // 1. 先將所有舊版本設為 inactive
        $connection->update($tableName, ['is_active' => 0]);
        
        // 2. 清除該版本舊資料 (如果重複匯入同一個 version)
        $connection->delete($tableName, ['version = ?' => $version]);

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            $output->writeln("<error>Cannot open file.</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $headers = fgetcsv($handle); // 略過標頭
        $batchData = [];
        $batchSize = 500;
        $totalCount = 0;
        $taiwanTime = $this->getTaiwanCurrentDatetime();

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;

            $batchData[] = [
                'keyword' => $row[0],
                'version' => $version,
                'is_active' => 1, // 新匯入的設為 active
                'created_at' => $taiwanTime
            ];

            if (count($batchData) >= $batchSize) {
                $connection->insertMultiple($tableName, $batchData);
                $totalCount += count($batchData);
                $batchData = [];
                $output->write('.');
            }
        }

        if (!empty($batchData)) {
            $connection->insertMultiple($tableName, $batchData);
            $totalCount += count($batchData);
        }

        fclose($handle);
        $output->writeln("");
        $output->writeln("<info>Successfully imported {$totalCount} keywords under version [{$version}].</info>");

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    protected function getTaiwanCurrentDatetime(): string
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        return $taiwanDateObj->format("Y-m-d H:i:s");
    }
}
