<?php

namespace Branch8\Customer\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class LatestOrders extends Column
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
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        array              $components,
        array              $data,
        UrlInterface       $urlBuilder
    )
    {
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
                if(empty($item[$name])) {
                    continue;
                }
                $isFiltering = isset($item['increment_id']) && isset($item['order_id']);//is filtering
                if ($isFiltering) {
                    $implodes[] = html_entity_decode(
                        '<a target="_blank" href="' . $this->urlBuilder->getUrl(
                            self::SALES_ORDER_URL_PATH, ['order_id' => $item['order_id']]) . '">'
                        . $item['increment_id'] . '</a>');
                    $item[$this->getData('name')] = implode(' ', $implodes);
                    continue;
                }
                $parts = array_map(
                    fn($item) => explode(':', $item, 2),
                    explode('||', $item[$name])
                );
                $implodes = [];
                foreach ($parts as $part) {
                    $implodes[] = html_entity_decode(
                        '<a target="_blank" href="' .
                        $this->urlBuilder->getUrl(self::SALES_ORDER_URL_PATH, ['order_id' => $part[0]]) . '">'
                        . $part[1] . '</a>'
                    );
                }
                $item[$this->getData('name')] = implode(' ', $implodes);
            }
        }
        return $dataSource;
    }
}
