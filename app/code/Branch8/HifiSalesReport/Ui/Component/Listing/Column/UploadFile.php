<?php

namespace Branch8\HifiSalesReport\Ui\Component\Listing\Column;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;

class UploadFile extends Column
{
    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var FormKey */
    protected $formKey;

    /** @var CommonHelper */
    protected $commonHelper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        FormKey $formKey,
        CommonHelper $commonHelper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder   = $urlBuilder;
        $this->formKey      = $formKey;
        $this->commonHelper = $commonHelper;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        $uploadMainFileFormAction   = $this->urlBuilder->getUrl('hifi_sales_report/upload/mainFile');
        $uploadDetailFileFormAction = $this->urlBuilder->getUrl('hifi_sales_report/upload/detailFile');
        $formKey                    = $this->formKey->getFormKey();

        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                $allowUpload                       = $this->commonHelper->checkIfMainRecordAllowUpload($item["record_id"]);
                $item[$fieldName . '_allowUpload'] = $allowUpload;

                if (!$allowUpload) {
                    continue;
                }

                $item[$fieldName . '_html']                       = "<button class='button'>Upload</button>";
                $item[$fieldName . '_title']                      = __('HIFI Sales Report File Upload');
                $item[$fieldName . '_uploadMainFileLabel']        = __('Upload Main File');
                $item[$fieldName . '_uploadDetailFileLabel']      = __('Upload Detail File');
                $item[$fieldName . '_resetLabel']                 = __('Reset');
                $item[$fieldName . '_recordId']                   = $item['record_id'];
                $item[$fieldName . '_uploadMainFileFormAction']   = $uploadMainFileFormAction;
                $item[$fieldName . '_uploadDetailFileFormAction'] = $uploadDetailFileFormAction;
                $item[$fieldName . '_formkey']                    = $formKey;
            }
        }

        return $dataSource;
    }
}
