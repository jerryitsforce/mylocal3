<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussion\Ui\Component\Listing\Column;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 *
 */
class SellerName extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param ResourceConnection $resourceConnection
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface       $urlBuilder,
        ResourceConnection $resourceConnection,
        array              $components = [],
        array              $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['thread_id'])) {
                    if (isset($item[$fieldName])) {
                        $sellerId = $item['seller_id'];
                        $item[$fieldName] = "<a href='" . $this->urlBuilder->getUrl(
                                'customer/index/edit',
                                ['id' => $sellerId, 'seller_panel' => 1]
                            ) . "' target='_blank' title='" .
                            __('View Seller') . "'>" . $item[$fieldName] . '</a>';
                    }

                }
            }
        }

        return $dataSource;
    }
}
