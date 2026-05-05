<?php

namespace Branch8\Rma\Ui\Component\Columns;

use Branch8\MaskCustomerInformation\Model\AdminPermission;
use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CreatedAt extends Column
{
    protected $timezone;
    public function __construct(
        ContextInterface                          $context,
        UiComponentFactory                        $uiComponentFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array                                     $components = [],
        array                                     $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->timezone = $timezone;
    }

    public function prepareDataSource(array $dataSource)
    {
        $fieldName = $this->getData('name');
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $createdAt = $item['created_date'];
                $formatedDate = $this->timezone->date($createdAt)->format('Y-m-d H:i:s');
                $item[$fieldName] = $formatedDate;
            }
        }
        return $dataSource;
    }
}