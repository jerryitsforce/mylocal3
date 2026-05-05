<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Magento\Framework\View\FileSystem;
use Magento\Store\Model\StoreManager;

class GetProfileImageLink
{
    private StoreManager $storeManager;
    private \Magento\Framework\Filesystem\Driver\File $fileDriver;
    private \Magento\Framework\View\Asset\Repository $viewFileSystem;
    private \Magento\Framework\Filesystem $fileSystem;

    /**
     * @param StoreManager $storeManager
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Framework\View\Asset\Repository $viewFileSystem
     */
    public function __construct(
        StoreManager                              $storeManager,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Framework\Filesystem             $filesystem,
        \Magento\Framework\View\Asset\Repository  $viewFileSystem
    )
    {
        $this->fileDriver = $fileDriver;
        $this->fileSystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->viewFileSystem = $viewFileSystem;
    }

    /**
     * @param $image
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($image)
    {
        $path = $this->fileSystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->getAbsolutePath('mpchatsystem/chatProfile/' . $image);
        $default = $this->viewFileSystem->getUrlWithParams(
            'Branch8_WebkulMpBuyerSellerChatAdminUi::images/default_profile.png',
            []
        );
        if ($this->fileDriver->isExists($path)) {
            $default = $this
                    ->storeManager
                    ->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                    ) . 'mpchatsystem/chatProfile/' . $image;
        }
        return $default;
    }
}
