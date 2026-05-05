<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\BrandManagement\Observer\Admin;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBrand\Model\ConfigProvider;
use Magento\Framework\Data\Form;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OptionFormFeatured implements ObserverInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * Constructor
     *
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * Execute observer
     * Only updates the legend text, as Amasty observer already adds the required fields
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Form $fieldSet */
        $fieldSet = $observer->getEvent()->getFieldset();
        /** @var OptionSettingInterface $setting */
        $setting = $observer->getEvent()->getSetting();
        $storeId = $observer->getEvent()->getStoreId();
        $brandAttributeCode = $this->configProvider->getBrandAttributeCode((int) ($storeId ?? 0));
        $attributeCode = $setting->getAttributeCode();

        // Only update the legend, don't add fields as Amasty observer already does that
        if ($attributeCode === $brandAttributeCode) {
            $fieldSet->setData('legend', 'Brand Options');
        }
    }
}

