<?php

namespace Branch8\HotaiCore\Ui\Component\Listing\Columns;

use Branch8\HotaiCore\Helper\Common as CommonHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Import extends Column
{
    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var FormKey */
    protected $formKey;

    /** @var CommonHelper */
    protected $commonHelper;

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
        CommonHelper $commonHelper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder        = $urlBuilder;
        $this->productRepository = $productRepository;
        $this->formKey           = $formKey;
        $this->commonHelper      = $commonHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        $formKey = $this->formKey->getFormKey();

        $downloadSampleActionUrl = $this->urlBuilder->getUrl('hotai_core/Import/DownloadSample');
        $downloadSampleFormKey   = $this->formKey->getFormKey();

        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                $displayImportButton                       = $this->commonHelper->isBatchImportTicketProduct($item["entity_id"]);
                $item[$fieldName . '_disaplyImportButton'] = $displayImportButton;
                if (!$displayImportButton) {
                    continue;
                }

                $formRoute = $this->commonHelper->getTicketActionUrl($item["entity_id"]);

                $item[$fieldName . '_html']                  = "<button class='button'><span>".__('Open Import Form')."</span></button>";
                $item[$fieldName . '_title']                 = __('HotaiCore Import');
                $item[$fieldName . '_submitlabel']           = __('Submit');
                $item[$fieldName . '_cancellabel']           = __('Reset');
                $item[$fieldName . '_productid']             = $item['entity_id'];
                $item[$fieldName . '_formaction']            = $this->urlBuilder->getUrl($formRoute);
                $item[$fieldName . '_formkey']               = $formKey;
                $item[$fieldName . '_downloadsampleaction']  = $downloadSampleActionUrl;
                $item[$fieldName . '_downloadsampleformkey'] = $downloadSampleFormKey;
            }
        }

        return $dataSource;
    }
}
