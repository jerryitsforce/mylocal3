<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\ProductCertification\Helper\ImageUploader;

/**
 * Grid column to render the certification icon
 */
class Icon extends Column
{
    /**
     * @var ImageUploader
     */
    private ImageUploader $imageUploader;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ImageUploader $imageUploader
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ImageUploader $imageUploader,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->imageUploader = $imageUploader;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (!empty($item[$fieldName])) {
                    $item[$fieldName . '_src'] = $this->imageUploader->getIconUrl($item[$fieldName]);
                    $item[$fieldName . '_alt'] = $item['name'] ?? '';
                    $item[$fieldName . '_link'] = '';
                    $item[$fieldName]           = '<img src="' . $item[$fieldName . '_src'] . '" alt="' . htmlspecialchars((string)$item[$fieldName . '_alt']) . '" style="max-width:48px;max-height:48px;" />';
                }
            }
        }
        return $dataSource;
    }
}
