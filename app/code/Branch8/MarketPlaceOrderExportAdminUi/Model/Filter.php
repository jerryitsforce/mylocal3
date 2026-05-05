<?php

namespace Branch8\MarketPlaceOrderExportAdminUi\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;

class Filter extends \Magento\Ui\Component\MassAction\Filter
{
    private $dataProvider;

    /**
     * @param AbstractDb $collection
     * @return AbstractDb
     * @throws LocalizedException
     */
    public function getCollection(AbstractDb $collection)
    {
        $selected = $this->request->getParam(static::SELECTED_PARAM);
        $excluded = $this->request->getParam(static::EXCLUDED_PARAM);

        $isExcludedIdsValid = (is_array($excluded) && !empty($excluded));
        $isSelectedIdsValid = (is_array($selected) && !empty($selected));

        if ('false' !== $excluded) {
            if (!$isExcludedIdsValid && !$isSelectedIdsValid) {
                throw new LocalizedException(__('An item needs to be selected. Select and try again.'));
            }
        }

        $filterIds = $this->getFilterIds();
        if (\is_array($selected)) {
            $filterIds = array_unique(array_merge($filterIds, $selected));
        }
        $collection->addFieldToFilter(
            $collection->getResource()->getIdFieldName(),
            ['in' => $filterIds]
        );

        return $collection;
    }

    /**
     * @return int[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getFilterIds()
    {
        $this->applySelectionOnTargetProvider();
        /**
         * @var $dataProvider \Branch8\MaskCustomerInformation\Ui\Listing\MaskOrderCollectionDataProvider
         */
        $dataProvider = $this->getDataProvider();
        return $dataProvider->getSearchResult()->getAllIds();
    }

    private function getDataProvider()
    {
        if (!$this->dataProvider) {
            $component = $this->getComponent();
            $this->prepareComponent($component);
            $this->dataProvider = $component->getContext()->getDataProvider();
        }
        return $this->dataProvider;
    }
}
