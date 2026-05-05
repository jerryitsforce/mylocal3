<?php

namespace Branch8\FamilyBonusPin\Ui\Component\Listing\Columns;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class DeleteUnsold extends Column
{
    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var FormKey */
    protected $formKey;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        ProductRepositoryInterface $productRepository,
        FormKey $formKey,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder        = $urlBuilder;
        $this->productRepository = $productRepository;
        $this->formKey           = $formKey;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                $item[$fieldName]["delete_unsold"] = [
                    "href"    => $this->getContext()->getUrl(
                        "family_bonus_pin/deleteUnsold/ReceiveGridForm",
                        [
                            "batch_setting_id" => $item["batch_setting_id"],
                            "product_id"       => $item["belong_to_product_id"],
                        ]
                    ),
                    "label"   => __('Delete unsold tickets'),
                    'confirm' => [
                        'title'   => __('Confirmation.'),
                        'message' => __('Are you sure you want to delete unsold tickets under batch code: ' . $item['batch_code'])
                    ]
                ];
            }
        }

        return $dataSource;
    }
}
