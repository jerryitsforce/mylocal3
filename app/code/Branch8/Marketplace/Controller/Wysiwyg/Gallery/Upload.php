<?php
declare(strict_types=1);

namespace Branch8\Marketplace\Controller\Wysiwyg\Gallery;

use Branch8\Marketplace\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Api\Data\WysiwygImageInterfaceFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

/**
 * Marketplace Wysiwyg Image Upload controller.
 */
class Upload extends \Webkul\Marketplace\Controller\Wysiwyg\Gallery\Upload
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var Filesystem\Driver\File $file
     */
    private $file;

    /**
     * Media StorageFile Uploader factory.
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $fileUploaderFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var WysiwygImageInterfaceFactory
     */
    protected $wysiwygImage;
    /**
     * @var MpHelper
     */
    protected $mpHelper;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    private Data $helperData;
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param Context $context
     * @param Filesystem $filesystem
     * @param Filesystem\Driver\File $file
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param WysiwygImageInterfaceFactory $wysiwygImage
     * @param MpHelper $mpHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param Data $data
     * @param MarketplaceLogger|null $marketplaceLogger
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context                                          $context,
        Filesystem                                       $filesystem,
        Filesystem\Driver\File                           $file,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        StoreManagerInterface                            $storeManager,
        WysiwygImageInterfaceFactory                     $wysiwygImage,
        MpHelper                                         $mpHelper,
        \Magento\Framework\Json\Helper\Data              $jsonHelper,
        Data                                             $data,
        MarketplaceLogger                                $marketplaceLogger = null
    )
    {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(
            DirectoryList::MEDIA
        );

        parent::__construct(
            $context,
            $filesystem,
            $file,
            $fileUploaderFactory,
            $storeManager,
            $wysiwygImage,
            $mpHelper,
            $jsonHelper
        );
        $this->helperData = $data;
        $this->file = $file;
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);

    }

    /**
     * Upload image function
     *
     * @return void
     */
    public function execute()
    {
        $helper = $this->mpHelper;
        $isPartner = $helper->isSeller();
        $sellerId = $helper->getCustomerId();
        $sellerWysiwygFolder = $this->helperData->getSellerWysiwygTextDirectory($sellerId);
        $sellerCode = $this->helperData->getSellerCode($sellerId);
        try {
            $target = $this->mediaDirectory->getAbsolutePath(
                $sellerWysiwygFolder
            );
            $fileUploader = $this->fileUploaderFactory->create(
                ['fileId' => 'image']
            );
            $fileUploader->setAllowedExtensions(
                ['gif', 'jpg', 'png', 'jpeg']
            );
            $fileUploader->setFilesDispersion(true);
            $fileUploader->setAllowRenameFiles(true);
            if ($sellerCode) {
                $newFilename = sprintf('%s_%s_%s', $sellerCode, $this->helperData->getRandomString(), basename($_FILES['image']['name']));
            } else {
                $newFilename = sprintf('%s_%s', basename($_FILES['image']['name']), $this->helperData->getRandomString());
            }
            $resultData = $fileUploader->save($target, $newFilename);
            $resultData['url'] = $this->storeManager->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $sellerWysiwygFolder . '/' . ltrim(str_replace('\\', '/', $resultData['file']), '/');
            $resultData['file'] = $resultData['file'] . '.tmp';
            if ($isPartner == 1) {
                $this->saveImageInformation($sellerId, $resultData);
            }
            unset($resultData['tmp_name']);
            unset($resultData['path']);
            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode($resultData)
            );
        } catch (\Exception $e) {
            $this->marketplaceLogger->logException('Upload', $e, [
                'seller_id' => $sellerId,
                'seller_wysiwyg_folder' => $sellerWysiwygFolder,
            ]);
            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode(
                    [
                        'error' => $e->getMessage(),
                        'errorcode' => $e->getCode(),
                    ]
                )
            );
        }
    }

    /**
     * @param $sellerId
     * @param $resultData
     * @return int
     */
    private function saveImageInformation($sellerId, $resultData)
    {
        try {
            $imageUrl = $resultData['url'];
            $imageName = $resultData['file'];
            $imageInfo = filetype($resultData['path']);
            $nameArray = explode("/", $imageName);
            $name = explode(".tmp", $nameArray[count($nameArray) - 1])[0];
            $descImage = $this->wysiwygImage->create();
            $descImage->setSellerId($sellerId);
            $descImage->setUrl($imageUrl);
            $descImage->setName($name);
            $descImage->setFile($name);
            $descImage->setType($imageInfo);
            $descImage->save();
            return 1;
        } catch (\Exception $e) {
            $this->marketplaceLogger->logException('Upload', $e, [
                'seller_id' => $sellerId,
                'result_data' => $resultData,
            ]);
            return 0;
        }
    }
}
