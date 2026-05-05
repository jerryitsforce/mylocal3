<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ViewAction
 */
class ViewParentOrderAction extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    private $orderRepository;

    private $authorization;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param OrderRepository $orderRepository
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                          $context,
        UiComponentFactory                        $uiComponentFactory,
        \Magento\Framework\AuthorizationInterface $authorization,
        OrderRepository                           $orderRepository,
        UrlInterface                              $urlBuilder,
        array                                     $components = [],
        array                                     $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->orderRepository = $orderRepository;
        $this->authorization = $authorization;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!empty($item['parent_order_id'])) {
                    $label = __('View');
                    $item['parent_link'] =$this->urlBuilder->getUrl(
                        'sales/parent_order/view',
                        ['id' => (int)$item['parent_order_id']]);
                } else {
                    $item['parent_link'] = '';
                }
            }
        }
        return $dataSource;
    }
}
