<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Psr\Log\LoggerInterface;

class SummaryChange extends Column
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var array
     */
    private array $label = [];

    /**
     * SummaryChange constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory  $collectionFactory,
        LoggerInterface    $logger,
        array              $components = [],
        array              $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        if (empty($this->label)) {
            $this->label = $this->getLabels();
        }

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $changeFields = [];
            $additionalInformationData = $item['additional_information'];
            if ($additionalInformationData != null) {
                $additionalInformation = json_decode((string)$additionalInformationData, true);
                if (is_array($additionalInformation)) {
                    foreach ($additionalInformation as $_field => $val) {
                        $changeFields[] = $_field;
                    }
                } else {
                    $this->logger->error('Item ' . $item['mageproduct_id'] . ' has additional_information is not an array ' . $additionalInformationData);
                }
            }

            $changeFields = array_unique($changeFields);

            $fields = array_filter(array_combine($changeFields, array_map(fn($key) => $this->label[$key] ?? null, $changeFields)), fn($value) => $value !== null);
            $item[$fieldName] = implode(',', array_values($fields));
        }

        return $dataSource;
    }

    /**
     * Returns all labels.
     *
     * @return array
     */
    private function getLabels(): array
    {
        $labels = [];
        $attrCollection = $this->collectionFactory->create()
            ->addFieldToSelect(['attribute_code', 'frontend_label']);
        foreach ($attrCollection as $attribute) {
            $labels[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }
        $labels['options'] = __('Options');
        $labels['related'] = __('Related Products');
        $labels['image_gallery'] = __('Image Gallery');
        $labels['wk_manage_variation'] = __('Variations');

        return $labels;
    }
}
