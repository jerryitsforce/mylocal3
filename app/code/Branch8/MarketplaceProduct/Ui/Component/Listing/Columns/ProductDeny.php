<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Framework\AuthorizationInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\MarketplaceProduct\Model\Config\Source\ApprovalFlowStatus;

class ProductDeny extends Column
{
    protected AuthorizationInterface $authorization;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        $this->authorization = $authorization;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }


    /**
     * @return void
     * @throws LocalizedException
     */
    public function prepare()
    {   
        $dataSource = $this->context->getDataProvider()->getName();
        
        if (!$this->authorization->isAllowed('Branch8_MarketplaceProduct::disapprove') 
            || $dataSource == 'manager_product_approval_management_listing_data_source') {
            $this->_data['config']['componentDisabled'] = true;
        }
        parent::prepare();
    }


    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }
        $fieldName = $this->getData('name');

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (!$this->authorization->isAllowed('Branch8_MarketplaceProduct::disapprove')
                    || ($item['approval_flow_status'] > 0 && !in_array($item['approval_flow_status'], [ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL ,ApprovalFlowStatus::CURATOR_PENDING_APPROVAL]))
                ) {
                    $item[$this->getData('name')] = null; // or customize how you want to hide/disable the button
                } else {
                    $item[$fieldName . '_html'] = "<button class='button'><span>" . __('Final Disapprove') . '</span></button>';
                    $item[$fieldName . '_title'] = __('What is the reason to disapprove this product?');
                    $item[$fieldName . '_submitlabel'] = __('Disapprove');
                    $item[$fieldName . '_cancellabel'] = __('Reset');
                    $item[$fieldName . '_sellerid'] = $item['seller_id'];
                    $item[$fieldName . '_productid'] = $item['mageproduct_id'];
                    $item[$fieldName . '_gridnamespace'] = 'marketplacectrl_products_list';
                    $item[$fieldName . '_formaction'] = $this->context->getUrl('marketplacectrl/product/disapprove');
                }
            }
        }

        return $dataSource;
    }
}
