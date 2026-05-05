<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\Data\AttachmentInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Store\Model\StoreManager;

/**
 * Class Attachment
 */
class Attachment extends \Magento\Framework\Model\AbstractModel implements AttachmentInterface
{

    const TYPE_VIDEO = 'video';
    const TYPE_DOC = 'document';
    const TYPE_IMAGE = 'image';
    const TYPE_ZIP = 'zip';

    private $storeManager;

    private $baseMediaPath;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param StoreManager $storeManager
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        StoreManager                                            $storeManager,
        \Magento\Framework\Filesystem\DirectoryList             $directoryList,
        \Magento\Framework\Filesystem                           $filesystem,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->storeManager = $storeManager;
        $this->baseMediaPath = $storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $this->fileSystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->mediaDir = $this->directoryList->getPath('media') . '/helpdesk/attachment';
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\HelpDesk\Model\ResourceModel\Attachment');
    }

    /**
     * setName
     * @param string $value
     * @return $this|AttachmentInterface
     */
    public function setName(string $value)
    {
        $this->setData(self::NAME, $value);
        return $this;
    }

    /**
     * GetName
     * @return array|mixed|string|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * SetPath
     * @param string $value
     * @return $this|AttachmentInterface
     */
    public function setPath(string $value)
    {
        $this->setData(self::PATH, $value);
        return $this;
    }

    /**
     * GetPath
     * @return array|mixed|string|null
     */
    public function getPath()
    {
        return $this->getData(self::PATH);
    }

    /**
     * GetType
     * @return string
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * SetType
     * @param string $value
     * @return $this|AttachmentInterface
     */
    public function setType(string $value)
    {
        $this->setData(self::TYPE, $value);
        return $this;
    }

    /**
     * @return string
     */
    public function getFullPath()
    {
        if ($this->getData(self::FULL_PATH) === null) {
            $this->setData(self::FULL_PATH, trim(
                    sprintf(
                        "%s/%s",
                        trim($this->baseMediaPath, '\/'),
                        'helpdesk/attachment/'
                    )
                ) . $this->getPath()
            );
        }
        return (string)$this->getData(self::FULL_PATH);

    }

    /**
     * @param string $value
     * @return Attachment
     */
    public function setFullPath(string $value)
    {
        $this->setData(self::FULL_PATH, $value);
        return $this;
    }

    /**
     * @return string
     */
    public function getLocalPath()
    {
        if ($this->getData('local_path') == null) {
            $this->setData('local_path', $this->mediaDir . '/' . $this->getPath());
        }
        return (string)$this->getData('local_path');
    }
}
