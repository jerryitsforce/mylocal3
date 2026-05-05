<?php
declare(strict_types=1);

namespace Branch8\CatalogRule\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Detail
 */
class Detail extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                          $context,
        UiComponentFactory                        $uiComponentFactory,
        UrlInterface                              $urlBuilder,
        array                                     $components = [],
        array                                     $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
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
                if (!empty($item['entity_id']) && $item['status'] == \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW) {
                    $item['detail_link'] = '<a href="'.$this->urlBuilder->getUrl(
                        'promocatalog/approval/detail',
                        ['approval_id' => (int)$item['entity_id'], 'id' => $item['catalogrule_id']]).'">Detail</a>
                        <input type="hidden" id="rname'.$item['entity_id'].'" value="'.$item['name'].'" />';
                } else {
                    $item['detail_link'] = '';
                }
            }
        }
        return $dataSource;
    }
}
