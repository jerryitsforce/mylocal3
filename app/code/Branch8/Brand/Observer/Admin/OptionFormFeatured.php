<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Shop by Brand for Magento 2
 */

namespace Branch8\Brand\Observer\Admin;

use Amasty\ShopbyBase\Helper\FilterSetting;
use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBrand\Model\ConfigProvider;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\Form;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OptionFormFeatured implements ObserverInterface
{
    /**
     * @var Yesno
     */
    private $yesNoSource;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Yesno $yesNosource,
        ConfigProvider $configProvider
    ) {
        $this->yesNoSource = $yesNosource;
        $this->configProvider = $configProvider;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var Form $fieldSet */
        $fieldSet = $observer->getEvent()->getFieldset();
        /** @var OptionSettingInterface $setting */
        $setting = $observer->getEvent()->getSetting();
        $storeId = $observer->getEvent()->getStoreId();
        $brandAttributeCode = $this->configProvider->getBrandAttributeCode((int) $storeId);
        $attributeCode = $setting->getAttributeCode();

        if ($attributeCode === $brandAttributeCode) {
            $fieldSet->addField(
                'en_alphabet',
                'text',
                [
                    'name' => 'en_alphabet',
                    'label' => __('English Alphabet'),
                    'title' => __('English Alphabet')
                ]
            );
            $fieldSet->addField(
                'ch_alphabet',
                'text',
                [
                    'name' => 'ch_alphabet',
                    'label' => __('Chinese Alphabet'),
                    'title' => __('Chinese Alphabet')
                ]
            );
        }
    }
}
