<?php

namespace Branch8\CatalogCustom\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;

class Upload extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Random
     */
    private Random $random;

    /**
     * @param Context $context
     * @param Filesystem $fileSystem
     * @param StoreManagerInterface $storeManager
     * @param Random $random
     */
    public function __construct(
        Context $context,
        Filesystem $fileSystem,
        StoreManagerInterface $storeManager,
        Random $random
    ) {
        parent::__construct($context);
        $this->fileSystem   = $fileSystem;
        $this->storeManager = $storeManager;
        $this->random = $random;
    }

    /**
     * Add ajax for Seller
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        $result = [
            'result' => false,
        ];

        $responseResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            if ($post) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $store          = $this->storeManager->getStore();
                $mediaPath      = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
                $catalogPath    = $mediaPath . "catalog/product";
                $catalogtmpPath = $mediaPath . "tmp/catalog/product";

                if (isset($post['imgBase64'])) {
                    $img  = $post['imgBase64'];
                    $img  = str_replace('data:image/png;base64,', '', $img);
                    $img  = str_replace(' ', '+', $img);
                    $data = base64_decode($img);
                    if (isset($post['actullImageData']['value_id']) && $post['actullImageData']['value_id'] != '') {
                        $existingFileDisperstionPath = $post['actullImageData']['file'];
                        $imageName                   = $catalogtmpPath . $existingFileDisperstionPath;
                        if (file_exists($imageName)) {
                            unlink($imageName);
                        }
                        $ImagesWithDIR = explode('/', $existingFileDisperstionPath);
                        $imageName     = '/' . $ImagesWithDIR[1] . '/' . $ImagesWithDIR[2] . '/' . $this->random->getRandomString(5) . $ImagesWithDIR[3];
                        if (!is_dir($catalogtmpPath . '/' . $ImagesWithDIR[1] . '/' . $ImagesWithDIR[2])) {
                            mkdir($catalogtmpPath . '/' . $ImagesWithDIR[1] . '/' . $ImagesWithDIR[2], 0777, true);
                        }
                        $imageWithPath        = $catalogtmpPath . $imageName;
                        $success              = file_put_contents($imageWithPath, $data);
                        $result['image_disp'] = $imageName . '.tmp';
                        $result['image_path'] = str_replace("/index.php", "", $store->getBaseUrl()) . 'media/tmp/catalog/product' . $imageName;
                        $result['value_id']   = $post['actullImageData']['value_id'];

                    } else {
                        $fileTMP                     = $post['actullImageData']['file'];
                        $existingFileDisperstionPath = explode('.tmp', $fileTMP);
                        $existingImageName           = $catalogtmpPath . $existingFileDisperstionPath[0];
                        if (file_exists($existingImageName)) {
                            unlink($existingImageName);
                        }
                        $ImagesWithDIR = explode('/', $existingFileDisperstionPath[0]);
                        $imageName     = '/' . $ImagesWithDIR[1] . '/' . $ImagesWithDIR[2] . '/' . $this->random->getRandomString(5) . $ImagesWithDIR[3];

                        if (!is_dir($catalogtmpPath . '/' . $ImagesWithDIR[1] . '/' . $ImagesWithDIR[2])) {
                            mkdir($catalogtmpPath . '/' . $ImagesWithDIR[1] . '/' . $ImagesWithDIR[2], 0777, true);
                        }

                        $imageNameWithTemp    = $imageName . '.tmp';
                        $imageWithPath        = $catalogtmpPath . $imageName;
                        $success              = file_put_contents($imageWithPath, $data);
                        $result['image_disp'] = $imageNameWithTemp;
                        $result['image_path'] = str_replace("/index.php", "", $store->getBaseUrl()) . 'media/tmp/catalog/product' . $imageName;
                    }
                    if ($success) {
                        $result['result'] = true;
                        $result['msg']    = ' file uploaded';
                    } else {
                        $result['msg'] = 'Unable to save the file.';
                    }
                }
            }
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
            return $responseResult->setData($result);
        }

        return $responseResult->setData($result);
    }
}
