<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_OptionsWithStockAndImages
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Branch8\MarketplaceStaging\Controller\Adminhtml\Variation;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Branch8\MarketplaceProduct\Model\FileUploaderFactory;
use Magento\Framework\Filesystem;

/**
 * upload image for variation
 */
class Upload extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    public $_jsonData;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    public $logger;

    /**
     * @var array
     */
    private $allowedMimeTypes = [
        'jpg' => 'image/jpg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/png',
        'png' => 'image/gif'
    ];

    /**
     * @var UploaderFactory
     */
    protected $fileUploader;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\Image\AdapterFactory
     */
    protected $imageAdapterFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Json\Helper\Data $jsonData
     * @param FileUploaderFactory $fileUploader
     * @param Filesystem $filesystem
     * @param \Magento\Catalog\Model\Product\Media\Config $config
     * @param \Magento\Framework\Image\AdapterFactory $imageAdapterFactory
     * @param \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonData,
        FileUploaderFactory $fileUploader,
        Filesystem $filesystem,
        \Magento\Catalog\Model\Product\Media\Config $config,
        \Magento\Framework\Image\AdapterFactory $imageAdapterFactory,
        \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
        $this->fileUploader = $fileUploader;
        $this->imageAdapterFactory = $imageAdapterFactory;
        $this->config = $config;
        $this->_jsonData = $jsonData;
        $this->logger = $logger;
    }

    /**
     * Upload image(s) to the product gallery.
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        try {
            $fileId = 'image';
            $uploader = $this->fileUploader->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions($this->getAllowedExtensions());

            if (!$uploader->checkMimeType($this->getAllowedMimeTypes())) {
                throw new LocalizedException(__('We are unable to recognize or support this file extension type.'));
            }

            $imageAdapter = $this->imageAdapterFactory->create();
            $uploader->addValidateCallback('catalog_product_image', $imageAdapter, 'validateUploadFile');
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $result = $uploader->save(
                $mediaDirectory->getAbsolutePath($this->config->getBaseTmpMediaPath())
            );

            $this->_eventManager->dispatch(
                'catalog_product_gallery_upload_image_after',
                ['result' => $result, 'action' => $this]
            );

            unset($result['tmp_name']);
            unset($result['path']);

            $result['url'] = $this->config->getTmpMediaUrl($result['file']);
            $result['file'] = $result['file'] . '.tmp';
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
            $this->logger->info('ERROR in Webkul\OptionsWithStockAndImages\Controller\Adminhtml\Image\Upload');
            $this->logger->info($e->getMessage());
            $this->logger->info($e->getTraceAsString());
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($this->_jsonData->jsonEncode($result));
        return $this->getResponse();
    }

    /**
     * Get the set of allowed file extensions.
     *
     * @return array
     */
    private function getAllowedExtensions()
    {
        return array_keys($this->allowedMimeTypes);
    }

    /**
     * Get the set of allowed mime types.
     *
     * @return array
     */
    private function getAllowedMimeTypes()
    {
        return array_values($this->allowedMimeTypes);
    }
}
