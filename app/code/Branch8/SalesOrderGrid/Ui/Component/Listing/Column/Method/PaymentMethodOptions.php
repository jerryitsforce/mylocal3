<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SalesOrderGrid\Ui\Component\Listing\Column\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Payment\Helper\Data;
/**
 * Class Options for Listing Column Method
 */
class PaymentMethodOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param Data $paymentHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [['value' => 'hotaipay', 'label' => $this->getMethodStoreTitle('hotaipay')]];
        }

        return $this->options;
    }

    private function getMethodStoreTitle(string $code, ?int $storeId = null): string
    {
        $configPath = sprintf('%s/%s/title', Data::XML_PATH_PAYMENT_METHODS, $code);
        return (string)$this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
