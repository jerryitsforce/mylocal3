<?php
declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Observer;

use Branch8\Catalog\Model\ConfigData;
use Branch8\MarketplaceProduct\Model\Product\Initialization\Helper;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Dispatcher for the `ValidateImageTags` event.
 */
class ValidateImageTagsObserver implements ObserverInterface
{
    private ConfigData $configData;
    private Helper $helper;
    private ProductFactory $productFactory;

    /**
     * @param ConfigData $configData
     * @param ProductFactory $productFactory
     * @param Helper $helper
     */
    public function __construct(
        ConfigData     $configData,
        ProductFactory $productFactory,
        Helper         $helper
    )
    {
        $this->productFactory = $productFactory;
        $this->helper = $helper;
        $this->configData = $configData;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer): void
    {
        if (!$this->configData->enableValidateImageTags()) {
            return;
        }
        $transport = $observer->getData('transport');
        $errors = $transport->getData('errors');
        $productData = $transport->getData('productData');
        $tagsNeedValidate = $this->configData->getImageTagsNeedToValidate();
        if (empty($productData)) {
            return;
        }
        $product = $this->helper->initializeFromData($this->productFactory->create(), $productData);
        if (!count($product->getMediaGalleryImages())) {
            return;
        }
        if (!$errors) {
            $errors = [];
        }
        $tagErros = [];
        foreach ($tagsNeedValidate as $code => $tag) {
            if ($product->getData($code) == '' || $product->getData($code) === 'no_selection') {
                $tagErros[$code] = $tag;
            }
        }
        if ($tagErros) {
            $errors[] = __('Please select all required image tags');
        }
        $transport->setData('errors', $errors);
    }
}
