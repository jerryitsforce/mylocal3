<?php
declare(strict_types=1);


namespace Branch8\MarketPlaceParentOrderChildLink\Model\Import;

class Import extends \Magento\ImportExport\Model\Import
{
    public function uploadDataFileCsv($filePath)
    {
        $path_parts = pathinfo($filePath);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $entity = $this->getEntity();
        if (!file_exists($this->getWorkingDir())) {
            mkdir($this->getWorkingDir(), 0775, true);
        }

        /** @var \Magento\Framework\File\Csv $csv */
        $csv = $objectManager->create('\Magento\Framework\File\Csv');
        $fileInfo = ['name' => $path_parts['filename'] . '.csv'];
        $uploadedFile = $this->getWorkingDir() . $fileInfo['name'];
        $this->_varDirectory->copyFile(
            $filePath,
            $uploadedFile
        );
        $extension = 'csv';
        $sourceFile = $this->getWorkingDir() . $entity;
        $sourceFile .= '.' . $extension;
        $sourceFileRelative = $this->_varDirectory->getRelativePath($sourceFile);

        if (strtolower($uploadedFile) != strtolower($sourceFile)) {
            if ($this->_varDirectory->isExist($sourceFileRelative)) {
                $this->_varDirectory->delete($sourceFileRelative);
            }
            try {
                $this->_varDirectory->renameFile(
                    $this->_varDirectory->getRelativePath($uploadedFile),
                    $sourceFileRelative
                );
            } catch (\Magento\Framework\Exception\FileSystemException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The source file moving process failed.'));
            }
        }

        $this->_removeBom($sourceFile);
        $this->createHistoryReport($sourceFileRelative, $entity, $extension, $fileInfo);
        // trying to create source adapter for file and catch possible exception to be convinced in its adequacy
        try {
            $this->_getSourceAdapter($sourceFile);
        } catch (\Exception $e) {
            $this->_varDirectory->delete($sourceFileRelative);
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
        return $sourceFile;
    }
}
