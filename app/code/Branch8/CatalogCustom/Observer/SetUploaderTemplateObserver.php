<?php
declare(strict_types=1);

namespace Branch8\CatalogCustom\Observer;

use Branch8\CatalogCustom\viewModel\MediaGalleryUploaderConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Dispatcher for the `SetUploaderTemplate` event.
 */
class SetUploaderTemplateObserver implements ObserverInterface
{
    /**
     * Handle the `SetUploaderTemplate` event.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $object = $observer->getEvent()->getBlock();
        if ($object
            && $object instanceof \Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery\Content
            && $uploaderBlock = $object->getuploader()
        ) {
            $uploaderBlock->setData('media_uploader_config',ObjectManager::getInstance()->get(MediaGalleryUploaderConfig::class));
            $uploaderBlock->setTemplate('Branch8_CatalogCustom::media/uploader.phtml');
        }
    }
}
