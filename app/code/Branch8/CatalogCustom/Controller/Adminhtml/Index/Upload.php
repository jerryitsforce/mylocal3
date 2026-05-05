<?php

namespace Branch8\CatalogCustom\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class Upload extends \Dotsquares\ImageEditor\Controller\Adminhtml\Index\Upload
{
    /**
     * Custom fix ajax display image
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
                        $existingFileurl             = $post['actullImageData']['url'];
                        $fileTMP                     = $existingFileDisperstionPath . '.tmp';
                        $imageName                   = $catalogtmpPath . $existingFileDisperstionPath;
                        if (file_exists($imageName)) {
                            unlink($imageName);
                        }
                        $ImagesWithDIR = explode('/', $existingFileDisperstionPath);
                        if (!is_dir($catalogtmpPath . '/' . $ImagesWithDIR[1] . '/' . $ImagesWithDIR[2])) {
                            mkdir($catalogtmpPath . '/' . $ImagesWithDIR[1] . '/' . $ImagesWithDIR[2], 0777, true);
                        }
                        $success              = file_put_contents($imageName, $data);
                        $result['image_disp'] = $fileTMP;
                        $result['image_path'] = str_replace("/index.php", "", $store->getBaseUrl()) . 'media/tmp/catalog/product' . $existingFileDisperstionPath;
                        $result['value_id']   = $post['actullImageData']['value_id'];

                    } else {
                        $fileTMP                     = $post['actullImageData']['file'];
                        $existingFileDisperstionPath = explode('.tmp', $fileTMP);
                        $existingImageName           = $catalogtmpPath . $existingFileDisperstionPath[0];
                        if (file_exists($existingImageName)) {
                            unlink($existingImageName);
                        }
                        $ImagesWithDIR = explode('/', $existingFileDisperstionPath[0]);
                        $imageName     = '/' . $ImagesWithDIR[1] . '/' . $ImagesWithDIR[2] . '/' . $this->generateRandomString() . $ImagesWithDIR[3];

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
