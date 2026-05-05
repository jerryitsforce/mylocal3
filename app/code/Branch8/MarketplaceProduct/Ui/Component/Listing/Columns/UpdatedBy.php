<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class UpdatedBy extends Column
{
    protected CustomerFactory $customerModel;

    protected $sellerName = [];


    /**
     * Price constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     * @param PriceCurrencyInterface|null $priceCurrency
     */
    public function __construct(
        ContextInterface        $context,
        UiComponentFactory      $uiComponentFactory,
        CustomerFactory         $customerModel,
        array                   $components = [],
        array                   $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->customerModel = $customerModel;
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource): array
    {
        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $sellerId = $item['seller_id'] ?? 0;
            if (!in_array($sellerId, $this->sellerName))  {
                $customer = $this->customerModel->create()->load($sellerId);
                $sellerName = $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
                $this->sellerName[$sellerId] = $sellerName;
            }
            $item[$fieldName] = $this->sellerName[$sellerId];
        }

        return $dataSource;
    }

}
