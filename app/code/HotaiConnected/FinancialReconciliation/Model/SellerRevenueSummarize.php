<?php
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Model;

use HotaiConnected\FinancialReconciliation\Api\SellerRevenueSummarizeInterface;
use HotaiConnected\FinancialReconciliation\Helper\ExportSellerRevenueSummarize;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Webapi\Rest\Response;

class SellerRevenueSummarize implements SellerRevenueSummarizeInterface
{
    /**
     * @var ExportSellerRevenueSummarize
     */
    protected ExportSellerRevenueSummarize $exportSellerRevenueSummarize;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected \Magento\Framework\App\ResourceConnection $resourceConnection;

    /**
     * @var FileFactory
     */
    protected FileFactory $fileFactory;

    /**
     * @var File
     */
    protected File $fileDriver;

    /**
     * @var Response
     */
    protected Response $response;

    /**
     * @param ExportSellerRevenueSummarize $exportSellerRevenueSummarize
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param FileFactory $fileFactory
     * @param File $fileDriver
     * @param Response $response
     */
    public function __construct(
        ExportSellerRevenueSummarize $exportSellerRevenueSummarize,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        FileFactory $fileFactory,
        File $fileDriver,
        Response $response
    )
    {
        $this->exportSellerRevenueSummarize = $exportSellerRevenueSummarize;
        $this->resourceConnection = $resourceConnection;
        $this->fileFactory = $fileFactory;
        $this->fileDriver = $fileDriver;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getSellerRevenueSummarize(string $ids): \Magento\Framework\App\ResponseInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('financial_reconciliation');
        $select = $connection->select()->from(
            $tableName,
            ['seller_code', 'from', 'to']
        )->where('id IN (?)', explode(',', $ids));

        $records = $connection->fetchAll($select);
        if (empty($records)) {
            throw new LocalizedException(__('No reconciliation records found for the given IDs.'));
        }

        $filePaths = [];
        foreach ($records as $record) {
            try {
                $filePath = $this->exportSellerRevenueSummarize->execute(
                    $record['from'],
                    $record['to'],
                    $record['seller_code']
                );
                if ($filePath !== '') {
                    $filePaths[] = $filePath;
                }
            } catch (LocalizedException $e) {
                throw new LocalizedException(__($e->getMessage()));
            } catch (\Exception $e) {
                throw new LocalizedException(__('An error occurred while generating the report for seller %1.', $record['seller_code']));
            }
        }
        if (empty($filePaths)) {
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'No export files were generated.']))
                ->sendResponse();
            exit;
        }
        // Assuming only one file is generated for simplicity, or you might need to zip them.
        // For now, let's return the first generated file.
        $filePathToDownload = reset($filePaths);
        $fileName = basename($filePathToDownload);

        //$fileContent = $this->fileDriver->fileGetContents($filePathToDownload);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        ob_clean();
        flush();

        readfile($filePathToDownload);

        // Clean up the generated final zip file and temp directory after sending
        unlink($filePathToDownload);
        //  $this->deleteDirectory($tempDir);
        exit;
        // Delete the temporary file after reading its content
        //$this->fileDriver->deleteFile($filePathToDownload);
    }

    /**
     * {@inheritdoc}
     */
    public function getSellerRevenueSummarizeAll(string $invoice_from, string $invoice_to): \Magento\Framework\App\ResponseInterface
    {
        $invoice_from = urldecode($invoice_from);
        $invoice_to = urldecode($invoice_to);

        $dateFormat = 'Y-m-d H:i:s';
        $dFrom = \DateTime::createFromFormat($dateFormat, $invoice_from);
        if (!($dFrom && $dFrom->format($dateFormat) === $invoice_from)) {
            throw new LocalizedException(__('Invalid invoice_from format. Expected: %1', $dateFormat));
        }

        $dTo = \DateTime::createFromFormat($dateFormat, $invoice_to);
        if (!($dTo && $dTo->format($dateFormat) === $invoice_to)) {
            throw new LocalizedException(__('Invalid invoice_to format. Expected: %1', $dateFormat));
        }

        try {
            $filePath = $this->exportSellerRevenueSummarize->execute(
                $invoice_from,
                $invoice_to,
                'all'
            );
        } catch (LocalizedException $e) {
            throw new LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
            throw new LocalizedException(__('An error occurred: ' . $e->getMessage()));
        }

        if (empty($filePath) || !file_exists($filePath)) {
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['success' => false, 'message' => 'No export files were generated.']))
                ->sendResponse();
            exit;
        }

        $fileName = basename($filePath);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        ob_clean();
        flush();

        readfile($filePath);

        unlink($filePath);
        exit;
    }
}
