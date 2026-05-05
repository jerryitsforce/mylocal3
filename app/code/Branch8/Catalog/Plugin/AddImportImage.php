<?php

namespace Branch8\Catalog\Plugin;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Setup\Exception;
use \Psr\Log\LoggerInterface;

class AddImportImage
{
    protected $filesystem;

    private $gallleryProcessor;

    private $productGallery;

    private $productMediaConfig;

    private $resourceConnection;

    public $logger;

    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Catalog\Model\Product\Gallery\Processor $gallleryProcessor,
        \Magento\Catalog\Model\ResourceModel\Product\Gallery $productGallery,
        \Magento\Catalog\Model\Product\Media\Config $productMediaConfig,
        ResourceConnection $resourceConnection,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->filesystem = $filesystem;
        $this->gallleryProcessor = $gallleryProcessor;
        $this->productGallery = $productGallery;
        $this->productMediaConfig = $productMediaConfig;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    public function afterSave($subject, $result, $product, $saveOptions)
    {
        $extAttribute = $product->getExtensionAttributes();
        if(!$extAttribute) {
            return $result;
        }
        $imagePathToImport = $extAttribute->getImageFilePath();

        if (empty($imagePathToImport)) {
            return $result;
        }

        if (!count($imagePathToImport)){
            return $result;
        }

        $mediaPath = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();

        $failImages = [];
        foreach($imagePathToImport as $imgKey => $_imageObj){
            if(!$this->validate($_imageObj, $mediaPath)){
                $failImages[] = $_imageObj->getFile();
                unset($imagePathToImport[$imgKey]);
            }
        }
        if(!count($imagePathToImport)){
            return $result;
        }
        /**
         * New images are valid, delete ole images
         */
        if(count($imagePathToImport)){
            $mediaAttrArr = array_keys($result->getMediaAttributeValues());
            $mediaAttrList = '"'.implode('","', $mediaAttrArr).'"';
            $oldImages = $result->getMediaGalleryImages();
            foreach($oldImages as $_oldImage) {
                $this->productGallery->deleteGallery($_oldImage->getValueId());
                $this->gallleryProcessor->removeImage($result, $_oldImage->getFile());
            }
            $connection = $this->resourceConnection->getConnection();
            $productTable = $connection->getTableName('catalog_product_entity');
            $productVarcharTable = $connection->getTableName('catalog_product_entity_varchar');
            $eavTable = $connection->getTableName('eav_attribute');
            $sqlSelectProduct = $connection->select()
                ->from($productTable, 'row_id')
                ->where('sku=:sku');
            $where = "attribute_id in( select attribute_id from ".$eavTable." where attribute_code in(".$mediaAttrList."))
            and row_id = (".$connection->fetchOne($sqlSelectProduct, ['sku' => $result->getSku()]).")";
             $connection->delete($productVarcharTable, $where);
        }

        $cntSuccess = 0;
        foreach($imagePathToImport as $_validImage){
            $fullPath = $mediaPath.'import/'.trim((string)$_validImage->getFile());
            $fileType = (string)$_validImage->getType();
            $fileTypeArr = explode(',', $fileType);
            foreach($fileTypeArr as $kType => $_type){
                $_type = trim($_type);
                if($_type == ''){
                    unset($fileTypeArr[$kType]);
                }
            }
            try {
                $result->addImageToMediaGallery($fullPath, $fileTypeArr, false, false);

                $cntSuccess ++;
                /**
                 * Do not delete image, because a image can use for many product
                 */
//            unlink($fullPath);

            }catch (\Exception $e){
                //do no things
                $this->logger->critical($e);
            }
        }

        if($cntSuccess){
            $result->save();
            return $subject->get($result->getSku(), false, $result->getStoreId());
        }


        return $result;
    }

    private function validate($imgObj, $mediaPath){
        $imgFile = (string)$imgObj->getFile();
        $fullPath = $mediaPath.'import/'.$imgFile;
        if(!file_exists($fullPath)) {
            return false;
        }
        if(trim($imgFile) == ''){
            return false;
        }
        return true;
    }

}
