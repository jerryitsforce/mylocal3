<?php

declare(strict_types=1);

namespace Branch8\Marketplace\Model\Import;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Branch8\Marketplace\Service\MarketplaceLogger;

class GetTrackingNumberFromCsv
{
    public const FILE_ID = 'import_tracking_file';

    private ?Filesystem\Directory\WriteInterface $varDirectory = null;

    private ?string $uploadedFile = null;

    /**
     * @var UploaderFactory
     */
    private UploaderFactory $uploaderFactory;

    /**
     * @var Random
     */
    private Random $random;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var Csv
     */
    private Csv $csv;
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param UploaderFactory $uploaderFactory
     * @param Random $random
     * @param Filesystem $filesystem
     * @param Csv $csv
     * @param MarketplaceLogger $marketplaceLogger
     */
    public function __construct(
        UploaderFactory $uploaderFactory,
        Random $random,
        Filesystem $filesystem,
        Csv $csv,
        MarketplaceLogger $marketplaceLogger
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->random = $random;
        $this->filesystem = $filesystem;
        $this->csv = $csv;
        $this->marketplaceLogger = $marketplaceLogger;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(): ?array
    {
        $uploader = $this->uploaderFactory->create(['fileId' => self::FILE_ID]);
        $uploader->setAllowedExtensions(['csv']);

        $fileExtension = $uploader->getFileExtension();
        $newFileName = $this->random->getRandomString(32) . '.' . $fileExtension;

        try {
            $result = $uploader->save($this->getVarDirectory()->getAbsolutePath('import_tracking/'), $newFileName);
        } catch (Exception $e) {
            $this->marketplaceLogger->logException('GetTrackingNumberFromCsv', $e, [
                'file_id' => self::FILE_ID,
                'new_file_name' => $newFileName,
            ]);
            throw new LocalizedException(__('Unable to upload file.'));
        }

        if ($result === false) {
            throw new LocalizedException(__('Unable to upload file.'));
        }

        try {
            $this->uploadedFile = $this->getVarDirectory()->getRelativePath($result['path'] . $result['file']);
            return $this->csv->getData($this->getVarDirectory()->getAbsolutePath($this->uploadedFile));
        } catch (Exception $e) {
            $this->marketplaceLogger->logException('GetTrackingNumberFromCsv', $e, [
                'uploaded_file' => $this->uploadedFile,
            ]);
            throw new LocalizedException(__('Unable to upload file.'));
        }
    }

    /**
     * @throws FileSystemException
     */
    private function getVarDirectory(): ?Filesystem\Directory\WriteInterface
    {
        if ($this->varDirectory === null) {
            $this->varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        }
        return $this->varDirectory;
    }

    public function __destruct()
    {
        if (!empty($this->uploadedFile)) {
            try {
                $this->getVarDirectory()->delete($this->uploadedFile);
            } catch (Exception $e) {
                $this->marketplaceLogger->logException('GetTrackingNumberFromCsv', $e, [
                    'uploaded_file' => $this->uploadedFile,
                ]);
            }
        }
    }
}
