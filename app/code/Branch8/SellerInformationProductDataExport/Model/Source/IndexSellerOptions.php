<?php

namespace Branch8\SellerInformationProductDataExport\Model\Source;

use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;

class IndexSellerOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var SellerCollection
     */
    protected $_sellerCollectionFactory;

    private $options = null;
    private \Webkul\Marketplace\Helper\Data $helper;

    public function __construct(
        \Webkul\Marketplace\Helper\Data $helper,
        SellerCollectionFactory         $sellerCollectionFactory
    )
    {
        $this->helper = $helper;
        $this->_sellerCollectionFactory = $sellerCollectionFactory;
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function getAllOptions()
    {
        if (null === $this->_options) {
            $this->options = [];
            try {
                $sellerList = $this->helper->getSellerList();
                $this->options = $sellerList;
            } catch (\Exception $exception) {
                $this->options = [];
            }
        }
        return $this->options;
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}
