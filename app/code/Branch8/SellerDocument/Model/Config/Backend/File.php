<?php
namespace Branch8\SellerDocument\Model\Config\Backend;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class File extends \Magento\Config\Model\Config\Backend\File
{

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface $requestData,
        Filesystem $filesystem,
        \Branch8\SellerContactInformation\Model\ContractUploadFactory $contractUploadFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_uploaderFactory = $uploaderFactory;
        $this->_requestData = $requestData;
        $this->_filesystem = $filesystem;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        parent::__construct($context, $registry, $config, $cacheTypeList, $uploaderFactory, 
            $requestData, $filesystem, $resource, $resourceCollection, $data);
        $this->_uploaderFactory = $contractUploadFactory;
    }

    private function setValueAfterValidation(string $value): void
    {
        if (preg_match('/[^a-z0-9\p{Han}_\\-\\.]+/iu', $value)
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            || !$this->_mediaDirectory->isFile($this->_getUploadDir() . DIRECTORY_SEPARATOR . basename($value))
        ) {
            throw new LocalizedException(__('Invalid file name'));
        }

        $this->setValue($value);
    }
}
