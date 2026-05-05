<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\AdvancedPermissions\Ui\Component\Listing\Column;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Customer\Model\AccountConfirmation;

/**
 * Class Order Ids column.
 */
class OrderIds extends Column
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /** Url Path */
    const SALES_ORDER_URL_PATH = 'sales/order/view';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     * @param UrlInterface $urlBuilder
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components,
        array $data,
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $orderIds = $item[$name] ? explode(',', $item[$name]) : [];
                $result = [];
                foreach ($orderIds as $orderId) {
                    $result[] = html_entity_decode('<a target="_blank" href="'.$this->urlBuilder->getUrl(self::SALES_ORDER_URL_PATH, ['order_id' => $orderId]).'">'.$orderId.'</a>');
                }
                $item[$this->getData('name')] = implode(' ', $result);
            }
        }
        return $dataSource;
    }
}
