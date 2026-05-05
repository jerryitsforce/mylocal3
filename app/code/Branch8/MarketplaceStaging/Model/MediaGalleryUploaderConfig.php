<?php

namespace Branch8\MarketplaceStaging\Model;

class MediaGalleryUploaderConfig
{
    /**
     * @return array
     */
    public function getMediaUploadConfig()
    {
        return [
            'maxSize' => $this->getMaxUploadSize(),
            'allowExtensions' => ['jpg', 'jpeg', 'png', 'gif']
        ];
    }

    /**
     * @return int
     */
    public function getMaxUploadSize()
    {
        return 5 * 1024 * 1024;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getMaxSizeError()
    {
        return __('檔案大小超過限制！最大允許：5 MB。請選擇較小的檔案後重新上傳。');
    }
}
