<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\Services;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class FormatAddress
{
    private $addressConfig;
    public ScopeConfigInterface $scopeConfig;

    /**
     * @param AddressConfig $addressConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        AddressConfig        $addressConfig,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->addressConfig = $addressConfig;
    }

    /**
     * @param ParentOrderAddress $address
     * @return null
     */
    public function getFormattedAddress(ParentOrderAddress $address = null, $type = 'html')
    {
        if ($address == null) {
            return '';
        }
        $formatType = $this->addressConfig->getFormatByCode($type);
        if (!$formatType || !$formatType->getRenderer()) {
            return null;
        }
        $addressData = $address->getData();
        $addressData['locale'] = $this->getLocaleByStoreId(
            (int)$address->getParentOrder()->getDetail()->getStoreId()
        );
        return $formatType->getRenderer()->renderArray($addressData);
    }

    /**
     * Returns locale by storeId
     *
     * @param int $storeId
     * @return string
     */
    private function getLocaleByStoreId(int $storeId): string
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_DEFAULT_LOCALE, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
