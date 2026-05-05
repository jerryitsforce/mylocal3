<?php

namespace Branch8\MarketplaceProduct\Helper;

use Branch8\Marketplace\Model\Actions\GetSellerWysiwygTextDirectory;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Store\Model\StoreManagerInterface;

class Images extends AbstractHelper
{
    /**
     * @var AttributeCollection
     */
    protected $attributeCollection;
    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $dir;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     * @since 101.0.0
     */
    protected $mediaConfig;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     * @since 101.0.0
     */
    protected $mediaDirectory;

    private GetSellerWysiwygTextDirectory $getSellerWysiwygTextDirectory;

    /**
     * @param Context $context
     * @param AttributeCollection $attributeCollection
     * @param Filesystem\DirectoryList $dir
     * @param StoreManagerInterface $storeManager
     * @param Config $mediaConfig
     * @param Filesystem $filesystem
     * @param GetSellerWysiwygTextDirectory $getSellerWysiwygTextDirectory
     * @throws FileSystemException
     */
    public function __construct(
        Context                                     $context,
        AttributeCollection                         $attributeCollection,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Store\Model\StoreManagerInterface  $storeManager,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Magento\Framework\Filesystem               $filesystem,
        GetSellerWysiwygTextDirectory               $getSellerWysiwygTextDirectory
    )
    {
        parent::__construct($context);
        $this->attributeCollection = $attributeCollection;
        $this->dir = $dir;
        $this->storeManager = $storeManager;
        $this->mediaConfig = $mediaConfig;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->getSellerWysiwygTextDirectory = $getSellerWysiwygTextDirectory;
    }

    public function processPostImagesInTextArea($product, $sellerId = null)
    {
        $mediaUrl = $this->storeManager->getStore(0)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $attrToUpdate = $this->getTextAreaAttribute();
        foreach ($attrToUpdate as $_attr) {
            if (!isset($product[$_attr])) {
                continue;
            }
            $attrVal = $product[$_attr];
            preg_match_all('/src="([^"]*)"/', $attrVal, $dataImages);
            if (!count($dataImages[1])) {
                continue;
            }
            $isUpdate = false;
            foreach ($dataImages[1] as $_dataImage) {
                $parseValue = explode(';base64,', $_dataImage);
                if (!isset($parseValue[1])) {
                    continue;
                }
                $isUpdate = true;
                $imageUrl = $mediaUrl . $this->uploadImage($_attr, $parseValue, $sellerId);
                $attrVal = str_replace($_dataImage, $imageUrl, $attrVal);
            }

            if ($isUpdate) {
                $product[$_attr] = $attrVal;
            }

        }

        return $product;
    }

    protected function getTextAreaAttribute()
    {
        $coll = $this->attributeCollection->addFieldToSelect('attribute_code')
            ->addFieldToFilter('frontend_input', 'textarea')
            ->addFieldToFilter('attribute_code', ['nin' => [
                'marketing_code', 'search_tag', 'custom_layout_update', 'meta_keyword', 'meta_description']
            ]);
        $attrs = [];
        foreach ($coll as $_attr) {
            $attrs[] = $_attr->getAttributeCode();
        }
        return $attrs;
    }

    /**
     * @param $attr
     * @param $parseValue
     * @param $sellerId
     * @return string
     * @throws FileSystemException
     */
    public function uploadImage($attr, $parseValue, $sellerId = null)
    {
        $directory = $this->getSellerWysiwygTextDirectory->execute($sellerId);
        $imageType = $parseValue[0];
        $parseImageType = explode('/', $imageType);
        $fileExt = $parseImageType[1];
        $fileName = $attr . '_' . microtime(true) . '_' . rand(0, 1000000) . '.' . $fileExt;
        $fileContent = base64_decode($parseValue[1]);
        $mediaPath = $this->dir->getPath('media') . '/' . $directory;
        if (!is_dir($mediaPath)) {
            mkdir($mediaPath, 0777, true);
        }
        $filePath = $mediaPath . '/' . $fileName;
        file_put_contents($filePath, $fileContent);
        chmod($filePath, 0775);
        $imageUrl = $directory . '/' . $fileName;
        return $imageUrl;
    }

    /**
     * Copy image to temporary directory
     *
     * @param string $file
     * @return string
     * @throws FileSystemException
     * @since 101.0.0
     */
    public function copyImage($file)
    {
        if ($file) {
            $imagePath = $this->mediaConfig->getMediaPath(
                $file
            );
            if ($this->mediaDirectory->isFile($imagePath)) {
                $file = $this->mediaConfig->getMediaPath($file);
                $pathinfo = pathinfo($file);
                $fileName = \Magento\MediaStorage\Model\File\Uploader::getCorrectFileName($pathinfo['basename']);
                $dispersionPath = \Magento\MediaStorage\Model\File\Uploader::getDispersionPath($fileName);
                $fileName = $dispersionPath . '/' . $fileName;
                $fileName = $dispersionPath . '/'
                    . $this->getNewFileName($this->mediaConfig->getTmpMediaPath($fileName));
                $destinationFile = $this->mediaConfig->getTmpMediaPath($fileName);
                $this->mediaDirectory->copyFile($file, $destinationFile);
            } else {
                $file = str_replace('.tmp', '', $file);
                $file = $this->mediaConfig->getTmpMediaPath($file);
                $pathinfo = pathinfo($file);
                $fileName = \Magento\MediaStorage\Model\File\Uploader::getCorrectFileName($pathinfo['basename']);
                $dispersionPath = \Magento\MediaStorage\Model\File\Uploader::getDispersionPath($fileName);
                $fileName = $dispersionPath . '/' . $fileName;
                $fileName = $dispersionPath . '/'
                    . $this->getNewFileName($this->mediaConfig->getTmpMediaPath($fileName));
                $destinationFile = $this->mediaConfig->getTmpMediaPath($fileName);
                $this->mediaDirectory->copyFile($file, $destinationFile);
            }
            $fileName .= '.tmp';
            return $fileName;
        }

        return '';
    }

    /**
     * Get new file name
     *
     * @param string $destinationFile
     * @return string
     * @since 101.0.0
     */
    private function getNewFileName($destinationFile)
    {
        /** @var Filesystem $fileSystem */
        $fileSystem = ObjectManager::getInstance()->get(Filesystem::class);
        $local = $fileSystem->getDirectoryRead(DirectoryList::MEDIA);
        /** @var TargetDirectory $targetDirectory */
        $targetDirectory = ObjectManager::getInstance()->get(TargetDirectory::class);
        $remote = $targetDirectory->getDirectoryRead(DirectoryList::ROOT);

        $fileExists = function ($path) use ($local, $remote) {
            return $local->isExist($path) || $remote->isExist($path);
        };

        $fileInfo = pathinfo($destinationFile);
        $index = 1;
        while ($fileExists($fileInfo['dirname'] . '/' . $fileInfo['basename'])) {
            $fileInfo['basename'] = $fileInfo['filename'] . '_' . ($index++);
            $fileInfo['basename'] .= isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
        }

        return $fileInfo['basename'];
    }

    /**
     * @param $filename
     * @return string
     */
    public static function normalizeImageFileName($filename)
    {
        $time = round(microtime(true) * 1000);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = pathinfo($filename, PATHINFO_FILENAME);
        if (function_exists('iconv')) {
            $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        }
        $name = preg_replace('/[^a-zA-Z0-9-_ ]/', '', $name);
        $name = preg_replace('/\s+/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');
        return strtolower($name) . '_' . $time . '.' . $ext;
    }
}
