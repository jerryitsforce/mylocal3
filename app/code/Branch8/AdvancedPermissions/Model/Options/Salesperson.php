<?php

declare(strict_types=1);

namespace Branch8\AdvancedPermissions\Model\Options;

use Branch8\SellerContactInformation\Helper\Data as SellerHelper;
use Magento\Framework\Data\OptionSourceInterface;

class Salesperson implements OptionSourceInterface
{
    /** @var SellerHelper */
    protected SellerHelper $sellerHelper;

    /** @var array|null */
    protected ?array $options = null;

    /**
     * @param SellerHelper $sellerHelper
     */
    public function __construct(
        SellerHelper $sellerHelper
    ) {
        $this->sellerHelper = $sellerHelper;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = $this->sellerHelper->getAdminRolesArrayOptions();
        }
        return $this->options;
    }
}
