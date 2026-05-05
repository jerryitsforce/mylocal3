<?php

declare(strict_types=1);

namespace Branch8\SellerContactInformation\Model\Options;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\Config as ShippingConfig;

class ShippingMethod implements OptionSourceInterface
{
    /** @var ShippingConfig */
    protected ShippingConfig $shippingConfig;

    /** @var array */
    protected array $options = [];

    /**
     * @param ShippingConfig $shippingConfig
     */
    public function __construct(
        ShippingConfig $shippingConfig
    ) {
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                [
                    'value' => 'no_shipping',
                    'label' => __('No Shipping')
                ]
            ];
            foreach ($this->shippingConfig->getAllCarriers() as $carrierCode => $carrierModel) {
                if (!$carrierModel->isActive()) {
                    continue;
                }
                $carrierMethods = $carrierModel->getAllowedMethods();
                if (!$carrierMethods) {
                    continue;
                }
                foreach ($carrierMethods as $methodCode => $methodTitle) {
                    if (!$methodCode) {
                        continue;
                    }
                    $this->options[] = [
                        'value' => $methodCode,
                        'label' => __($methodTitle)
                    ];
                }
            }
        }
        return $this->options;
    }
}
