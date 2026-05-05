<?php
namespace Branch8\SellerContactInformation\Plugin;

use Magento\Ui\Component\MassAction\Filter;

class ExportRowDataModification
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * ExportRowDataModification constructor.
     * @param Filter $filter
     */
    public function __construct(
        Filter $filter
    ) {
        $this->filter = $filter;
    }

    public function afterGetRowData($subject, $result, $document, $fields, $options)
    {
        $component = $this->filter->getComponent();
        if ($component->getName() == 'marketplace_sellers_list')
        {
            $i = 0;
            $options = $subject->getOptions();
            foreach ($fields as $column)
            {
                if ($column === 'shipping_methods') {
                   $values = $document->getCustomAttribute($column)->getValue();
                   $values = $values ? explode(',', $values) : [];
                   $label = [];
                   foreach ($values as $value) {
                       if (!isset($options[$column][$value])) {
                           continue;
                       }
                       $label[] = $options[$column][$value]->getText();
                   }
                    $result[$i] = implode(',', $label);
                }
                $i++;
            }
        }
        return $result;
    }
}
