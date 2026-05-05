<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class GrossProfit extends Column
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

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
        StoreManagerInterface   $storeManager,
        array                   $components = [],
        array                   $data = [],
        ?PriceCurrencyInterface $priceCurrency = null
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency ?? ObjectManager::getInstance()->get(PriceCurrencyInterface::class);
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $store = $this->storeManager->getStore(
            $this->context->getFilterParam('store_id', Store::DEFAULT_STORE_ID)
        );

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $additionInformation = $item['additional_information'];
             if($additionInformation){
                 $dataInformation = json_decode($additionInformation, true);
                 $oldValue = null;
                 $newValue = null;
                 if (isset($dataInformation['commission_percent'])) {
                     $oldValue = $dataInformation['commission_percent']['before'];
                     $newValue = $dataInformation['commission_percent']['after'];
                     if ($newValue && $newValue != $oldValue) {
                         $html = '<div class="price-comparison">';
                         $html .= '<span style="text-decoration: line-through; margin-right: 8px;">' . $oldValue . '</span>';
                         $html .= '<span>' . $newValue . '</span></div>';
                         $item[$fieldName] = $html;
                     }
                 }

             }
        }

        return $dataSource;
    }

    /**
     * Format price.
     *
     * @param mixed $price
     * @param StoreInterface $store
     *
     * @return string
     */
    private function formatPrice(mixed $price, StoreInterface $store): string
    {
        return $this->priceCurrency->format(
            sprintf("%F", $price),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $store
        );
    }
}
