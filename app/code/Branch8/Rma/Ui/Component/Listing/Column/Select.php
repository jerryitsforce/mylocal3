<?php

declare(strict_types=1);

namespace Branch8\Rma\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Select extends Column
{
    /** @var OptionSourceInterface */
    protected OptionSourceInterface $option;

    /** @var string */
    protected string $defaultLabel;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OptionSourceInterface $option
     * @param array $components
     * @param array $data
     * @param string $defaultLabel
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OptionSourceInterface $option,
        array $components,
        array $data,
        string $defaultLabel = ''
    ) {
        $this->option = $option;
        $this->defaultLabel = $defaultLabel;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $name = $this->getData('name');
            $options = $this->option->toOptionArray();
            $options = array_combine(array_column($options, 'value'), array_column($options, 'label'));
            foreach ($dataSource['data']['items'] as &$item) {
                $values = [];
                foreach (($item[$name] ? explode('|', $item[$name]) : []) as $value) {
                    $values[] = $options[$value] ?? ($this->defaultLabel ? __($this->defaultLabel) : $value);
                }
                $item[$name] = join('<br>', $values);
            }
        }
        return $dataSource;
    }
}
