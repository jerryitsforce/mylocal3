<?php

namespace Branch8\EventTicket\Model\Import;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Psr\Log\LoggerInterface;

class MappingFromFile
{
    public const FILE_ID = 'import_mapping_file';

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
    private LoggerInterface $logger;

    /**
     * @param UploaderFactory $uploaderFactory
     * @param Random $random
     * @param Filesystem $filesystem
     * @param Csv $csv
     * @param LoggerInterface $logger
     */
    public function __construct(
        UploaderFactory $uploaderFactory,
        Random          $random,
        Filesystem      $filesystem,
        Csv             $csv,
        LoggerInterface $logger
    )
    {
        $this->uploaderFactory = $uploaderFactory;
        $this->random = $random;
        $this->filesystem = $filesystem;
        $this->csv = $csv;
        $this->logger = $logger;
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
            $result = $uploader->save($this->getVarDirectory()->getAbsolutePath('import/import_mapping/'), $newFileName);
        } catch (Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_EventTicket', 'exceptionlog')){
                $this->logger->error($e);
            }
            throw new LocalizedException(__('Unable to upload file.'));
        }

        if ($result === false) {
            throw new LocalizedException(__('Unable to upload file.'));
        }

        try {
            $this->uploadedFile = $this->getVarDirectory()->getRelativePath($result['path'] . $result['file']);
            $rowData = $this->csv->getData($this->getVarDirectory()->getAbsolutePath($this->uploadedFile));
            foreach($rowData as $key => $_row){
                if(trim((string)implode('', $_row)) == ''){
                    unset($rowData[$key]);
                }
            }
            return $rowData;
        } catch (Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_EventTicket', 'exceptionlog')){
                $this->logger->error($e);
            }
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
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_EventTicket', 'exceptionlog')){
                    $this->logger->error($e);
                }
            }
        }
    }
}
