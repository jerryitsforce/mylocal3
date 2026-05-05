<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Controller\Adminhtml\OneId;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\ImportExport\Model\Import\SampleFileProvider;

class SampleFiles extends Action implements HttpGetActionInterface
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var ReadFactory
     */
    protected $readFactory;

    /**
     * @var ComponentRegistrar
     */
    protected $componentRegistrar;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var SampleFileProvider
     */
    private $sampleFileProvider;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param RawFactory $resultRawFactory
     * @param ReadFactory $readFactory
     * @param ComponentRegistrar $componentRegistrar
     * @param SampleFileProvider|null $sampleFileProvider
     */
    public function __construct(
        Context            $context,
        FileFactory        $fileFactory,
        RawFactory         $resultRawFactory,
        ReadFactory        $readFactory,
        ComponentRegistrar $componentRegistrar,
        SampleFileProvider $sampleFileProvider = null
    )
    {
        parent::__construct(
            $context
        );
        $this->fileFactory = $fileFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->readFactory = $readFactory;
        $this->componentRegistrar = $componentRegistrar;
        $this->sampleFileProvider = $sampleFileProvider
            ?: ObjectManager::getInstance()
                ->get(SampleFileProvider::class);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $directoryRead = $this->getDirectoryRead();
            $filePath = $this->getPath();
            $fileContents = $directoryRead->readFile($filePath);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('There is no sample file for this entity.'));
            return $this->getResultRedirect();
        }
        $fileSize = isset($directoryRead->stat($filePath)['size'])
            ? $directoryRead->stat($filePath)['size'] : null;
        $fileName = 'oneIdList.csv';
        return $this->fileFactory->create(
            $fileName,
            $fileContents,
            DirectoryList::VAR_IMPORT_EXPORT,
            'application/octet-stream',
            $fileSize
        );
    }

    /**
     * @param string $entityName
     * @return string
     * @throws NoSuchEntityException
     */
    private function getPath(): string
    {
        $moduleName = 'Branch8_CustomNotification';
        $directoryRead = $this->getDirectoryRead();
        $moduleDir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $fileAbsolutePath = $moduleDir . '/Files/Sample/oneIdList' . '.csv';
        $filePath = $directoryRead->getRelativePath($fileAbsolutePath);
        if (!$directoryRead->isFile($filePath)) {
            throw new NoSuchEntityException(__("There is no file: %file", ['file' => $filePath]));
        }

        return $filePath;
    }

    /**
     * @param string $entityName
     * @return ReadInterface
     */
    private function getDirectoryRead(): ReadInterface
    {
        $moduleName = 'Branch8_CustomNotification';
        $moduleDir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $directoryRead = $this->readFactory->create($moduleDir);

        return $directoryRead;
    }

    /**
     * Get redirect result
     *
     * @return Redirect
     */
    private function getResultRedirect(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/import');

        return $resultRedirect;
    }
}
